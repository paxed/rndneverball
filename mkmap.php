<?php

$prefab_cache = array();

function sort6($size)
{
  if ($size[0] > $size[3]) {
    $tmp = $size[0];
    $size[0] = $size[3];
    $size[3] = $tmp;
  }
  if ($size[1] > $size[4]) {
    $tmp = $size[1];
    $size[1] = $size[4];
    $size[4] = $tmp;
  }
  if ($size[2] > $size[5]) {
    $tmp = $size[2];
    $size[2] = $size[5];
    $size[5] = $tmp;
  }
  return $size;
}


function read_config($fname)
{

  $config = array();

  $cnf = file($fname);

  foreach ($cnf as $line) {
    if (preg_match('/^[ \t]*#/', $line)) continue;
    if (preg_match('/^[ \t]*START[ \t]*=[ \t]*(.+)$/', $line, $match)) {
	$config['START'] = array_merge(isset($config['START']) ? $config['START'] : array(), explode(",", $match[1]));
    } else if (preg_match('/^[ \t]*MAPDIR[ \t]*=[ \t]*(.+)$/', $line, $match)) {
      $config['MAPDIR'] = trim($match[1]);
    } else if (preg_match('/^[ \t]*MAP:(.+)[ \t]*=[ \t]*(.+)$/', $line, $match)) {
      $mapname = $match[1];
      array_shift($match);
      preg_match('/\((-?[0-9]+),(-?[0-9]+),(-?[0-9]+)\)-\((-?[0-9]+),(-?[0-9]+),(-?[0-9]+)\)[ \t]+(.+)$/', $match[1], $size);
      array_shift($size);
      $config['MAPS'][$mapname]['files'] = explode("|", $size[6]);
      unset($size[6]);

      $size = sort6($size);

      if ($size[0] == $size[3] && $size[1] == $size[4] && $size[2] == $size[5]) { unset($size); }
      else
	  $config['MAPS'][$mapname]['size'] = $size;
    } else if (preg_match('/^[ \t]*DEF:(.+)[ \t]*=[ \t]*(.+)$/', $line, $match)) {
      $mapname = trim($match[1]);
      $tmp = explode("|", $match[2]);

      $mf = explode("/", trim($tmp[0]));

      $config['PARTS'][$mapname]['mapfile'] = trim($mf[0]);
      array_shift($mf);
      $config['PARTS'][$mapname]['caps'] = $mf;
      array_shift($tmp);
      foreach ($tmp as $l) {
	$config['PARTS'][$mapname]['linkage'][] = explode("/", $l);
      }
    } else if (!preg_match('/^[ \t]*$/', $line)) {
    	print "Error while reading the config file. Sorry.";
	exit;
    }
  }
  return $config;
}

function shift_mapdata($data, $shifts, $uid)
{
  $ret = '';
  foreach ($data as $line) {
    if (preg_match('/^\( (-?[0-9]+) (-?[0-9]+) (-?[0-9]+) \) \( (-?[0-9]+) (-?[0-9]+) (-?[0-9]+) \) \( (-?[0-9]+) (-?[0-9]+) (-?[0-9]+) \) (.+)$/', $line, $match)) {
	$ret .= '( '.($match[1]+$shifts[0]).' '.($match[2]+$shifts[1]).' '.($match[3]+$shifts[2]).' ) ( '.
	    ($match[4]+$shifts[0]).' '.($match[5]+$shifts[1]).' '.($match[6]+$shifts[2]).' ) ( '.
	    ($match[7]+$shifts[0]).' '.($match[8]+$shifts[1]).' '.($match[9]+$shifts[2]).' ) '.$match[10]."\n";
    } else if (preg_match('/^"origin" "([-0-9]+) ([-0-9]+) ([-0-9]+)"$/', $line, $match)) {
	$ret .= '"origin" "'.($match[1]+$shifts[0]).' '.($match[2]+$shifts[1]).' '.($match[3]+$shifts[2]).'"'."\n";
    } else if (preg_match('/^"target" "(.+)"$/', $line, $match)) {
	$ret .= '"target" "'.$match[1].'_'.$uid.'"'."\n";
    } else if (preg_match('/^"targetname" "(.+)"$/', $line, $match)) {
	$ret .= '"targetname" "'.$match[1].'_'.$uid.'"'."\n";
    } else $ret .= $line;
  }
  return $ret;
}


function check_collision($a, $b)
{
  if (
      ((($a[0] >= $b[0]) && ($a[0] < $b[3])) || (($a[3] > $b[0]) && ($a[3] <= $b[3]))) &&
      ((($a[1] >= $b[1]) && ($a[1] < $b[4])) || (($a[4] > $b[1]) && ($a[4] <= $b[4]))) &&
      ((($a[2] >= $b[2]) && ($a[2] < $b[5])) || (($a[5] > $b[2]) && ($a[5] <= $b[5])))
      ) return 1;

  return 0;
}

