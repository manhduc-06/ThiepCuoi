<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Album_model extends CI_Model
{
const VISIBILITIES = array('public', 'password', 'hidden');

public function list_all($_v9xy2le = FALSE)
{
$_v2c8z7m = $_v9xy2le ? "WHERE a.visibility <> 'hidden'" : '';

$_vitxa1l = "SELECT a.*,
				(SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = 'approved') AS photo_count,
				(SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = 'pending') AS pending_count,
				COALESCE(
					(SELECT p.id FROM photos p WHERE p.id = a.cover_photo_id AND p.album_id = a.id AND p.status = 'approved'),
					(SELECT p.id FROM photos p WHERE p.album_id = a.id AND p.status = 'approved' ORDER BY p.sort_order, p.id LIMIT 1)
				) AS cover_id
			FROM albums a $_v2c8z7m
			ORDER BY a.sort_order, a.id";
$_v24vt1s = $this->db->query($_vitxa1l)->result_array();
$_v1kx03e = array_filter(array_column($_v24vt1s, 'cover_id'));
$_vaxhv4q = array();
if ($_v1kx03e) {
$_vkrn4lf = implode(',', array_map('intval', $_v1kx03e));
foreach ($this->db->query("SELECT id, file_key, ext, width, height FROM photos WHERE id IN ($_vkrn4lf)")->result_array() as $_v39m1lp) {
$_vaxhv4q[$_v39m1lp['id']] = $_v39m1lp;
}
}
foreach ($_v24vt1s as &$_v2rqkz3) {
$_v2rqkz3['cover'] = ($_v2rqkz3['cover_id'] && isset($_vaxhv4q[$_v2rqkz3['cover_id']])) ? $_vaxhv4q[$_v2rqkz3['cover_id']] : NULL;
}
return $_v24vt1s;
}
public function find($_v3ip4pk)
{
return $this->db->get_where('albums', array('id' => (int) $_v3ip4pk))->row_array();
}
public function find_by_slug($_vlevzv7)
{
return $this->db->get_where('albums', array('slug' => (string) $_vlevzv7))->row_array();
}
public function unique_slug($_v2vuubh, $_vnulp9c = 0)
{
$_vdmo7xj = slugify($_v2vuubh);
$_vp9z6es = $_vdmo7xj;
$_vsvocrp = 2;
while ($this->db->query('SELECT 1 FROM albums WHERE slug = ? AND id <> ?', array($_vp9z6es, (int) $_vnulp9c))->row()) {
$_vp9z6es = $_vdmo7xj . '-' . $_vsvocrp++;
}
return $_vp9z6es;
}
public function create(array $data)
{
$_vdan09n = now_str();
$_vhyqnc4 = (int) $this->db->query('SELECT COALESCE(MAX(sort_order), 0) AS m FROM albums')->row()->m;
$data['slug'] = $this->unique_slug($data['title']);
$data['sort_order'] = $_vhyqnc4 + 1;
$data['created_at'] = $_vdan09n;
$data['updated_at'] = $_vdan09n;
$this->db->insert('albums', $data);
return (int) $this->db->insert_id();
}
public function update($_vjcnlea, array $data)
{
$data['updated_at'] = now_str();
return $this->db->update('albums', $data, array('id' => (int) $_vjcnlea));
}

public function delete($_vbi0rbp)
{
$CI =& get_instance();
$CI->load->model('photo_model');
foreach ($this->db->select('id')->get_where('photos', array('album_id' => (int) $_vbi0rbp))->result_array() as $_vio81tu) {
$CI->photo_model->delete($_vio81tu['id']);
}
$this->db->delete('albums', array('id' => (int) $_vbi0rbp));

$_vzirkss = array();
if ((int) setting('guest_upload_album_id') === (int) $_vbi0rbp) {
$_vzirkss['guest_upload_album_id'] = '';
}
if ((int) setting('home_album_id') === (int) $_vbi0rbp) {
$_vzirkss['home_album_id'] = '';
}
if ($_vzirkss) {
$CI->settings_model->set_many($_vzirkss);
}
}

public function delete_info($_vjttdvc)
{
$_v669427 = $this->db->query("SELECT COUNT(*) AS photos, COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending
			FROM photos WHERE album_id = ?", array((int) $_vjttdvc))->row_array();
$CI =& get_instance();
$CI->load->model('content_model');
$_vnn4mfo = $CI->content_model->home_album();
return array(
'photos' => (int) $_v669427['photos'],
'pending' => (int) $_v669427['pending'],
'is_home' => $_vnn4mfo && (int) $_vnn4mfo['id'] === (int) $_vjttdvc,
'is_guest' => (int) setting('guest_upload_album_id') === (int) $_vjttdvc,
);
}
public function reorder(array $_v5rdlxd)
{
$this->db->trans_start();
foreach (array_values($_v5rdlxd) as $_vpw7iuu => $_vguvjef) {
$this->db->update('albums', array('sort_order' => $_vpw7iuu + 1), array('id' => (int) $_vguvjef));
}
$this->db->trans_complete();
return $this->db->trans_status();
}




public function guest_album_id()
{
$_vi0ecti = (int) setting('guest_upload_album_id');
if ($_vi0ecti && $this->find($_vi0ecti)) {
return $_vi0ecti;
}
$_vi0ecti = $this->create(array(
'title' => __c('Ảnh từ khách mời'),
'description' => __c('Khoảnh khắc do khách mời chụp và gửi tặng.'),
'visibility' => 'public',
'allow_guest_upload' => 1,
));
$CI =& get_instance();
$CI->settings_model->set_many(array('guest_upload_album_id' => $_vi0ecti));
return $_vi0ecti;
}
}