package MT::Plugin::StructuredBlogging;

# MT-SB version ##VERSION##, built ##DATE##

use base qw(MT::Plugin);
use strict;

use MT;
use MicroContent;

my $structuredblogging;
my $about = {
  name => 'MT-StructuredBlogging',
  description => 'Structured Blogging content for your Movable Type installation.',
  author_name => 'Broadband Mechanics (Phillip Pearson, Kimbro Staken, Chad Everett, Marc Senasac and Marc Canter)',
  author_link => 'http://www.broadbandmechanics.com/',
  version => '##VERSION##',
  config => \&configure_plugin_settings,
  blog_config_template => sub { $structuredblogging->load_tmpl('settings_blog.tmpl') },
  settings => new MT::PluginSettings([
    ['blog_amazon_url', { Default => 'us' }],
    ['blog_amazon_affiliate'],
    ['blog_disabled', { Default => 0 }],
    ['blog_outputthis_user'],
    ['blog_outputthis_pass'],
    ['blog_upload_dir'],
    ['blog_upload_url'],
    ['blog_use_sb_css', { Default => 1 }]
  ])
};
$structuredblogging = MT::Plugin::StructuredBlogging->new($about);
MT->add_plugin($structuredblogging);

# plugin stuff

sub configure_plugin_settings {
  my $config = {};
  if ($structuredblogging) {
    use MT::Request;
    my $r = MT::Request->instance;
    my ($scope) = (@_);
    $config = $r->cache('sb_config_'.$scope);
    if (!$config) {
      $config = $structuredblogging->get_config_hash($scope);
      $r->cache('sb_config_'.$scope, $config);
    }
  }
  $config;
}

my $printfield = sub {
    my $title = shift;
    my $field = shift;
    my $style = shift;

    my $wrapper = "<div style='%STYLE%'><fieldset class='sb_titlediv'><legend>%TITLE%</legend><div>%FIELD%</div></fieldset></div>";
    
    $wrapper =~ s/%TITLE%/$title/;
    $wrapper =~ s/%FIELD%/$field/;
    $wrapper =~ s/%STYLE%/$style/;
    
    return $wrapper;
};

my $sb_mcd_class = 'MicroContent';
my $sb_mcd_path = './plugins/StructuredBlogging/descriptions';
my $sb_mcd_outputthis_endpoint = 'http://outputthis.org/xmlrpc';

# bigpapi callbacks

MT->add_callback('bigpapi::template::edit_entry', 9, $structuredblogging, \&add_admin_stylesheet);
MT->add_callback('bigpapi::template::edit_entry', 9, $structuredblogging, \&change_form);
MT->add_callback('bigpapi::template::edit_entry', 9, $structuredblogging, \&parse_entry);
MT->add_callback('bigpapi::template::edit_entry', 9, $structuredblogging, \&navigation);
MT->add_callback('bigpapi::param::edit_entry', 9, $structuredblogging, \&add_descriptions);
MT->add_callback('bigpapi::param::edit_entry', 9, $structuredblogging, \&get_outputthis_targets);

# app callbacks

use MT::Entry;
MT::Entry->add_callback('post_save', 9, $structuredblogging, \&save_entry);

# tag callbacks

use MT::Template::Context;
MT::Template::Context->add_tag(StructuredBloggingHTML => \&return_data);
MT::Template::Context->add_tag(StructuredBloggingXML => \&return_data);

# app functions

sub instance { $structuredblogging }

# param callbacks