function build_map($config, $maxlen=NULL)
{
  $ret = array();

  $curpos = array();
  $open_ends = array();
  $used_space = array();

  $curmap = $config['START'][array_rand($config['START'])];

  $open_ends[] = array($curmap, 0,0,0);

  do {
    $tmp = array_pop($open_ends);

    $prevmap = $curmap;
    $curmap = $tmp[0];
    array_shift($tmp);
    $prev_pos = $curpos;
    $curpos = $tmp;

    if (!isset($config['PARTS'][$curmap])) {
	print " // .map ERROR: Undefined map part: ".$curmap."\n";
	exit;
    }

    if ($curpos[0] < -64000 || $curpos[0] > 64000 ||
	$curpos[1] < -64000 || $curpos[1] > 64000 ||
	$curpos[2] < -64000 || $curpos[2] > 64000) {
	print " // .map ERROR: Coords out of bounds, blocking...\n";
	$blocked = 1;
    } else {

      $blocked = 0;

      if (isset($maxlen) && (--$maxlen <= 0)) $blocked = 1;
      else {
	foreach ($used_space as $used) {
	  $tmp = $config['MAPS'][$config['PARTS'][$curmap]['mapfile']]['size'];
	  if (!isset($tmp)) break;
	  $tmp[0] += intval($curpos[0]);
	  $tmp[1] += intval($curpos[1]);
	  $tmp[2] += intval($curpos[2]);

	  $tmp[3] += intval($curpos[0]);
	  $tmp[4] += intval($curpos[1]);
	  $tmp[5] += intval($curpos[2]);
	  if (check_collision($used, $tmp)) {
	    $blocked = 1;
	    break;
	  }
	}
      }
    }

    if (!$blocked) {
      $tmp = $config['MAPS'][$config['PARTS'][$curmap]['mapfile']]['files'];
      if (is_array($tmp))
	$fname = $config['MAPDIR'].trim($tmp[array_rand($tmp)]);
      else $fname = $tmp;
      $ret[] = array($fname, $curpos[0], $curpos[1], $curpos[2]);

      if (isset($config['MAPS'][$config['PARTS'][$curmap]['mapfile']]['size'])) {
        $tmp = $config['MAPS'][$config['PARTS'][$curmap]['mapfile']]['size'];
	$tmp[0] += intval($curpos[0]);
	$tmp[1] += intval($curpos[1]);
	$tmp[2] += intval($curpos[2]);

	$tmp[3] += intval($curpos[0]);
	$tmp[4] += intval($curpos[1]);
	$tmp[5] += intval($curpos[2]);

	//$tmp = sort6($tmp);

	$used_space[] = $tmp;
      }

      foreach ($config['PARTS'][$curmap]['linkage'] as $l) {
	$tmp = $l;
	//$tmp = split("/", $l);
	$x = trim($tmp[array_rand($tmp)]);
	if (preg_match('/^NONE$/', $x)) continue;
	if (preg_match('/^(.+):\((-?[0-9]+), *(-?[0-9]+), *(-?[0-9]+)\)$/', $x, $tmp)) {
	  array_shift($tmp);
	  $tmp[1] += $curpos[0];
	  $tmp[2] += $curpos[1];
	  $tmp[3] += $curpos[2];
	  $open_ends[] = $tmp;
	}
      }
    } else { /* it's blocked by something */

      //array_pop($ret);

      //array_shift($curpos);

      $tmp = $config['MAPS'][$config['PARTS'][$prevmap]['mapfile']]['size'];
      if (isset($tmp)) {
	$tmp[0] += intval($prev_pos[0]);
	$tmp[1] += intval($prev_pos[1]);
	$tmp[2] += intval($prev_pos[2]);

	$tmp[3] += intval($prev_pos[0]);
	$tmp[4] += intval($prev_pos[1]);
	$tmp[5] += intval($prev_pos[2]);

	$used_space[] = $tmp;
      }
      $tmp = $config['PARTS'][$prevmap]['caps'];
      $fname = $config['MAPDIR'].trim($tmp[array_rand($tmp)]);

      if (preg_match('/^(.+):\((-?[0-9]+),(-?[0-9]+),(-?[0-9]+)\)$/', $fname, $x)) {
	  array_shift($x);
	  $x[1] += $prev_pos[0];
	  $x[2] += $prev_pos[1];
	  $x[3] += $prev_pos[2];
	  $ret[] = $x;
      } else $ret[] = array($fname, $prev_pos[0], $prev_pos[1], $prev_pos[2]);
    }
  } while (count($open_ends) > 0);

  return $ret;
}


function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}


function output_map($config, $maxlen, &$map_length, $seed=NULL)
{
  global $prefab_cache;

  if ($seed)
    srand($seed);

  $uid = 0;

  $rndmap = build_map($config, $maxlen);

  $map_length = count($rndmap);

  $ret = " // Random NeverPutt .map - http://bilious.alt.org/~paxed/rndneverball/\n";
  $ret .= " // .map date: ".date("Y-m-d H:i:s")."\n";
  if ($seed)
    $ret .= " // .map seed: ".$seed."\n";
  $ret .= " // .map maxlen: ".$maxlen."\n";
  $ret .= " // .map blocks: ".$map_length."\n";

  foreach ($rndmap as $tmp) {

    $fname = $tmp[0];
    array_shift($tmp);

    $ret .= " // random .map part: ".$fname." @ (".$tmp[0].", ".$tmp[1].", ".$tmp[2].")\n";

    if (!isset($prefab_cache[$fname])) {
	if (file_exists($fname)) {
	    $prefab_cache[$fname] = @file($fname);
	} else {
	    $ret .= " // .map ERROR: Nonexistent map file: ".$fname."\n";
	    return $ret;
	}
    }

    $mapfiledata = $prefab_cache[$fname];
    $ret .= shift_mapdata($mapfiledata, $tmp, $uid);
    $uid++;
  }
  return $ret;
}
