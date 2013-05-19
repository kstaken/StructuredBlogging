<h1>Structured Blogging: All Microcontent Types</h1>

<?

$d = opendir(".");
$entries = array();
while ($e = readdir($d))
{
  if (!preg_match("/^(.*?)\.xml$/", $e, $m)) continue;
  $f = fopen($e, "rt");
  $txt = fread($f, 1024);
  fclose($f);
  if (preg_match('/type="(.*?)"\s*label="(.*?)"/', $txt, $m))
    array_push($entries, array($m[1], $m[2], $e));
}
closedir($d);

sort($entries);

foreach ($entries as $e)
{
  list($mctype, $label, $fn) = $e;
  echo "<li><a href=\"$fn\">$mctype</a>: $label</li>";
}

?>