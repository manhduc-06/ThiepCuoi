<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Photo_model extends CI_Model
{
public function __construct()
{
parent::__construct();
$this->load->library('imageproc', array(
'medium_px' => (int) $this->config->item('photo_medium_px'),
'thumb_px' => (int) $this->config->item('photo_thumb_px'),
'quality' => (int) $this->config->item('photo_jpeg_quality'),
'allowed' => (array) $this->config->item('photo_allowed_mime'),
));
$this->load->library('quota');
}





private function image_lock()
{
$_vshp9sf = @fopen(FCPATH . 'database/.imageproc.lock', 'c');
if (!$_vshp9sf) {
log_message('error', 'Photo_model: không mở được database/.imageproc.lock');
return fopen('php://memory', 'r'); 
}
$_vncczwf = microtime(TRUE) + 60;
while (!flock($_vshp9sf, LOCK_EX | LOCK_NB)) {
if (microtime(TRUE) >= $_vncczwf) {
fclose($_vshp9sf);
json_out(array('ok' => FALSE, 'busy' => TRUE,
'error' => __('Máy đang bận xử lý ảnh, bạn thử lại sau ít phút.')), 503);
$this->output->set_header('Retry-After: 30');
$this->output->_display();
exit;
}
usleep(200000);
}
return $_vshp9sf;
}
private function dir_for($_v2oyoc1)
{
return FCPATH . 'uploads/photos/' . substr($_v2oyoc1, 0, 2);
}




public function add_from_file($_v4btdf0, $_vjn2aow, $_v93jh97, array $_vcn1bxv = array())
{
$_vmrprs8 = isset($_vcn1bxv['source']) ? $_vcn1bxv['source'] : 'owner';

$_vxi7nk6 = (int) $this->config->item($_vmrprs8 === 'guest' ? 'photo_guest_max_mp' : 'photo_owner_max_mp');
$_vx804ln = $this->imageproc->inspect($_v4btdf0, ($_vxi7nk6 > 0 ? $_vxi7nk6 : 120) * 1000000);
if (!is_array($_vx804ln)) {
return $_vx804ln;
}

$_vx78e1e = $this->quota->check((int) @filesize($_v4btdf0), $_vmrprs8 === 'guest');
if ($_vx78e1e !== NULL) {
return $_vx78e1e;
}
$_v8jiw6p = random_key(16);
$_vhxtulq = $this->dir_for($_v8jiw6p);

$_vj6vncy = $this->image_lock();


$_vgyt4qs = $this->quota->begin();
$_vx78e1e = $_vgyt4qs === NULL ? NULL : $this->quota->over($_vgyt4qs, (int) @filesize($_v4btdf0), $_vmrprs8 === 'guest');
$_v1qf2vi = $_vx78e1e !== NULL ? $_vx78e1e : $this->imageproc->store($_v4btdf0, $_vhxtulq, $_v8jiw6p, $_vx804ln, $_vmrprs8 === 'guest');
$_v29srxg = 0;
if (is_array($_v1qf2vi) && $_vgyt4qs !== NULL) {
$_v29srxg = Quota::files_bytes($this->variant_paths($_vhxtulq, $_v8jiw6p, $_v1qf2vi['ext']));
$_vx78e1e = $this->quota->over($_vgyt4qs, $_v29srxg, $_vmrprs8 === 'guest');
if ($_vx78e1e !== NULL) {
$this->imageproc->remove($_vhxtulq, $_v8jiw6p, $_v1qf2vi['ext']);
$_v1qf2vi = $_vx78e1e;
} else {
$this->quota->add($_v29srxg);
}
}
$this->quota->end();
@flock($_vj6vncy, LOCK_UN);
fclose($_vj6vncy);
if (!is_array($_v1qf2vi)) {
return $_v1qf2vi;
}
$_vl1z5ar = (int) $this->db->query('SELECT COALESCE(MAX(sort_order), 0) AS m FROM photos WHERE album_id = ?', array((int) $_v93jh97))->row()->m;
$_vqe7z66 = array(
'album_id' => (int) $_v93jh97,
'file_key' => $_v8jiw6p,
'ext' => $_v1qf2vi['ext'],
'orig_name' => mb_substr((string) $_vjn2aow, 0, 200),
'width' => $_v1qf2vi['width'],
'height' => $_v1qf2vi['height'],
'size_bytes' => $_v1qf2vi['size_bytes'],
'taken_at' => $_v1qf2vi['taken_at'],
'status' => isset($_vcn1bxv['status']) ? $_vcn1bxv['status'] : 'approved',
'source' => $_vmrprs8,
'guest_name' => isset($_vcn1bxv['guest_name']) ? $_vcn1bxv['guest_name'] : NULL,
'guest_message' => isset($_vcn1bxv['guest_message']) ? $_vcn1bxv['guest_message'] : NULL,
'uploader_ip' => $_vmrprs8 === 'guest' ? client_ip() : NULL,
'sort_order' => $_vl1z5ar + 1,
'created_at' => now_str(),
);
if (!$this->db->insert('photos', $_vqe7z66)) {
$this->imageproc->remove($_vhxtulq, $_v8jiw6p, $_v1qf2vi['ext']);
$this->quota->add(-$_v29srxg);
return __('Không ghi được vào cơ sở dữ liệu.');
}
$_vqe7z66['id'] = (int) $this->db->insert_id();
return $_vqe7z66;
}
public function find($_v8755kj)
{
return $this->db->get_where('photos', array('id' => (int) $_v8755kj))->row_array();
}

public function find_by_key($file_key)
{
if (!preg_match('/^[0-9a-f]{32}$/', (string) $file_key)) {
return NULL;
}
return $this->db->get_where('photos', array('file_key' => (string) $file_key))->row_array();
}

public function by_album($_vihremc, $_vcvnbte = 'approved')
{
$this->db->where('album_id', (int) $_vihremc);
if ($_vcvnbte === NULL) {
$this->db->where('status <>', 'rejected');
} else {
$this->db->where('status', $_vcvnbte);
}
return $this->db->order_by('sort_order ASC, id ASC')->get('photos')->result_array();
}
public function pending()
{
return $this->db->query("SELECT p.*, a.title AS album_title FROM photos p
			JOIN albums a ON a.id = p.album_id WHERE p.status = 'pending' ORDER BY p.id")->result_array();
}
public function set_status(array $_vuh0tjz, $_vegmmy2)
{
if (!$_vuh0tjz || !in_array($_vegmmy2, array('approved', 'pending', 'rejected'), TRUE)) {
return 0;
}
$this->db->where_in('id', array_map('intval', $_vuh0tjz))->update('photos', array('status' => $_vegmmy2));
return $this->db->affected_rows();
}
public function update_caption($_vieokmz, $_v7oj115)
{
return $this->db->update('photos', array('caption' => mb_substr(trim((string) $_v7oj115), 0, 500)), array('id' => (int) $_vieokmz));
}
public function move(array $_v0y7n8v, $_v6faupp)
{
$this->db->where_in('id', array_map('intval', $_v0y7n8v))->update('photos', array('album_id' => (int) $_v6faupp));
return $this->db->affected_rows();
}
public function reorder($_vxab9ih, array $_vgny4tq)
{
$this->db->trans_start();
foreach (array_values($_vgny4tq) as $_v04p9l0 => $_vyjwyy5) {
$this->db->update('photos', array('sort_order' => $_v04p9l0 + 1), array('id' => (int) $_vyjwyy5, 'album_id' => (int) $_vxab9ih));
}
$this->db->trans_complete();
return $this->db->trans_status();
}

public function delete($_vjyj7rs)
{
$_v5cv6v6 = $this->find($_vjyj7rs);
if (!$_v5cv6v6) {
return FALSE;
}
$this->db->delete('photos', array('id' => (int) $_vjyj7rs));
$this->db->update('albums', array('cover_photo_id' => NULL), array('cover_photo_id' => (int) $_vjyj7rs));
if ((int) setting('hero_photo_id') === (int) $_vjyj7rs) {
$this->settings_model->set_many(array('hero_photo_id' => ''));
}
$_vhbhqxd = $this->dir_for($_v5cv6v6['file_key']);
$_v3ycawt = Quota::files_bytes($this->variant_paths($_vhbhqxd, $_v5cv6v6['file_key'], $_v5cv6v6['ext']));
$this->imageproc->remove($_vhbhqxd, $_v5cv6v6['file_key'], $_v5cv6v6['ext']);
$this->quota->add(-$_v3ycawt);
return TRUE;
}

private function variant_paths($_vw5aq4q, $_vot0vti, $_v0zec6x)
{
return array($_vw5aq4q . '/' . $_vot0vti . '_o.' . $_v0zec6x, $_vw5aq4q . '/' . $_vot0vti . '_m.jpg',
$_vw5aq4q . '/' . $_vot0vti . '_s.jpg', $_vw5aq4q . '/' . $_vot0vti . '_t.jpg');
}

public function stats()
{
return $this->db->query("SELECT
				COUNT(CASE WHEN status = 'approved' AND album_id > 0 THEN 1 END) AS approved,
				COUNT(CASE WHEN album_id = 0 THEN 1 END) AS deco,
				COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
				COUNT(CASE WHEN source = 'guest' AND status <> 'rejected' THEN 1 END) AS from_guests,
				COALESCE(SUM(size_bytes), 0) AS bytes
			FROM photos")->row_array();
}





public function clean_orphans($_vd38tqy = 3600, $_vx7jrgm = 2.0)
{
$_vt5ie3a = microtime(TRUE);
$_vnst4m3 = time() - (int) $_vd38tqy;
$_vdcvl5g = array('deleted' => 0, 'bytes' => 0, 'left' => FALSE);
$_v12lrn0 = FCPATH . 'uploads/photos';
foreach ((array) glob($_v12lrn0 . '/[0-9a-f][0-9a-f]', GLOB_ONLYDIR) as $_v9beygm) {
if (microtime(TRUE) - $_vt5ie3a > $_vx7jrgm) {
$_vdcvl5g['left'] = TRUE;
break;
}
$_vz1obt1 = array();
foreach ((array) scandir($_v9beygm) as $_vht477e) {
if (preg_match('/^([0-9a-f]{32})_[omst]\.[a-z0-9]{2,5}$/', $_vht477e, $_v1p45bd) && @filemtime($_v9beygm . '/' . $_vht477e) < $_vnst4m3) {
$_vz1obt1[$_v1p45bd[1]][] = $_vht477e;
}
}
if (!$_vz1obt1) {
continue;
}
$_vz7nfm6 = array();
foreach (array_chunk(array_keys($_vz1obt1), 400) as $_vt195q2) {
foreach ($this->db->select('file_key')->where_in('file_key', $_vt195q2)->get('photos')->result_array() as $_vnn7ium) {
$_vz7nfm6[$_vnn7ium['file_key']] = TRUE;
}
}
foreach ($_vz1obt1 as $_v6u9a64 => $_vmyde0s) {
if (isset($_vz7nfm6[$_v6u9a64])) {
continue;
}
foreach ($_vmyde0s as $_vht477e) {
$_vlcmlyq = (int) @filesize($_v9beygm . '/' . $_vht477e);
if (@unlink($_v9beygm . '/' . $_vht477e)) {
$_vdcvl5g['deleted']++;
$_vdcvl5g['bytes'] += $_vlcmlyq;
}
}
}
}
if ($_vdcvl5g['bytes'] > 0) {

$this->load->library('quota');
$this->quota->add(-$_vdcvl5g['bytes']);
}
return $_vdcvl5g;
}

public $small_report = array();

public function missing_small()
{
$_v32mv6i = array();
foreach ($this->db->select('file_key, ext')->order_by('id', 'DESC')->get('photos')->result_array() as $_vfyexy8) {
if (!is_file(FCPATH . photo_rel_path($_vfyexy8['file_key'], 's'))) {
$_v32mv6i[] = $_vfyexy8;
}
}
return $_v32mv6i;
}







public function backfill_small($_vrro2i2 = 3.0, $_vl87i8e = NULL)
{
$_vlz462o = FCPATH . 'database/.small_done';
$this->small_report = array('made' => 0, 'errors' => array(), 'left' => FALSE, 'done' => TRUE);
if (is_file($_vlz462o)) {
return -1;
}
$_vehc39i = microtime(TRUE);
$_vp1c1n5 = 0;
$_vorloaf = FALSE;
$_v8q2n82 = FALSE;
$_v9xoay5 = array();
$_vdf1ii0 = $this->missing_small();
$_v8a4xj7 = count($_vdf1ii0);
foreach ($_vdf1ii0 as $_vxorx7s => $_v2v97w8) {
if (microtime(TRUE) - $_vehc39i > $_vrro2i2) {
$_vorloaf = TRUE;
break;
}
$_vuns1ew = $this->imageproc->make_small($this->dir_for($_v2v97w8['file_key']), $_v2v97w8['file_key'], $_v2v97w8['ext']);
if ($_vuns1ew === TRUE) {
$_vp1c1n5++;
} elseif ($_vuns1ew === NULL) {
$_v9xoay5[$_v2v97w8['file_key']] = 'mất cả bản _m lẫn bản gốc';
} else {
$_v9xoay5[$_v2v97w8['file_key']] = 'ảnh hỏng, không đọc được';
$_v8q2n82 = TRUE;
}
if ($_vl87i8e) {
call_user_func($_vl87i8e, $_vxorx7s + 1, $_v8a4xj7);
}
}
$_v5wzc5w = FCPATH . 'database/.small_errors';
if ($_v9xoay5) {
$_vv5oixa = array();
foreach ($_v9xoay5 as $_vtvrfb9 => $_vtnehxg) {
$_vv5oixa[] = $_vtvrfb9 . "\t" . $_vtnehxg;
}
@file_put_contents($_v5wzc5w, implode("\n", $_vv5oixa) . "\n", LOCK_EX);
} elseif (!$_vorloaf && is_file($_v5wzc5w)) {
@unlink($_v5wzc5w);
}
$_vctygyx = !$_vorloaf && !$_v8q2n82;
if ($_vctygyx) {
@touch($_vlz462o);
}
$this->small_report = array('made' => $_vp1c1n5, 'errors' => $_v9xoay5, 'left' => $_vorloaf, 'done' => $_vctygyx);
return $_vp1c1n5;
}
public function original_path($_vd5ajyo)
{
return FCPATH . photo_rel_path($_vd5ajyo['file_key'], 'o', $_vd5ajyo['ext']);
}
}