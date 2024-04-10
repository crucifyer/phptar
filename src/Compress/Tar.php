<?php

namespace Xeno\Compress;

// https://en.wikipedia.org/wiki/Tar_(computing)#UStar_format
class Tar
{
	const NONE = 0, GZ = 1, BZ = 2, DETECT = 99;
	private $headers = [], $f = [];

	private function addBlock($block) {
		// star ext header not implemented
		if($block['name'] == '') throw new \ErrorException('embed filename needed.');
		while(true) {
			$name = preg_replace(['~[^/]+/\.\./~', '~^\.*/~', '~/\.{3,}/~', '~/\./~', '~/{2,}~'], ['', '', '/', '/', '/'], $block['name']);
			if($block['name'] == $name) break;
			$block['name'] = $name;
		}
		if(substr($block['name'], -1) == '/') {
			if(!$block['file']) throw new \ErrorException('embed filename needed. '.$block['name']);
			$block['name'] = $block['name'].basename($block['file']);
		}

		if(strlen($block['name']) > 99) throw new \ErrorException('embed filename must be less then 100 bytes. '.$block['name']);
		if($block['size'] > 077777777777) throw new \ErrorException('file size over.', E_USER_ERROR);
		$this->headers[] = $block;
	}

	public function addFile($file, $name = '', $permis = 0644, $uid = 0, $gid = 0, $uname = 'root', $gname = 'root') {
		if(!file_exists($file)) throw new \ErrorException('file not found. '.$file);
		$block = [
			'file' => $file,
			'size' => filesize($file),
			'name' => $name ? $name : basename($file),
			'permis' => $permis,
			'uid' => $uid,
			'gid' => $gid,
			'uname' => $uname,
			'gname' => $gname
		];
		$this->addBlock($block);
	}

	public function addString($string, $name, $permis = 0644, $uid = 0, $gid = 0, $uname = 'root', $gname = 'root') {
		$block = [
			'file' => null,
			'body' => $string,
			'size' => strlen($string),
			'name' => $name,
			'permis' => $permis,
			'uid' => $uid,
			'gid' => $gid,
			'uname' => $uname,
			'gname' => $gname
		];
		$this->addBlock($block);
	}

	private function makeHeader($block) {
		// posix
		$header = sprintf(
			"%99s\0%07o\0%07o\0%07o\0%011o\0%011o\0",
			str_pad($block['name'], 99, "\0"), $block['permis'], $block['uid'], $block['gid'], $block['size'], $block['file'] ? filemtime($block['file']) : time()
		);

		$offset = strlen($header);
		$header .= sprintf(
			"        0%99s\0",
			str_repeat("\0", 99) // link not implemented
		);

		// ustar
		$header .= sprintf(
			"ustar  \0%31s\0%31s\0%7s\0%7s\0%154s\0", // device major, minor number, filename prefix not implemented
			str_pad($block['uname'], 31, "\0"), str_pad($block['gname'], 31, "\0"),
			str_repeat("\0", 7), str_repeat("\0", 7), str_repeat("\0", 154)
		).str_repeat("\0", 12); // I don't know

		$sum = 0;
		$len = strlen($header);
		for($i = 0; $i < $len; $i ++) {
			$sum += ord($header[$i]);
		}
		$header = substr_replace($header, sprintf("%06o\0", $sum), $offset, 7);
		return $header;
	}

	private function fileCopy($tfp, $file) {
		$fp = fopen($file, 'r');
		while(!feof($fp)) {
			$this->f['write']($tfp, fgets($fp, 8192));
		}
		fclose($fp);
	}

	private function set($filename, $type, $output) {
		if($type == self::DETECT) {
			switch(strtolower(preg_replace('~^.*\.~', '', $filename))) {
				case 'gz':
					$type = self::GZ;
					break;
				case 'bz2':
					$type = self::BZ;
					break;
				default:
					$type = self::NONE;
					break;
			}
		}
		switch($output) {
			case 'file':
				switch($type) {
					case self::NONE;
						$this->f = [
							'open' => 'fopen',
							'write' => 'fwrite',
							'close' => 'fclose',
						];
						break;
					case self::GZ;
						$this->f = [
							'open' => 'gzopen',
							'write' => 'gzwrite',
							'close' => 'gzclose',
						];
						break;
					case self::BZ;
						$this->f = [
							'open' => 'bzopen',
							'write' => 'bzwrite',
							'close' => 'bzclose',
						];
						break;
				}
				break;
			case 'stream':
				$this->f = [
					'open' => 'fopen',
					'write' => 'fwrite',
					'close' => 'fclose',
				];
				switch($type) {
					case self::NONE;
						$this->f['type'] = 'tar';
						break;
					case self::GZ;
						$this->f['type'] = 'tar+gzip';
						break;
					case self::BZ;
						$this->f['type'] = 'tar+bzip2';
						break;
				}
				break;
		}

	}

	private function write($fp) {
		foreach($this->headers as $block) {
			$this->f['write']($fp, $this->makeHeader($block));
			if($block['file']) $this->fileCopy($fp, $block['file']);
			else $this->f['write']($fp, $block['body']);
			$pad = $block['size'] % 512;
			if($pad) $this->f['write']($fp, str_repeat("\0", 512 - $pad));
		}
		$this->f['write']($fp, str_repeat("\0", 8192));
		$this->f['close']($fp);
	}

	public function save($filename, $type = self::DETECT) {
		$this->set($filename, $type, 'file');
		$fp = $this->f['open']($filename, 'w');
		$this->write($fp);
	}

	public function stream($filename, $type = self::DETECT) {
		if(!$filename && $type == self::DETECT) throw new \ErrorException('no name stream must set type', E_USER_ERROR);
		$this->set($filename, $type, 'stream');
		$fp = $this->f['open']('php://output', 'w');
		switch($type) {
			case self::GZ:
				throw new \ErrorException('gzip stream not supported', E_USER_ERROR);
			case self::BZ:
				stream_filter_append($fp, 'bzip2.compress');
				break;
		}
		header('Content-Type: application/'.$this->f['type']);
		if($filename) header('Content-Disposition: attachment; filename='.basename($filename));
		$this->write($fp);
	}
}
