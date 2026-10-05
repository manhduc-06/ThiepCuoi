<?php

defined('BASEPATH') OR exit('No direct script access allowed');








class Zipstream
{
const MAX_BYTES = 4294967295; 
const MAX_FILES = 65535;
const CHUNK = 65536; 

private $entries = array();

public function add($path, $name)
{
if (!is_file($path) || !is_readable($path)) {
return FALSE;
}
$size = (int) @filesize($path);
$mt = (int) @filemtime($path);
$t = getdate($mt > 0 ? $mt : time());
if ($t['year'] < 1980) {
$t = array('year' => 1980, 'mon' => 1, 'mday' => 1, 'hours' => 0, 'minutes' => 0, 'seconds' => 0);
}
$this->entries[] = array(
'path' => $path,
'name' => (string) $name,
'size' => $size,
'time' => ($t['hours'] << 11) | ($t['minutes'] << 5) | ($t['seconds'] >> 1),
'date' => (min(127, $t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'],
);
return TRUE;
}
public function count()
{
return count($this->entries);
}

public function length()
{
$n = 22;
foreach ($this->entries as $en) {
$n += 30 + 46 + 2 * strlen($en['name']) + $en['size'];
}
return $n;
}

public function fits()
{
return $this->count() > 0 && $this->count() < self::MAX_FILES && $this->length() < self::MAX_BYTES;
}





public function send($tick = NULL)
{
$offset = 0;
$sent = 0;
$central = '';
foreach ($this->entries as $en) {
$crc = hash_file('crc32b', $en['path']);
$crc = $crc === FALSE ? 0 : (int) hexdec($crc);
$head = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $en['time'], $en['date'], $crc, $en['size'], $en['size'],
strlen($en['name']), 0) . $en['name'];
$central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $en['time'], $en['date'], $crc,
$en['size'], $en['size'], strlen($en['name']), 0, 0, 0, 0, 0, $offset) . $en['name'];
echo $head;
$sent += strlen($head);
$left = $en['size'];
$fp = @fopen($en['path'], 'rb');
while ($left > 0) {
$buf = $fp ? fread($fp, min(self::CHUNK, $left)) : FALSE;
if ($buf === FALSE || $buf === '') {
$buf = str_repeat("\0", min(self::CHUNK, $left));
}
echo $buf;
$left -= strlen($buf);
$sent += strlen($buf);
flush();
if (connection_aborted()) {
if ($fp) {
fclose($fp);
}
return FALSE;
}
if ($tick) {
call_user_func($tick, $sent);
}
}
if ($fp) {
fclose($fp);
}
$offset += strlen($head) + $en['size'];
}
$n = count($this->entries);
echo $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $n, $n, strlen($central), $offset, 0);
flush();
return TRUE;
}
}