sub add_descriptions {
  my ($cb, $app, $param) = @_;

  # existing entry, get data
  if (my $id = $param->{'id'}) {
    my $sb_mcd_data = $structuredblogging->get_config_value('data', 'entry:'.$id);
    my $sb_mcd_type = $structuredblogging->get_config_value('type', 'entry:'.$id);
    if ($sb_mcd_type && $sb_mcd_data) {
      if (my $mcd = $sb_mcd_class->new($sb_mcd_path, $sb_mcd_type, $sb_mcd_data)) {
        $mcd->registerEditWrapper($printfield);
        my $form = $mcd->getEditor();
        $param->{'sb_mcd_form'} = $form;
        $param->{'sb_mcd_type'} = $sb_mcd_type;
      } else {
        $param->{'sb_off'} = 1;
      }
    } else {
      $param->{'sb_off'} = 1;
    }

  # new entry, select detail
  } else {
    my $vars = $app->{'query'}->Vars;
    my $blog_id = $param->{'blog_id'};
    my $scope = 'blog:'.$blog_id;
    my $sb_off = $structuredblogging->get_config_value('blog_disabled', $scope);
    if ($sb_off) {
      $param->{'sb_off'} = 1;
    } else {
      # processing by category
      if (my $sb_mcd_cat = ucfirst($vars->{'sb_mcd_cat'})) {
        my $mc = {};
        bless $mc, $sb_mcd_class;
        $mc->_buildDescriptorMap($sb_mcd_path);
        my $maps = $mc->{'map'};
        my @mcd;
        foreach my $key (keys (%$maps)) { 
          my $mcd = $maps->{$key};
          push @mcd, {
            sb_cat => $mcd->{'category'},
            sb_label => $mcd->{'label'},
            sb_path => $mcd->{'path'},
            sb_type => $mcd->{'type'}
          } if (ucfirst($mcd->{'category'}) eq $sb_mcd_cat);
        }
        @mcd = sort {
          $a->{'sb_label'} cmp $b->{'sb_label'}
        } @mcd;
        $param->{'sb_mcd'} = \@mcd;
        $param->{'sb_mcd_cat'} = $sb_mcd_cat;
      }

      # processing individual type
      if (my $sb_mcd_type = $vars->{'sb_mcd_type'}) {
        my $mcd = $sb_mcd_class->new($sb_mcd_path, $sb_mcd_type);
        if ($mcd) {
          $mcd->registerEditWrapper($printfield);
          my $form = $mcd->getEditor();
          $param->{'sb_mcd_form'} = $form;
          $param->{'sb_mcd_type'} = $sb_mcd_type;

          # lookup links
          my $amazon = $structuredblogging->get_config_value('blog_amazon_url', $scope);
          $param->{'sb_amazon_url'} = 'http://xml-'.$amazon.'.amznxslt.com';
          $param->{'sb_amazon_affiliate'} = $structuredblogging->get_config_value('blog_amazon_affiliate', $scope);
          $param->{'sb_proxy_url'} = 'plugins/StructuredBlogging/sb-get.cgi';
        }
      }

      # no cat?  no type?  turn it off
      unless ($vars->{'sb_mcd_cat'} || $vars->{'sb_mcd_type'}) {
        $param->{'sb_off'} = 1;
      }
    }
  }
}

sub get_outputthis_targets {
  my ($cb, $app, $param) = @_;

  my $blog_id = $param->{'blog_id'};
  my $scope = 'blog:'.$blog_id;
  my $sb_off = $structuredblogging->get_config_value('blog_disabled', $scope);

  unless ($sb_off) {
    my $user = $structuredblogging->get_config_value('blog_outputthis_user', $scope);
    my $pass = $structuredblogging->get_config_value('blog_outputthis_pass', $scope);
    if ($user) {
      use XMLRPC::Lite;
      $param->{'sb_outputthis_off'} = 0;
      my $request = XMLRPC::Lite->proxy($sb_mcd_outputthis_endpoint);
      my $response = $request->call('outputthis.getPublishedTargets', $user, $pass);
      my $sb_ot_targets = $response->result();
      if ($sb_ot_targets) {
        my $i = 1;
        my @sb_ot_targets;
        foreach (@$sb_ot_targets) {
          push @sb_ot_targets, {
            sb_ot_target_id => $_->{'ID'},
            sb_ot_target_even => ($i % 2) == 0 ? 1 : 0,
            sb_ot_target_title => $_->{'title'}
          };
          $i++;
        }
        $param->{'sb_outputthis_targets'} = \@sb_ot_targets;
        $param->{'sb_outputthis_user'} = $user;
      }
    } else {
      $param->{'sb_outputthis_off'} = 1;
    }
  }
}

# template callbacks

sub add_admin_stylesheet {
  my ($cb, $app, $template) = @_;
  open FOO, ">/tmp/mt-tmpl.txt";
  print FOO $$template;
  close FOO;
  my $old = qq{<link rel="stylesheet"};
  $old = quotemeta($old);
  my $new = qq{<link rel="stylesheet" href="plugins/StructuredBlogging/sb-styles.css" type="text/css" />
<link rel="stylesheet"};
  $$template =~ s/$old/$new/;
}

