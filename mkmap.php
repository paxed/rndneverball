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

function shift_coords3($coords, $shifts)
{
    $coords[0] = ($coords[0] + $shifts[0]);
    $coords[1] = ($coords[1] + $shifts[1]);
    $coords[2] = ($coords[2] + $shifts[2]);

    $coords[3] = ($coords[3] + $shifts[0]);
    $coords[4] = ($coords[4] + $shifts[1]);
    $coords[5] = ($coords[5] + $shifts[2]);

    $coords[6] = ($coords[6] + $shifts[0]);
    $coords[7] = ($coords[7] + $shifts[1]);
    $coords[8] = ($coords[8] + $shifts[2]);
    return $coords;
}

function shift_coords2($coords, $shifts)
{
    $coords[0] = ($coords[0] + $shifts[0]);
    $coords[1] = ($coords[1] + $shifts[1]);
    $coords[2] = ($coords[2] + $shifts[2]);

    $coords[3] = ($coords[3] + $shifts[0]);
    $coords[4] = ($coords[4] + $shifts[1]);
    $coords[5] = ($coords[5] + $shifts[2]);
    return $coords;
}

function shift_coords1($coords, $shifts)
{
    $coords[0] = ($coords[0] + $shifts[0]);
    $coords[1] = ($coords[1] + $shifts[1]);
    $coords[2] = ($coords[2] + $shifts[2]);
    return $coords;
}

function rotate_coords3($coords, $rotate)
{
    switch ($rotate) {
    default:
    case 0: break;
    case 1:
       $tmp = $coords[0];
       $coords[0] = $coords[1];
       $coords[1] = ($tmp * -1);

       $tmp = $coords[3];
       $coords[3] = $coords[4];
       $coords[4] = ($tmp * -1);

       $tmp = $coords[6];
       $coords[6] = $coords[7];
       $coords[7] = ($tmp * -1);
       break;
    case 2:
       $coords[0] = $coords[0] * -1;
       $coords[1] = $coords[1] * -1;

       $coords[3] = $coords[3] * -1;
       $coords[4] = $coords[4] * -1;

       $coords[6] = $coords[6] * -1;
       $coords[7] = $coords[7] * -1;
       break;
    case 3:
       $tmp = $coords[0];
       $coords[0] = ($coords[1] * -1);
       $coords[1] = $tmp;

       $tmp = $coords[3];
       $coords[3] = ($coords[4] * -1);
       $coords[4] = $tmp;

       $tmp = $coords[6];
       $coords[6] = ($coords[7] * -1);
       $coords[7] = $tmp;
       break;
    }
    return $coords;
}

function rotate_coords2($coords, $rotate)
{
    switch ($rotate) {
    default:
    case 0: break;
    case 1:
       $tmp = $coords[0];
       $coords[0] = $coords[1];
       $coords[1] = ($tmp * -1);

       $tmp = $coords[3];
       $coords[3] = $coords[4];
       $coords[4] = ($tmp * -1);
       break;
    case 2:
       $coords[0] = $coords[0] * -1;
       $coords[1] = $coords[1] * -1;

       $coords[3] = $coords[3] * -1;
       $coords[4] = $coords[4] * -1;
       break;
    case 3:
       $tmp = $coords[0];
       $coords[0] = ($coords[1] * -1);
       $coords[1] = $tmp;

       $tmp = $coords[3];
       $coords[3] = ($coords[4] * -1);
       $coords[4] = $tmp;
       break;
    }
    return $coords;
}

function rotate_coords1($coords, $rotate)
{
    switch ($rotate) {
    default:
    case 0: break;
    case 1:
       $tmp = $coords[0];
       $coords[0] = $coords[1];
       $coords[1] = ($tmp * -1);
       break;
    case 2:
       $coords[0] = $coords[0] * -1;
       $coords[1] = $coords[1] * -1;
       break;
    case 3:
       $tmp = $coords[0];
       $coords[0] = ($coords[1] * -1);
       $coords[1] = $tmp;
       break;
    }
    return $coords;
}


function parse_coord_int($str)
{
    if (preg_match('/^\(\s*(-?[0-9]+)\s*,\s*(-?[0-9]+)\s*,\s*(-?[0-9]+)\s*\)$/', trim($str), $coords)) {
	array_shift($coords);
	return $coords;
    }
    print "ERROR parsing coord $str\n";
    return array(0,0,0);
}

function parse_size_int($str)
{
    if (preg_match('/^(.+\))\s*-\s*(\(.+)$/', $str, $parts)) {
	return array_merge(parse_coord_int($parts[1]), parse_coord_int($parts[2]));
    }
    return array(0,0,0,0,0,0);
}

