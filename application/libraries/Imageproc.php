<?php











class Imageproc
{
public $medium_px = 2048;
public $small_px = 1280;
public $thumb_px = 640;
public $quality = 84;

public $quality_by_size = array('m' => 82, 's' => 80, 't' => 78);
public $allowed = array(
'image/jpeg' => 'jpg',
'image/png' => 'png',
'image/webp' => 'webp',
'image/gif' => 'gif',
);
public function __construct($params = array())
{
foreach ((array) $params as $k => $v) {
if (property_exists($this, $k)) {
$this->$k = $v;
}
}
}

public $max_pixels = 120000000;







public function inspect($path, $max_pixels = NULL)
{
$bad = __('File này không phải ảnh hoặc đã hỏng, hãy chọn ảnh JPG/PNG khác.');
if (!is_file($path) || filesize($path) === 0) {
return __('File rỗng hoặc không đọc được.');
}
$info = @getimagesize($path);
if (!$info || empty($info['mime'])) {
$head = (string) @file_get_contents($path, FALSE, NULL, 0, 16);
if (strpos($head, 'ftypheic') !== FALSE || strpos($head, 'ftypheix') !== FALSE || strpos($head, 'ftypmif1') !== FALSE) {
return __('Ảnh HEIC chưa được hỗ trợ. Trên iPhone chọn Cài đặt → Camera → Định dạng → "Tương thích nhất", hoặc gửi ảnh qua trình duyệt Safari (tự chuyển sang JPEG).');
}
return $bad;
}
$mime = $info['mime'];
if (!isset($this->allowed[$mime])) {
return __('Định dạng {mime} chưa hỗ trợ (chỉ JPG, PNG, WEBP, GIF).', array('mime' => $mime));
}
if ((int) $info[0] < 1 || (int) $info[1] < 1) {
return $bad;
}
$cap = $max_pixels === NULL ? (int) $this->max_pixels : (int) $max_pixels;
$pixels = (float) $info[0] * (float) $info[1];



$bytes = (float) filesize($path);
if ($pixels > 1000000 && ($bytes < 1024
|| ($pixels > $cap && ($mime === 'image/gif' || $mime === 'image/jpeg') && $bytes / $pixels < 0.01))) {
return $bad;
}
if ($pixels > $cap) {

return __('Ảnh quá lớn (tối đa {max} megapixel, ảnh này {mp} megapixel). Hãy thu nhỏ ảnh rồi gửi lại.', array(
'max' => round($cap / 1000000), 'mp' => number_format(ceil($pixels / 100000) / 10, 1, lang_cur() === 'en' ? '.' : ',', '.')));
}
return array('mime' => $mime, 'ext' => $this->allowed[$mime], 'width' => (int) $info[0], 'height' => (int) $info[1]);
}




public function store($src_path, $dir, $file_key, array $meta, $reencode_original = FALSE)
{
if (!is_dir($dir) && !@mkdir($dir, 0755, TRUE)) {
return __('Không tạo được thư mục lưu ảnh.');
}
$taken_at = $this->exif_date($src_path, $meta['mime']);
$img = NULL;
try {
$img = $this->load($src_path, $meta['mime']);
if (!$img) {
return __('File này không phải ảnh hoặc đã hỏng, hãy chọn ảnh JPG/PNG khác.');
}
$img = $this->apply_orientation($img, $src_path, $meta['mime']);
$w = imagesx($img);
$h = imagesy($img);
$ext = $meta['ext'];
$orig = $dir . '/' . $file_key . '_o.';
if ($reencode_original) {
$ext = 'jpg';

if ($meta['mime'] === 'image/jpeg') {
$ok = $this->save_jpeg($img, $orig . $ext, 92);
} else {
$flat = $this->flatten($img);
$ok = $this->save_jpeg($flat, $orig . $ext, 92);
$this->free($flat);
}
} elseif ($meta['mime'] === 'image/jpeg') {
// Bản gốc của chủ nhà: bỏ EXIF/XMP (vị trí GPS, số máy...). Ảnh có cờ xoay thì lưu lại từ bản đã xoay đúng
// chiều (mất EXIF thì trình duyệt không còn biết phải xoay); còn lại cắt metadata, giữ nguyên dữ liệu ảnh.
$ok = $this->exif_orientation($src_path, $meta['mime']) !== 1
? $this->save_jpeg($img, $orig . $ext, 95)
: self::strip_jpeg_metadata($src_path, $orig . $ext);
} else {
$ok = @copy($src_path, $orig . $ext);
}
if (!$ok) {
return __('Không lưu được bản gốc.');
}
@chmod($orig . $ext, 0644);

foreach (array('m' => $this->medium_px, 's' => $this->small_px, 't' => $this->thumb_px) as $size => $px) {
$scaled = $this->scale($img, $px);
$this->free($img);
$img = $scaled;
$scaled = NULL;
$q = isset($this->quality_by_size[$size]) ? $this->quality_by_size[$size] : $this->quality;
if (!$this->save_jpeg($img, $dir . '/' . $file_key . '_' . $size . '.jpg', $q)) {
$this->remove($dir, $file_key, $ext);
return __('Không tạo được ảnh thu nhỏ.');
}
}
return array(
'width' => $w,
'height' => $h,
'ext' => $ext,
'taken_at' => $taken_at,
'size_bytes' => (int) filesize($orig . $ext),
);
} finally {

$this->free($img);
gc_collect_cycles();
if (function_exists('gc_mem_caches')) {
gc_mem_caches();
}
}
}




/**
 * Chép file JPEG, bỏ các đoạn APP1 (EXIF/XMP), APP13 (IPTC/Photoshop) và COM. Giữ JFIF, ICC (APP2), Adobe
 * (APP14), bảng lượng tử, Huffman và toàn bộ dữ liệu ảnh từ SOS trở đi nên không giảm chất lượng.
 * File không đúng cấu trúc thì trả FALSE (người gọi báo lỗi lưu bản gốc).
 */
public static function strip_jpeg_metadata($src, $dest)
{
$data = @file_get_contents($src);
if ($data === FALSE || strlen($data) < 4 || substr($data, 0, 2) !== "\xFF\xD8") {
return FALSE;
}
$out = "\xFF\xD8";
$pos = 2;
$len = strlen($data);
while ($pos < $len) {
if ($data[$pos] !== "\xFF") {
return FALSE;
}
while ($pos < $len && $data[$pos] === "\xFF") {
$pos++;
}
if ($pos >= $len) {
return FALSE;
}
$marker = ord($data[$pos]);
$pos++;
if ($marker === 0xD9) {
$out .= "\xFF\xD9";
break;
}
if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
$out .= "\xFF" . chr($marker);
continue;
}
if ($pos + 2 > $len) {
return FALSE;
}
$seg_len = (ord($data[$pos]) << 8) | ord($data[$pos + 1]);
if ($seg_len < 2 || $pos + $seg_len > $len) {
return FALSE;
}
if ($marker === 0xDA) {
$out .= "\xFF\xDA" . substr($data, $pos);
break;
}
if ($marker !== 0xE1 && $marker !== 0xED && $marker !== 0xFE) {
$out .= "\xFF" . chr($marker) . substr($data, $pos, $seg_len);
}
$pos += $seg_len;
}
return @file_put_contents($dest, $out, LOCK_EX) !== FALSE;
}