sub change_form {
  my ($cb, $app, $template) = @_;
  my $old = qq{<form name="entry_form" method="post"};
  $old = quotemeta($old);
  my $new = <<"HTML1";
<form name="entry_form" method="post" enctype="multipart/form-data"
HTML1
  $$template =~ s/$old/$new/;
}

sub navigation {
  my ($cb, $app, $template) = @_;
  my $old = qq{<TMPL_IF NAME=CAN_EDIT_ENTRIES><li><a<TMPL_IF NAME=NAV_ENTRIES>};
  $old = quotemeta($old);
  my $new = <<"HTML2";
<TMPL_IF NAME=CAN_POST><li><a<TMPL_IF NAME=NAV_NEW_ENTRY> class="here"</TMPL_IF> style="background-image: url(<TMPL_VAR NAME=STATIC_URI>images/nav_icons/color/new-entry.gif);" title="<MT_TRANS phrase="Create New Review Entry">" href="<TMPL_VAR NAME=MT_URL>?__mode=view&_type=entry&amp;blog_id=<TMPL_VAR NAME=BLOG_ID>&amp;sb_mcd_cat=review"><MT_TRANS phrase="New Review"></a></li></TMPL_IF>
<TMPL_IF NAME=CAN_POST><li><a<TMPL_IF NAME=NAV_NEW_ENTRY> class="here"</TMPL_IF> style="background-image: url(<TMPL_VAR NAME=STATIC_URI>images/nav_icons/color/new-entry.gif);" title="<MT_TRANS phrase="Create New Event Entry">" href="<TMPL_VAR NAME=MT_URL>?__mode=view&_type=entry&amp;blog_id=<TMPL_VAR NAME=BLOG_ID>&amp;sb_mcd_cat=event"><MT_TRANS phrase="New Event"></a></li></TMPL_IF>
<TMPL_IF NAME=CAN_POST><li><a<TMPL_IF NAME=NAV_NEW_ENTRY> class="here"</TMPL_IF> style="background-image: url(<TMPL_VAR NAME=STATIC_URI>images/nav_icons/color/new-entry.gif);" title="<MT_TRANS phrase="Create New List Entry">" href="<TMPL_VAR NAME=MT_URL>?__mode=view&_type=entry&amp;blog_id=<TMPL_VAR NAME=BLOG_ID>&amp;sb_mcd_cat=list"><MT_TRANS phrase="New List"></a></li></TMPL_IF>
<TMPL_IF NAME=CAN_POST><li><a<TMPL_IF NAME=NAV_NEW_ENTRY> class="here"</TMPL_IF> style="background-image: url(<TMPL_VAR NAME=STATIC_URI>images/nav_icons/color/new-entry.gif);" title="<MT_TRANS phrase="Create New Group Showcase">" href="<TMPL_VAR NAME=MT_URL>?__mode=view&_type=entry&amp;blog_id=<TMPL_VAR NAME=BLOG_ID>&amp;sb_mcd_cat=showcase"><MT_TRANS phrase="New Showcase"></a></li></TMPL_IF>
<TMPL_IF NAME=CAN_POST><li><a<TMPL_IF NAME=NAV_NEW_ENTRY> class="here"</TMPL_IF> style="background-image: url(<TMPL_VAR NAME=STATIC_URI>images/nav_icons/color/new-entry.gif);" title="<MT_TRANS phrase="Create New Audio Entry">" href="<TMPL_VAR NAME=MT_URL>?__mode=view&_type=entry&amp;blog_id=<TMPL_VAR NAME=BLOG_ID>&amp;sb_mcd_type=media/audio"><MT_TRANS phrase="New Audio Post"></a></li></TMPL_IF>
<TMPL_IF NAME=CAN_POST><li><a<TMPL_IF NAME=NAV_NEW_ENTRY> class="here"</TMPL_IF> style="background-image: url(<TMPL_VAR NAME=STATIC_URI>images/nav_icons/color/new-entry.gif);" title="<MT_TRANS phrase="Create New Image Entry">" href="<TMPL_VAR NAME=MT_URL>?__mode=view&_type=entry&amp;blog_id=<TMPL_VAR NAME=BLOG_ID>&amp;sb_mcd_type=media/image"><MT_TRANS phrase="New Image Post"></a></li></TMPL_IF>
<TMPL_IF NAME=CAN_POST><li><a<TMPL_IF NAME=NAV_NEW_ENTRY> class="here"</TMPL_IF> style="background-image: url(<TMPL_VAR NAME=STATIC_URI>images/nav_icons/color/new-entry.gif);" title="<MT_TRANS phrase="Create New Video Entry">" href="<TMPL_VAR NAME=MT_URL>?__mode=view&_type=entry&amp;blog_id=<TMPL_VAR NAME=BLOG_ID>&amp;sb_mcd_type=media/video"><MT_TRANS phrase="New Video Post"></a></li></TMPL_IF>
<TMPL_IF NAME=CAN_EDIT_ENTRIES><li><a<TMPL_IF NAME=NAV_ENTRIES>
HTML2
  $$template =~ s/$old/$new/;
}