function config_test($config)
{
    if (!isset($config['START'])) print '<p>START not defined.';
    if (!isset($config['MAPDIR'])) print '<p>MAPDIR not defined.';
    if (!file_exists($config['MAPDIR'])) print '<p>MAPDIR does not exist.';

    foreach ($config['DEFINE'] as $k => $v) {
	if (count($v['prefab']) == 0) print "<p>DEFINE $k has no prefabs.";
	foreach ($v['prefab'] as $f) {
	    if (!file_exists($f)) print "<p>File ".$f." does not exist.";
	}
	foreach ($v['next'] as $i) {
	    if (!isset($config['DEFINE'][$i])) print '<p>join '.$i.' used, but not DEFINEd.';
	}
    }
}

function config_define_part(&$config, $cur_define, $param, $data)
{
    switch ($param) {
    case 'size':
	$config['DEFINE'][$cur_define]['size'] = parse_size_int($data);
	$config['DEFINE'][$cur_define]['use_size'] = 1;
	break;
    case 'cost':
	$config['DEFINE'][$cur_define]['cost'] = intval($data);
	break;
    case 'prefab':
	$config['DEFINE'][$cur_define]['prefab'] = array_merge(glob($config['MAPDIR'].trim($data)), $config['DEFINE'][$cur_define]['prefab']);
	break;
    case 'next':
	$config['DEFINE'][$cur_define]['next'][] = trim($data);
	$config['DEFINE'][$cur_define]['n_nexts']++;
	break;
    case 'exit':
	preg_match('/^(.+?)(,\s*r\s*=\s*([+-]?[0-9]+))?\s*$/', $data, $tmp);
	$config['DEFINE'][$cur_define]['exit'][] = array(
							 'coord'=>parse_coord_int($tmp[1]),
							 'rot'=>(isset($tmp[3]) ? intval($tmp[3]) : 0)
							 );
    default: break;
    }
}

function read_config($fname, $test=0)
{

  $config = array();

  $cnf = file($fname);

  $config['DEFINE'] = array();

  $state = 0;
  $cur_define = '';

  foreach ($cnf as $line) {
      if ($line==="\n" || preg_match('/^\s*#/', $line)) continue;
      if ($state == 0 || $state == 1) {
	  if (preg_match('/^\s*([A-Z]+)\s*=\s*(.+)$/', $line, $match)) {
	      switch ($match[1]) {
	      case 'START':
		  $config['START'] = $match[2];
		  break;
	      case 'FINISH':
		  $config['FINISH'] = explode(',', $match[2]);
		  break;
	      case 'MAPDIR':
		  $config['MAPDIR'] = trim($match[2]);
		  break;
	      case 'DEFCAP':
		  $default_capping = trim($match[2]);
		  break;
	      case 'DEFINE':
		  $cur_define = trim($match[2]);
		  $state = 1;
		  if (preg_match('/^(.+){$/', $cur_define, $match)) {
		      $cur_define = trim($match[1]);
		      $state = 2;
		  }
		  $config['DEFINE'][$cur_define] = array(
							 'size' => array(0,0,0,0,0,0),
							 'use_size' => 0,
							 'prefab' => array(),
							 'next' => array(),
							 'n_nexts' => 0,
							 'exit' => array(),
							 'cap' => $default_capping,
							 'cost' => 1
							 );
		  break;
	      default:
		  print 'Error parsing config file.';
		  exit;
	      }
	  } else if ($state == 1 && preg_match('/^\s*{\s*$/', $line, $match)) {
	      $state = 2;
	  } else {
	      print 'Error parsing config file.';
	      exit;
	  }
      } else if ($state == 2) {
	  if (preg_match('/^\s*}/', $line)) {
	      if (count($config['DEFINE'][$cur_define]['exit']) == 0) {
		  $config['DEFINE'][$cur_define]['exit'][] = array('coord'=>array(0,0,0),'rot'=>0);
	      }
	      $state = 0;
	      continue;
	  } else if (preg_match('/^\s*(\S+)\s*:(.+)$/', $line, $match)) {
	      config_define_part($config, $cur_define, $match[1], $match[2]);
	  }
      }
  }

  if ($test) config_test($config);

  return $config;
}

