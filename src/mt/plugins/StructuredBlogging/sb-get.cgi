#!/usr/bin/perl -w

# Proxies a request to a remote service for use by an AJAX request.

use strict;
use CGI;

print "Content-Type: text/xml\n\n";

my $q = new CGI;
my $url = $q->param('url');
$url =~ s/ /%20/g;

# No deal unless it's looking for one of these URLs.

$url = '' unless (
  $url =~ m~^http://xml-(ca|de|fr|jp|uk|us).amznxslt.com~
);

if ($url) {
  require LWP::UserAgent;
  my $ua = LWP::UserAgent->new;
  $ua->timeout(15);
  $ua->agent("MT-StructuredBlogging");
  my $req = HTTP::Request->new(GET => $url);
  my $result = $ua->request($req);
  if ($result->is_success) {
    print $result->content;
  } else {
    print 'ERROR: Connection failed.';
  }
} else {
  print 'ERROR: Can\'t connect to that URL.';
}
