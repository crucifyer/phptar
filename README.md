# php tar compress gzip, bzip2 file and http stream download

```bash
$ php composer.phar require "crucifyer/phptar" "dev-main"
```

```php
$xtar = new \Xeno\Compress\Tar();
$xtar->addFile('../dir/file.jpg');
$xtar->addFile('file.html', 'public_html/index.html');
$xtar->addFile('../dir/file.js', 'public_html/script.js');
$xtar->addString('string contents', 'string.txt');

// file
$xtar->file('test.tar.bz2');

// stream
$xtar->stream('test.tar.bz2'); // browser realtime compress download
exit;

// unicode filename stream
// url /down/load/example.php/1234/한국어파일명.tar.bz2
// $_SERVER["PATH_INFO"] is /1234/한국어파일명.tar.bz2 
$xtar->stream(null, \Xeno\Compress\Tar::BZ);
```