function shift_mapdata($data, $shifts, $uid, $rotate=0)
{
  $ret = '';
  foreach ($data as $line) {
    if (preg_match('/^\( (-?[0-9.]+) (-?[0-9.]+) (-?[0-9.]+) \) \( (-?[0-9.]+) (-?[0-9.]+) (-?[0-9.]+) \) \( (-?[0-9.]+) (-?[0-9.]+) (-?[0-9.]+) \) (.+)$/', $line, $match)) {
	array_shift($match);
	$match = rotate_coords3($match, $rotate);
	$match = shift_coords3($match, $shifts);
	$ret .= '( '.($match[0]).' '.($match[1]).' '.($match[2]).' ) ( '.
	    ($match[3]).' '.($match[4]).' '.($match[5]).' ) ( '.
	    ($match[6]).' '.($match[7]).' '.($match[8]).' ) '.$match[9]."\n";
    } else if (preg_match('/^"origin" "([-0-9.]+) ([-0-9.]+) ([-0-9.]+)"$/', $line, $match)) {
       array_shift($match);
       $match = rotate_coords1($match, $rotate);
       $match = shift_coords1($match, $shifts);
       $ret .= '"origin" "'.($match[0]).' '.($match[1]).' '.($match[2]).'"'."\n";
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

function build_map($config, $maxlen=999999)
{
  $ret = array();

  $curpos = array();
  $open_ends = array();
  $used_space = array();

  $curmap = $config['START'];
  $currot = 0;

  $open_ends[] = array('curmap'=>$curmap, 'coord'=>array(0,0,0), 'rot' => 0);
  $finish = 0;

  do {
    $tmp = array_pop($open_ends);

    $curmap = $tmp['curmap'];
    $curpos = $tmp['coord'];
    $currot = $tmp['rot'];

    $ret[] = array('msg' => ".map CURMAP: $curmap");

    $blocked = 0;
    $cost = $config['DEFINE'][$curmap]['cost'];

    $maxlen -= $cost;

    if (($maxlen <= 0) && !$finish) {
	$ret[] = array('msg' => ".map maxlen reached");
	$open_ends = array();
	$open_ends[] = array('curmap' => $config['FINISH'][array_rand($config['FINISH'])],
			     'coord' => $tmpcoord,
			     'rot' => $tmprot
			     );
	$finish = 1;
	continue;
    } else if ($curpos[0] < -64000 || $curpos[0] > 64000 ||
	       $curpos[1] < -64000 || $curpos[1] > 64000 ||
	       $curpos[2] < -64000 || $curpos[2] > 64000) {
	$ret[] = array('msg' => '.map ERROR: Coords out of bounds');
	$blocked = 1;
    } else if ($config['DEFINE'][$curmap]['use_size']) {
	foreach ($used_space as $used) {
	    $tmp = $config['DEFINE'][$curmap]['size'];
	    $tmp = rotate_coords2($tmp, $currot);
	    $tmp = shift_coords2($tmp, $curpos);
	    $tmp = sort6($tmp);
	    if (check_collision($used, $tmp)) {
		$ret[] = array('msg' => '.map collision: ('.join(',', $tmp).')');
		$blocked = 1;
		break;
	    }
	}
    }

    if ($blocked) {
	$curmap = $config['DEFINE'][$curmap]['cap'];
    }

    $prefabs = $config['DEFINE'][$curmap]['prefab'];
    $fname = $prefabs[array_rand($prefabs)];
    $ret[] = array('prefab' => $fname, 'coord' => $curpos, 'rot' => $currot);

    if ($config['DEFINE'][$curmap]['use_size']) {
	$tmp = $config['DEFINE'][$curmap]['size'];
	$tmp = rotate_coords2($tmp, $currot);
	$tmp = shift_coords2($tmp, $curpos);
	$used_space[] = sort6($tmp);
	$ret[] = array('msg' => '.map used_space: ('.join(',', $tmp).')');
    }

    if ($config['DEFINE'][$curmap]['n_nexts']) {
	$nxt = $config['DEFINE'][$curmap]['next'];
	foreach ($config['DEFINE'][$curmap]['exit'] as $e) {
	    $n = $nxt[array_rand($nxt)];
	    $tmpcoord = $e['coord'];
	    $tmpcoord = rotate_coords1($tmpcoord, $currot);
	    $tmpcoord = shift_coords1($tmpcoord, $curpos);
	    $tmprot = (($currot + $e['rot']) % 4);
	    $open_ends[] = array('curmap' => $n,
				 'coord' => $tmpcoord,
				 'rot' => $tmprot
				 );
	    $ret[] = array('msg' => ".map: $n, (".join(',', $tmpcoord)."), r=$tmprot");
	}
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

  $map_length = 0;

  $ret = '';

  foreach ($rndmap as $tmp) {

      if (isset($tmp['msg'])) {
	  $ret .= " // ".$tmp['msg']."\n";
	  continue;
      }

      $map_length++;

    $fname = $tmp['prefab'];
    $coord = $tmp['coord'];
    $rot = $tmp['rot'];

    $ret .= " // random .map part: ".$fname." @ (".join(', ', $coord)."), r=$rot\n";

    if (!isset($prefab_cache[$fname])) {
	if (file_exists($fname)) {
	    $prefab_cache[$fname] = @file($fname);
	} else {
	    $ret .= " // .map ERROR: Nonexistent map file: ".$fname."\n";
	    return $ret;
	}
    }

    $mapfiledata = $prefab_cache[$fname];
    $ret .= shift_mapdata($mapfiledata, $coord, $uid, $rot);
    $uid++;
  }

  $head = " // Random NeverPutt .map - http://bilious.alt.org/~paxed/rndneverball/\n";
  $head .= " // .map date: ".date("Y-m-d H:i:s")."\n";
  if ($seed)
    $head .= " // .map seed: ".$seed."\n";
  $head .= " // .map maxlen: ".$maxlen."\n";
  $head .= " // .map blocks: ".$map_length."\n";

  return $head.$ret;
}
