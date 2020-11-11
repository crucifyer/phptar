<?php

chdir(__DIR__);
include_once '../vendor/autoload.php';

$xtar = new \Xeno\Compress\Tar();
$xtar->addFile('../src/Compress/Tar.php');
$xtar->addFile('example.php', 'exam/example.php');
$xtar->addFile('../composer.json');
$xtar->file('test.tar.bz2');
//$xtar->stream('test.tar.bz2'); // browser realtime compress download
//exit;