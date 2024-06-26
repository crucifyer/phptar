<?php

chdir(__DIR__);
include_once '../vendor/autoload.php';

$xtar = new \Xeno\Compress\Tar();
$xtar->addFile('../src/Compress/Tar.php');
$xtar->addFile('example.php', 'exam/example.php');
$xtar->addFile('../composer.json', '../dir/.../test///..//');
$xtar->addString('string contents', 'string.txt');

// file
$xtar->save('test.tar.bz2');
exit;

// stream
$xtar->stream('test.tar.bz2'); // browser realtime compress download
exit;

// unicode filename stream
// url /down/load/example.php/1234/한국어파일명.tar.bz2
// $_SERVER["PATH_INFO"] is /1234/한국어파일명.tar.bz2
$xtar->stream(null, \Xeno\Compress\Tar::BZ);
exit;