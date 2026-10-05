<?php

defined('BASEPATH') OR exit('No direct script access allowed');













class Ac_SQLite3 extends SQLite3
{
public function exec($query): bool
{
if (strcasecmp(trim((string) $query), 'BEGIN TRANSACTION') === 0) {
$query = 'BEGIN IMMEDIATE TRANSACTION';
}
return parent::exec($query);
}
}
class MY_Loader extends CI_Loader
{
private static $tuned = array();
public function database($params = '', $return = FALSE, $query_builder = NULL)
{
$ret = parent::database($params, $return, $query_builder);
$db = ($return === TRUE && is_object($ret)) ? $ret : NULL;
if ($db === NULL) {
$CI =& get_instance();
$db = (isset($CI->db) && is_object($CI->db)) ? $CI->db : NULL;
}


if (is_object($db) && isset($db->dbdriver) && $db->dbdriver === 'sqlite3' && empty($db->conn_id)) {
self::db_unavailable();
}
self::tune_sqlite($db);
return $ret;
}

public static function db_unavailable()
{
log_message('error', 'SQLite: không mở được CSDL (quyền file/ổ đĩa?) -> 503');
if (PHP_SAPI === 'cli') {
fwrite(STDERR, "Không mở được CSDL database/anhcuoi.db (kiểm tra quyền file / ổ đĩa).\n");
exit(1);
}
while (ob_get_level()) {
ob_end_clean();
}
header('HTTP/1.1 503 Service Unavailable', TRUE, 503);
header('Retry-After: 60');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$uri = (string) (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
if (preg_match('~(^|/|\?/?)health(/|$|\?)~', $uri)) {

header('Content-Type: application/json; charset=utf-8');
echo '{"ok":false}';
exit;
}
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array('ok' => FALSE, 'error' => 'Chưa mở được dữ liệu của trang, bạn thử lại sau ít phút nhé.'), JSON_UNESCAPED_UNICODE);
exit;
}
$heading = 'Trang đang tạm nghỉ';
$message = '<p>Chưa mở được dữ liệu của trang (ổ đĩa hoặc quyền file). Bạn thử lại sau ít phút nhé.</p>'
. '<p class="small">Chủ nhà: kiểm tra thư mục <code>database/</code> còn đọc/ghi được không (ổ đầy, USB rút ra, quyền file), rồi chạy lại chương trình.</p>'
. '<p lang="en">The page data could not be opened right now — please try again in a few minutes.</p>';
include VIEWPATH . 'errors/html/_page.php';
exit;
}
public static function tune_sqlite($db)
{
if (!is_object($db) || !isset($db->dbdriver) || $db->dbdriver !== 'sqlite3' || empty($db->conn_id)) {
return;
}
$key = spl_object_id($db);
if (isset(self::$tuned[$key])) {
return;
}
self::$tuned[$key] = TRUE;
self::use_immediate_transactions($db);
$db->simple_query('PRAGMA busy_timeout=5000');
$db->simple_query('PRAGMA synchronous=NORMAL');
$mode = @$db->conn_id->querySingle('PRAGMA journal_mode');
if (strcasecmp((string) $mode, 'wal') !== 0) {
$db->simple_query('PRAGMA journal_mode=WAL');
}
}
private static function use_immediate_transactions($db)
{
if (!($db->conn_id instanceof SQLite3) || $db->conn_id instanceof Ac_SQLite3) {
return;
}
if (!empty($db->password) || empty($db->database)) {
return;
}
try {
$fresh = new Ac_SQLite3($db->database);
} catch (Exception $e) {
return;
}
@$db->conn_id->close();
$db->conn_id = $fresh;
}
}