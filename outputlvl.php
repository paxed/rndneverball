<?php

include 'mkmap.php';

$config = read_config("connectors.txt");

#print_r($config);
#exit;

$map_length = 0;
$maplen = 100;

print output_map($config, $maplen, $map_length);

?>