private function free(&$img)
{
if (is_resource($img)) {
@imagedestroy($img);
}
$img = NULL;
}
public function remove($dir, $file_key, $ext)
{
foreach (array($file_key . '_o.' . $ext, $file_key . '_m.jpg', $file_key . '_s.jpg', $file_key . '_t.jpg') as $f) {
if (is_file($dir . '/' . $f)) {
@unlink($dir . '/' . $f);
}
}
}







public function make_small($dir, $file_key, $ext = NULL)
{
$s = $dir . '/' . $file_key . '_s.jpg';
if (is_file($s)) {
return TRUE;
}
$sources = array();
$m = $dir . '/' . $file_key . '_m.jpg';
if (is_file($m)) {
$sources[] = $m;
}
$o = array();
if ($ext !== NULL && $ext !== '') {
$o = array($dir . '/' . $file_key . '_o.' . $ext);
} else {
$o = (array) glob($dir . '/' . $file_key . '_o.*');
}
foreach ($o as $f) {
if (is_file($f)) {
$sources[] = $f;
}
}
if (!$sources) {
return NULL;
}
foreach ($sources as $src) {
if ($this->small_from($src, $s, $src !== $m)) {
return TRUE;
}
}
return FALSE;
}

private function small_from($src, $dest, $is_original)
{
$img = NULL;
$tmp = $dest . '.' . getmypid() . '.tmp';
try {
if ($is_original) {
$info = @getimagesize($src);
$mime = $info && !empty($info['mime']) ? $info['mime'] : '';
if (!isset($this->allowed[$mime]) || (float) $info[0] * (float) $info[1] > $this->max_pixels) {
return FALSE;
}
$img = $this->load($src, $mime);
if ($img) {
$img = $this->apply_orientation($img, $src, $mime);
}
} else {
$img = @imagecreatefromjpeg($src);
}
if (!$img) {
return FALSE;
}
$scaled = $this->scale($img, $this->small_px);
$this->free($img);
$img = $scaled;
$scaled = NULL;
if (!$this->save_jpeg($img, $tmp, $this->quality_by_size['s'])) {
return FALSE;
}
if (!@rename($tmp, $dest)) {
return FALSE;
}
return TRUE;
} finally {
$this->free($img);
if (is_file($tmp)) {
@unlink($tmp);
}
if ($is_original) {
gc_collect_cycles();
if (function_exists('gc_mem_caches')) {
gc_mem_caches();
}
}
}
}
private function load($path, $mime)
{

@ini_set('memory_limit', '768M');
switch ($mime) {
case 'image/jpeg': return @imagecreatefromjpeg($path);
case 'image/png': return @imagecreatefrompng($path);
case 'image/webp': return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : FALSE;
case 'image/gif': return @imagecreatefromgif($path);
}
return FALSE;
}

