# php tar compress gzip, bzip2 file and http stream download

```bash
$ php composer.phar require "crucifyer/phptar" "dev-master"
```

```php
$xtar = new \Xeno\Compress\Tar();
$xtar->addFile('../dir/file.jpg');
$xtar->addFile('file.html', 'public_html/index.html');
$xtar->addFile('../dir/file.js', 'public_html/script.js');
$xtar->file('test.tar.bz2');
//$xtar->stream('test.tar.bz2'); // browser realtime compress download
//exit;
```