sub parse_entry {
  my ($cb, $app, $template) = @_;

  my $old = qq{<div id="body-box">};
  $old = quotemeta($old);
  my $new = <<"HTML3";
<TMPL_UNLESS NAME=SB_OFF>
  <div class="body-structuredblogging">
  <TMPL_IF NAME=SB_MCD>
    <script type="text/javascript">
    <!--
    function gotopage(form) {
      var index = form.sb_mcd_type.selectedIndex;
      if (form.sb_mcd_type.options[index].value != "0") {
        var url = form.return_args.value;
        location = ScriptURI + "?" + url + "&sb_mcd_type=" + form.sb_mcd_type.options[index].value;
      }
    }
    //-->
    </script>
    <div class="structuredblogging-type-menu">
      Structured Blogging <MT_TRANS phrase="is enabled.">
      <TMPL_VAR NAME=SB_MCD_CAT> <MT_TRANS phrase="type to create:">
      <select name="sb_mcd_type" onchange="gotopage(this.form);">
        <option value="0"><MT_TRANS phrase="Available types..."></option>
        <TMPL_LOOP NAME=SB_MCD>
          <option value="<TMPL_VAR NAME=SB_TYPE>"><TMPL_VAR NAME=SB_LABEL></option>
        </TMPL_LOOP>
      </select>
    </div>
  <TMPL_ELSE>
    <input type="hidden" name="sb_mcd_type" value="<TMPL_VAR NAME=SB_MCD_TYPE>" />
  </TMPL_IF>
  <TMPL_IF NAME=SB_MCD_FORM>
    <script type="text/javascript" src="plugins/StructuredBlogging/js/prototype.js"></script>
    <script type="text/javascript" src="plugins/StructuredBlogging/js/scriptaculous.js"></script>
    <script type="text/javascript" src="plugins/StructuredBlogging/js/sb-lookup.js"></script>
    <TMPL_VAR NAME=SB_MCD_FORM>
    <script type="text/javascript">
    <!--
      lookupAddLinks("<TMPL_VAR NAME=SB_PROXY_URL>", "<TMPL_VAR NAME=SB_AMAZON_URL>", "<TMPL_VAR NAME=SB_AMAZON_AFFILIATE>");
    //-->
    </script>
    <div><fieldset id="titlediv"><legend><a href="http://outputthis.org">OutputThis.org</a></legend><div>
    <TMPL_UNLESS NAME=SB_OUTPUTTHIS_OFF>
      <TMPL_IF NAME=SB_OUTPUTTHIS_TARGETS>
        <TMPL_LOOP NAME=SB_OUTPUTTHIS_TARGETS>
          <input type="checkbox" name="sb_outputthis_target" value="<TMPL_VAR NAME=SB_OT_TARGET_ID>" /> <TMPL_VAR NAME=SB_OT_TARGET_TITLE>
          <TMPL_IF NAME=SB_OT_TARGET_EVEN><br /></TMPL_IF>
        </TMPL_LOOP>
      <TMPL_ELSE>
        <MT_TRANS phrase="The outputthis.org service is currently unavailable.">
      </TMPL_IF>
    <TMPL_ELSE>
      <MT_TRANS phrase="You must"> <a href="<TMPL_VAR NAME=SCRIPT_URL>?__mode=cfg_plugins&blog_id=<TMPL_VAR NAME=BLOG_ID>"><MT_TRANS phrase="configure"></a> <MT_TRANS phrase="your outputthis.org account information if you want to use the outputthis.org service.">
    </TMPL_UNLESS>
    </div></fieldset></div>
  </TMPL_IF>
  </div>
  <div id="body-box"<TMPL_IF NAME=SB_MCD_FORM> style="display: none;"</TMPL_IF>>
<TMPL_ELSE>
  <div id="body-box">
</TMPL_UNLESS>
HTML3
  $$template =~ s/$old/$new/;
}