private function flatten($img)
{
$w = imagesx($img);
$h = imagesy($img);
$out = imagecreatetruecolor($w, $h);
imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
imagecopy($out, $img, 0, 0, 0, 0, $w, $h);
return $out;
}
private function scale($img, $max)
{
$w = imagesx($img);
$h = imagesy($img);
$ratio = min(1, $max / max($w, $h));
$nw = max(1, (int) round($w * $ratio));
$nh = max(1, (int) round($h * $ratio));
$out = imagecreatetruecolor($nw, $nh);
imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
return $out;
}
private function save_jpeg($img, $path, $quality)
{
imageinterlace($img, TRUE);
$ok = @imagejpeg($img, $path, $quality);
if ($ok) {
@chmod($path, 0644);
}
return $ok;
}

private function apply_orientation($img, $path, $mime)
{
$o = $this->exif_orientation($path, $mime);
if (in_array($o, array(2, 4, 5, 7), TRUE)) {
imageflip($img, in_array($o, array(4), TRUE) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
}
$angle = array(3 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90);
if (!isset($angle[$o])) {
return $img;
}
$rot = imagerotate($img, $angle[$o], 0);
if (!$rot) {
return $img;
}
$this->free($img); 
return $rot;
}
private function exif_orientation($path, $mime)
{
if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
return 1;
}
$exif = @exif_read_data($path, 'IFD0');
return (is_array($exif) && isset($exif['Orientation'])) ? (int) $exif['Orientation'] : 1;
}
private function exif_date($path, $mime)
{
if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
return NULL;
}
$exif = @exif_read_data($path, 'EXIF');
foreach (array('DateTimeOriginal', 'DateTimeDigitized', 'DateTime') as $k) {
if (is_array($exif) && !empty($exif[$k]) && preg_match('/^(\d{4}):(\d{2}):(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $exif[$k], $m)) {
return "$m[1]-$m[2]-$m[3] $m[4]:$m[5]:$m[6]";
}
}
return NULL;
}
}