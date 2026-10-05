<?php

defined('BASEPATH') or exit('No direct script access allowed');


$__is_https = (
(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower($_SERVER['HTTPS']) !== 'off')
|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
);
$__host = isset($_SERVER['HTTP_HOST']) && preg_match('~^[A-Za-z0-9.\-:\[\]]+$~', $_SERVER['HTTP_HOST'])
? $_SERVER['HTTP_HOST'] : 'localhost';
$__script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
$config['base_url'] = ($__is_https ? 'https' : 'http') . '://' . $__host
. str_replace(basename($__script), '', $__script);
$config['index_page'] = '';
$config['uri_protocol'] = 'REQUEST_URI';
$config['url_suffix'] = '';
$config['language'] = 'english';
$config['charset'] = 'UTF-8';
$config['enable_hooks'] = FALSE;
$config['subclass_prefix'] = 'MY_';
$config['composer_autoload'] = FALSE;
$config['permitted_uri_chars'] = 'a-z 0-9~%.:_\-';
$config['enable_query_strings'] = FALSE;
$config['controller_trigger'] = 'c';
$config['function_trigger'] = 'm';
$config['directory_trigger'] = 'd';
$config['allow_get_array'] = TRUE;
$config['log_threshold'] = 1;
$config['log_path'] = '';
$config['log_file_extension'] = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format'] = 'Y-m-d H:i:s';
$config['error_views_path'] = '';
$config['cache_path'] = '';
$config['cache_query_string'] = FALSE;
$config['time_reference'] = 'local';


$__secret_key_dir = defined('FCPATH') ? FCPATH : (dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
$__secret_key_file = $__secret_key_dir . 'database/.secret_key';
$__secret_key = is_file($__secret_key_file) ? trim((string) @file_get_contents($__secret_key_file)) : '';
if (!preg_match('/^[a-f0-9]{64}$/i', $__secret_key)) {
$__secret_key = bin2hex(random_bytes(32));
if (@file_put_contents($__secret_key_file, $__secret_key, LOCK_EX) !== FALSE) {
@chmod($__secret_key_file, 0600);
}
}
$config['encryption_key'] = $__secret_key;

$config['sess_driver'] = 'files';
$config['sess_cookie_name'] = 'anhcuoi_session';
$config['sess_expiration'] = 60 * 60 * 24 * 14;
$config['sess_save_path'] = $__secret_key_dir . 'database/sessions';
$config['sess_match_ip'] = FALSE;
$config['sess_time_to_update'] = 300;
$config['sess_regenerate_destroy'] = FALSE;
$config['cookie_prefix'] = '';
$config['cookie_domain'] = '';
$config['cookie_path'] = '/';
$config['cookie_secure'] = $__is_https;
$config['cookie_httponly'] = TRUE;
$config['standardize_newlines'] = FALSE;
$config['global_xss_filtering'] = FALSE;
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'csrf_token';
$config['csrf_cookie_name'] = 'anhcuoi_csrf';
$config['csrf_expire'] = 60 * 60 * 24 * 14; 
$config['csrf_regenerate'] = FALSE;
$config['csrf_exclude_uris'] = array('health');
$config['compress_output'] = FALSE;
$config['rewrite_short_tags'] = FALSE;
$config['proxy_ips'] = '';
unset($__is_https, $__host, $__script, $__secret_key_dir, $__secret_key_file, $__secret_key);