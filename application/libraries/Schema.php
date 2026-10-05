<?php

defined('BASEPATH') OR exit('No direct script access allowed');







class Schema
{

private $db;
public function __construct()
{
$CI =& get_instance();
$this->db = $CI->db;
}
public function ensure()
{
$files = $this->sql_files();
$stamp = md5(implode('|', array_keys($files)));
$stamp_file = FCPATH . 'database/.schema_stamp';
if (is_file($stamp_file) && trim((string) @file_get_contents($stamp_file)) === $stamp
&& $this->db->table_exists('app_migrations')) {
return;
}
$conn = $this->db->conn_id;
$conn->exec('CREATE TABLE IF NOT EXISTS app_migrations (name TEXT PRIMARY KEY, applied_at TEXT NOT NULL)');
$done = array();
$res = $conn->query('SELECT name FROM app_migrations');
while ($res && ($row = $res->fetchArray(SQLITE3_ASSOC))) {
$done[$row['name']] = TRUE;
}
foreach ($files as $name => $path) {
if (isset($done[$name])) {
continue;
}
$sql = (string) file_get_contents($path);
$conn->exec('BEGIN IMMEDIATE TRANSACTION');
if (!@$conn->exec($sql)) {
$err = $conn->lastErrorMsg();
$conn->exec('ROLLBACK');
log_message('error', 'Schema: ' . $name . ' failed: ' . $err);
show_error('Không cập nhật được cơ sở dữ liệu (' . html_escape($name) . '): ' . html_escape($err), 500);
}
$stmt = $conn->prepare('INSERT INTO app_migrations (name, applied_at) VALUES (:n, :t)');
$stmt->bindValue(':n', $name, SQLITE3_TEXT);
$stmt->bindValue(':t', date('Y-m-d H:i:s'), SQLITE3_TEXT);
$stmt->execute();
$conn->exec('COMMIT');
}
@file_put_contents($stamp_file, $stamp, LOCK_EX);
}

private function sql_files()
{
$out = array();
foreach ((array) glob(APPPATH . 'sql/*.sql') as $path) {
$out[basename($path)] = $path;
}
ksort($out, SORT_STRING);
return $out;
}
}