# data callbacks

sub save_entry {
  my ($err, $obj) = @_;
  my $app = MT->instance;
  my $cgi = $app->{'query'};
  my $vars = $cgi->Vars;
  if (my $use = $vars->{'sb_mcd_type'}) {
    my $sb_upload_dir = $structuredblogging->get_config_value('blog_upload_dir', 'blog:'.$obj->blog_id);
    my $sb_upload_url = $structuredblogging->get_config_value('blog_upload_url', 'blog:'.$obj->blog_id);
    if (my $mcd = $sb_mcd_class->new($sb_mcd_path, $use, '', $cgi, '', $sb_upload_dir, $sb_upload_url)) {
      if (my $xml = $mcd->getInstanceXml()) {
        $structuredblogging->set_config_value('data', $xml, 'entry:'.$obj->id);
        $structuredblogging->set_config_value('type', $use, 'entry:'.$obj->id);

        # push to outputthis.org
        my $scope = 'blog:'.$obj->blog_id;
        my $user = $structuredblogging->get_config_value('blog_outputthis_user', $scope);
        my $pass = $structuredblogging->get_config_value('blog_outputthis_pass', $scope);
        if ($user) {
          my %outputthis_entry = (
            'title' => $obj->title,
	    'description' => $obj->body . $mcd->getView() . sb_wrap_xml($obj, $mcd->getInstanceXml())
          );
          my @outputthis_targets;
          foreach ($app->param('sb_outputthis_target')) {
            my %entry = (
              'ID' => $_,
              'status' => 'publish'
            );
            push @outputthis_targets, \%entry;
          }
          use XMLRPC::Lite;
          my $request = XMLRPC::Lite->proxy($sb_mcd_outputthis_endpoint); 
          my $response = $request->call('outputthis.publishPost', $user, $pass, \@outputthis_targets, \%outputthis_entry);
        }
      }
    }
  }
}

# tags

sub sb_wrap_xml {
    my $entry = shift;
    my $xml = shift;
    return qq{<script type="application/x-subnode; charset=utf-8">
<!-- the following is structured blog data for machine readers. -->
<subnode alternate-for-id="sbentry_}.$entry->id.qq{" xmlns:data-view="http://www.w3.org/2003/g/data-view#" data-view:interpreter="http://structuredblogging.org/subnode-to-rdf-interpreter.xsl" xmlns="http://www.structuredblogging.org/xmlns#subnode">
<xml-structured-blog-entry xmlns="http://www.structuredblogging.org/xmlns">
<generator id="mtsb-}.$entry->id.qq{" type="x-mtsb-post" version="1"/>
} . $xml . qq{</xml-structured-blog-entry>
</subnode>
</script>};
}

sub return_data {
  my ($ctx, $args) = @_;
  my $entry = $ctx->stash('entry');
  return '' unless ($entry);
  my $sb_mcd_data = $structuredblogging->get_config_value('data', 'entry:'.$entry->id);
  my $sb_mcd_type = $structuredblogging->get_config_value('type', 'entry:'.$entry->id);
  if ($sb_mcd_type && $sb_mcd_data) {
    if (my $mcd = $sb_mcd_class->new($sb_mcd_path, $sb_mcd_type, $sb_mcd_data)) {
      if ($ctx->stash('tag') =~ m/XML$/ ) {
        return sb_wrap_xml($entry, $mcd->getInstanceXml());
      } else {
	return '<div id="sbentry_'.$entry->id.'">'.$mcd->getView()."</div>";
      }
    }
  }
  return '';
}

1;
