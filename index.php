<?php

error_reporting(E_ALL);
ini_set('display_errors','On');


function query_str($params, $sep='&amp;', $quoted=0, $encode=1)
{
  $str = '';
  foreach ($params as $key => $value) {
    $str .= (strlen($str) < 1) ? '' : $sep;
    if (($value=='') || is_null($value)) {
      $str .= $key;
      continue;
    }
    $rawval = ($encode) ? rawurlencode($value) : $value;
    if ($quoted) $rawval = '"'.$rawval.'"';
    $str .= $key . '=' . $rawval;
  }
  return ($str);
}

function phpself_querystr($querystr = null)
{
  $ret = $_SERVER['PHP_SELF'];
  $ret = preg_replace('/\/index.php$/', '/', $ret);
  if (!isset($querystr)) parse_str($_SERVER['QUERY_STRING'], $querystr);
  if (is_array($querystr)) {
    if (count($querystr)) {
      $querystr = query_str($querystr);
      if ($querystr) {
	$ret .= '?' . $querystr;
      }
    }
  } else {
    if ($querystr) {
      $ret .= '?' . $querystr;
    }
  }

  return $ret;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  include 'mkmap.php';

  $config = read_config("connectors.txt");

  $tempdir_base = "/tmp/rndneverputt";

  if (!file_exists($tempdir_base)) {
    mkdir($tempdir_base);
  }

  do {
    $coursebase = rand(0,99999);
    $tempdir = $tempdir_base.'/rndmap-'.$coursebase;
  } while (file_exists($tempdir));

  mkdir($tempdir);

  if (isset($_POST['num_levels']) && preg_match('/^[0-9]+$/', $_POST['num_levels'])) {
    $num_levels = $_POST['num_levels'];
    if ($num_levels < 2) $num_levels = 2;
    if ($num_levels > 29) $num_levels = 29;
  } else $num_levels = 18;

  if (isset($_GET['seed']) && preg_match('/^[0-9]+$/', $_GET['seed'])) {
    $seedi = intval($_GET['seed']);
  } else $seedi = intval(make_seed());

  srand($seedi);

  if (isset($_POST['lev_len']) && preg_match('/^[0-9]+$/', trim($_POST['lev_len']))) {
    $lev_len = trim($_POST['lev_len']);
  }
  switch (intval($lev_len)) {
  default:
  case 0: $maplen_min = 6;  $maplen_max = 20; break;
  case 1: $maplen_min = 6;  $maplen_max = 12; break;
  case 2: $maplen_min = 10; $maplen_max = 17; break;
  case 3: $maplen_min = 15; $maplen_max = 20; break;
  case 4: $maplen_min = 20; $maplen_max = 30; break;
  }

  if (isset($_POST['lev_par']) && preg_match('/^[0-9]+$/', trim($_POST['lev_par']))) {
    $lev_par = trim($_POST['lev_par']);
  }
  switch (intval($lev_par)) {
  case 0: $mappar_min = 1; $mappar_max = 4; break;
  default:
  case 1: $mappar_min = 3; $mappar_max = 6; break;
  case 2: $mappar_min = 5; $mappar_max = 8; break;
  }


  $filelist = array();

  for ($x = 0; $x < $num_levels; $x++) {
    $fname = sprintf("%s/%02d.map", $tempdir, ($x+1));
    $filelist[] = $fname;
    if (isset($_POST['lev_prog'])) $maplen = intval(($x/$num_levels)*($maplen_max-$maplen_min+1))+$maplen_min;
    else $maplen = rand($maplen_min, $maplen_max);

    $fh = fopen($fname, "w");
    $map_length = 1;
    fwrite($fh, output_map($config, $maplen, $map_length));

    $map_length -= rand($mappar_min,$mappar_max);
    if ($map_length < 2) $map_length = 2;
    $map_pars[$x] = $map_length;

    fclose($fh);
  }

  $backgrounds = array("blk_org", "blues", "greens", "greys", "org_yel", "pastel", "purples", "red_blu", "red_wht");

  $fname = sprintf("%s/holes-rnd%s.txt", $tempdir_base, $coursebase);
  $filelist[] = $fname;
  $fh = fopen($fname, "w");
  fwrite($fh, "rndmap-$coursebase/rndmap.jpg\n");
  fwrite($fh, "Randomly generated\\$num_levels hole course\\\\\\\n");
  for ($x = 0; $x < $num_levels; $x++) {
    $num_shots = 12;
    if ($num_shots > $map_pars[$x]) $num_shots = $map_pars[$x];
    if (!isset($bg) || (rand(0,100) < 25))
      $bg = $backgrounds[array_rand($backgrounds)];
    if (!isset($bgm) || (rand(0,100) < 25))
      $bgm = rand(1,5);
    $line = sprintf("rndmap-%s/%02d.sol  back/%s.png  %d  bgm/track%d.ogg\n", $coursebase, ($x+1), $bg, $num_shots, $bgm);
    if ($x == 0) fwrite($fh, $line);
    fwrite($fh, $line);
  }
  fclose($fh);

  $f = array();

  foreach ($filelist as $tmp) {
    $f[] = substr($tmp, strlen($tempdir_base)+1);
  }

  copy('rndmap.jpg', $tempdir_base."/rndmap-$coursebase/rndmap.jpg");
  $f[] = "rndmap-$coursebase/rndmap.jpg";

  chdir($tempdir_base);

  system('tar -cf rndmap-'.$coursebase.'.tar '.implode(' ', $f));
  system('gzip rndmap-'.$coursebase.'.tar');

  $puttfile = 'rndmap-'.$coursebase.'.tar.gz';

  header('Content-Type: binary/octet-stream');
  header('Content-Length: '.filesize($puttfile));
  header('Content-Disposition: attachment; filename="'.$puttfile.'"');
  readfile($puttfile);

  system('rm -rf '.$puttfile.' '.implode(' ', $f));
  system('rm -rf '.$tempdir);
  exit;
 } else {

  header('Content-type: text/html; charset=iso-8859-1');

  print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
  print '<html>';
  print '<head>';
  print '<title>Randomly generated Neverputt courses</title>';
  print '</head>';
  print '<body>';

  print '<h1>Randomly generated <a href="http://icculus.org/neverball/">Neverputt</a> courses</h1>';

  print '<span style="float:right;width:250px;padding:1em">';
  print '<img src="rnd1.png" alt="example level">';
  print '<img src="rnd2.png" alt="example level">';
  print '</span>';

  print '<p>Just click Generate, and you\'ll get a tar.gz packed file which contains the course .map files, and the txt file describing the course.';
  print '<p><b>NOTE: It will take approximately 10-30 seconds, so wait for the script to finish instead of clicking several times on the button.</b>';
  print '<p>You will have to compile the map-files into sol-files with the mapc-program that comes with neverputt and manually edit the <span style="background:lightgrey;font-family:monospace">courses.txt</span> to include the new course, eg.';
  print '<pre style="background:lightgrey;width:25em;padding:.5em">holes-rnd54588.txt shot-putt/putt.jpg
\Random putt course\ \ \Author: paxed\
</pre>';
#  print '<p>If you\'re using Neverputt v1.4.0, then you also need <a href="http://www.nevercorner.net/forum/viewtopic.php?id=310">Dave\'s texture pack</a>.';
  print '<p>The levels generated by this do NOT work correctly with Neverputt v1.4.0, due to texture renaming.';
  print ' Use the version from <a href="https://s.snth.net/projects/neverball/">SVN</a>.';
#  print ' Unfortunately the <a href="https://s.snth.net/projects/neverball/">SVN</a> has diverged too much so these levels do not work correctly with it.';
  print '<p><br>';
  print '<form method="POST" action="'.phpself_querystr().'" name="f1">';
  print '<table style="background:lightgrey;padding:1em">';
  print '<tr><td>Number of levels:</td><td><input type="text" name="num_levels" size="2" maxlength="2" value="18"> (2 - 29)</td></tr>';

  print '<tr><td>Level lengths:</td><td><select name="lev_len">';
  print '<option value="0" selected>Default</option>';
  print '<option value="1">Short</option>';
  print '<option value="2">Medium</option>';
  print '<option value="3">Long</option>';
  print '<option value="4">Ridiculous</option>';
  print '</select>';
  print ' <input type="checkbox" name="lev_prog" checked>Progressive';
  print '</td></tr>';

  print '<tr><td>Level pars:</td><td><select name="lev_par">';
  print '<option value="0">Easy</option>';
  print '<option value="1" selected>Normal</option>';
  print '<option value="2">Hard</option>';
  print '</select>';
  print '</td></tr>';


  print '<tr><td><input type="Submit" value="Generate"></td><td></td></tr>';
  print '</table>';
  print '</form>';

  print '<p><br><p>Problems with this generator or the levels it generates? Leave a message to <a href="http://www.nevercorner.net/forum/viewtopic.php?pid=9857">this thread</a> on Neverforum.';

  print '</body></html>';

 }
