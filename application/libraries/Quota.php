<?php

defined('BASEPATH') OR exit('No direct script access allowed');









class Quota
{
const RESCAN = 600;
const MB = 1048576;

private $limit;

private $lock_fp;

public function limit_bytes()
{
if ($this->limit === NULL) {
$this->limit = 0;
$f = FCPATH . '.quota';
if (is_file($f)) {
$j = json_decode((string) @file_get_contents($f), TRUE);
$mb = is_array($j) && isset($j['mb']) ? (int) $j['mb'] : 0;
$this->limit = $mb > 0 ? $mb * self::MB : 0;
}
}
return $this->limit;
}
public function enabled()
{
return $this->limit_bytes() > 0;
}

public function used_bytes()
{
if (!$this->enabled()) {
return 0;
}
return $this->update(0);
}




public function check($incoming, $guest = FALSE)
{
$limit = $this->limit_bytes();
if ($limit <= 0) {
return NULL;
}
return $this->over($this->used_bytes(), $incoming, $guest);
}







public function begin()
{
if (!$this->enabled()) {
return NULL;
}
if (!$this->lock_fp) {
$fp = @fopen(FCPATH . 'database/.quota.lock', 'c');
if ($fp && flock($fp, LOCK_EX)) {
$this->lock_fp = $fp;
} elseif ($fp) {
fclose($fp);
}
}
return $this->used_bytes();
}

public function end()
{
if ($this->lock_fp) {
flock($this->lock_fp, LOCK_UN);
fclose($this->lock_fp);
$this->lock_fp = NULL;
}
}

public function over($used, $incoming, $guest = FALSE)
{
$limit = $this->limit_bytes();
if ($limit <= 0 || (int) $used + max(0, (int) $incoming) <= $limit) {
return NULL;
}
$used = (int) $used;
$cap = self::size_text($limit);
if ($guest) {
return __('Trang cưới đã dùng hết {cap} dung lượng nên chưa nhận thêm ảnh được — bạn báo cô dâu chú rể giúp nhé.', array('cap' => $cap));
}
return __('Trang cưới đã dùng hết {cap} dung lượng (đã dùng {used}, file này {size}). Hãy xóa bớt ảnh hoặc nhạc không cần, hoặc liên hệ thiep.site để nâng hạn mức.',
array('cap' => $cap, 'used' => self::size_text($used), 'size' => self::size_text((int) $incoming)));
}

public function add($bytes)
{
if ($this->enabled() && (int) $bytes !== 0) {
$this->update((int) $bytes);
}
}

public static function files_bytes(array $paths)
{
$n = 0;
foreach ($paths as $p) {
if (is_file($p)) {
$n += (int) @filesize($p);
}
}
return $n;
}

public function summary()
{
$limit = $this->limit_bytes();
if ($limit <= 0) {
return NULL;
}
$used = $this->used_bytes();
return array(
'limit' => $limit,
'used' => $used,
'pct' => min(100, (int) floor($used * 100 / $limit)),
'limit_text' => self::size_text($limit),
'used_text' => self::size_text($used),
);
}

public static function size_text($bytes)
{
$mb = $bytes / self::MB;
if ($mb >= 1024) {
$gb = round($mb / 1024, 1);
return ($gb == floor($gb) ? (int) $gb : number_format($gb, 1, lang_cur() === 'en' ? '.' : ',', lang_cur() === 'en' ? ',' : '.')) . ' GB';
}
if ($mb >= 1) {
return (int) round($mb) . ' MB';
}
return max(0, (int) round($bytes / 1024)) . ' KB';
}




private function update($delta)
{
$file = FCPATH . 'database/.usage.json';
$fp = @fopen($file, 'c+');
if (!$fp) {
return $this->scan(); 
}
flock($fp, LOCK_EX);
$j = json_decode((string) stream_get_contents($fp), TRUE);
$fresh = is_array($j) && isset($j['bytes'], $j['at']) && time() - (int) $j['at'] < self::RESCAN && (int) $j['at'] <= time();
if ($fresh) {
$bytes = max(0, (int) $j['bytes'] + $delta);
$at = (int) $j['at'];
} else {
$bytes = $this->scan();
$at = time();
}
if ($delta !== 0 || !$fresh) {
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode(array('bytes' => $bytes, 'at' => $at)));
fflush($fp);
}
flock($fp, LOCK_UN);
fclose($fp);
return $bytes;
}

private function scan()
{
$n = 0;
foreach (array('uploads/photos', 'uploads/media') as $d) {
$dir = FCPATH . $d;
if (!is_dir($dir)) {
continue;
}
try {
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
if ($f->isFile()) {
$n += (int) $f->getSize();
}
}
} catch (Exception $e) {
log_message('error', 'Quota: không đo được ' . $d . ': ' . $e->getMessage());
}
}
return $n;
}
}