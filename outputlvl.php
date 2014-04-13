<?php

error_reporting(E_ALL);
ini_set('display_errors','On');


include 'mkmap.php';

$config = read_config("connectors.txt", 1);

print "\n<pre>\n";

print_r($config);


$map_length = 0;
$maplen = 100;

$seed = rand();

print output_map($config, $maplen, $map_length, $seed);
