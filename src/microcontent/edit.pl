#!/usr/bin/env perl -w
use strict;
use lib "MT-3.2-en_US/extlib";
use MicroContent;
use CGI;

my $printfield = sub {
    my $title = shift;
    my $field = shift;
    my $style = shift;

    my $wrapper = "<div style='%STYLE%'><fieldset id='titlediv'><legend><a>%TITLE%</a></legend><div>%FIELD%</div></fieldset></div>";
    
    $wrapper =~ s/%TITLE%/$title/;
    $wrapper =~ s/%FIELD%/$field/;
    $wrapper =~ s/%STYLE%/$style/;
    
    return $wrapper;
};


my $cgi = CGI->new();


my $schema = "";
my $data = "";


my $mc;
#print "Content-type: text/html\n\n";
# read in the data if any
if ($cgi->param("instance")) {
    my $instanceFile = $cgi->param("instance");
    open(FILE, $instanceFile);
    my @instance = <FILE>;
    $data = join("", @instance);
    close(FILE);
    $mc = MicroContent->new("../wp/wpsb-files/microcontent/descriptions", $cgi->param("type"), $data, "", "", "uploads", "uploads");  
}
else {
    my %vars = $cgi->Vars;
    $mc = MicroContent->new("../wp/wpsb-files/microcontent/descriptions", $cgi->param("type"), "", $cgi, "", "uploads", "uploads");    
}

$mc->registerEditWrapper($printfield);

if ($cgi->param("xml")) {
    print "Content-type: text/xml\n\n";

    print $mc->getInstanceXml();
}
elsif ($cgi->param("html")) {
    print "Content-type: text/html\n\n";

    print $mc->getView();
}
else {
    print $cgi->header();
    print '<div id="poststuff" style="clear: all">';
    print '<form action="edit.pl" method="post" enctype="multipart/form-data">';

    print $mc->getEditor();

    print '<input type="hidden" name="type" value="' . $cgi->param("type") . '"/>';

    print '<input type="submit" name="xml" value="View XML"/>';
    print '<input type="submit" name="html" value="View HTML"/>';
    print '</form></div>';    
}

