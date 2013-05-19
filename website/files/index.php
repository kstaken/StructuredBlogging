<h1>Structured Blogging plugin download</h1>

<?
$version = file_get_contents("version.txt");

foreach (array("wp" => "Wordpress",
	       "mt" => "Movable Type") as $abbr => $title)
{
  echo "<h2>$title</h2>
<ul>
";

  foreach (array("tar.gz" => "Tarball",
		 "zip" => "Zip file") as $ext => $extdesc)
  {
    echo "<li><a href=\"structuredblogging-$abbr-$version.$ext\">$extdesc of $title plugin version $version</a></li>\n";
  }

  echo "</ul>
";
}
?>