<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Wish_model extends CI_Model
{
public function add($_v1sbv9r, $_vws1ji0, $_vw8vivm)
{
$this->db->insert('wishes', array(
'name' => $_v1sbv9r,
'message' => $_vws1ji0,
'status' => $_vw8vivm,
'ip' => client_ip(),
'created_at' => now_str(),
));
return (int) $this->db->insert_id();
}
public function approved($_v7ml73v = 200)
{
return $this->db->where('status', 'approved')->order_by('id', 'DESC')->limit((int) $_v7ml73v)->get('wishes')->result_array();
}



public function all($_vdfwnpp = '')
{
if (in_array($_vdfwnpp, array('approved', 'pending', 'hidden'), TRUE)) {
$this->db->where('status', $_vdfwnpp);
}
return $this->db->order_by('COALESCE(updated_at, created_at) DESC, id DESC', '', FALSE)->get('wishes')->result_array();
}
public function count_all()
{
return (int) $this->db->count_all('wishes');
}
public function count_pending()
{
return (int) $this->db->where('status', 'pending')->count_all_results('wishes');
}
public function set_status($_vl2gsej, $_vwy5h0g)
{
if (!in_array($_vwy5h0g, array('approved', 'pending', 'hidden'), TRUE)) {
return FALSE;
}
return $this->db->update('wishes', array('status' => $_vwy5h0g), array('id' => (int) $_vl2gsej));
}
public function delete($_v87wdex)
{
return $this->db->delete('wishes', array('id' => (int) $_v87wdex));
}
}