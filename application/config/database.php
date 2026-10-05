<?php

defined('BASEPATH') OR exit('No direct script access allowed');


$_db_path = FCPATH . 'database/anhcuoi.db';
$_db_override = getenv('ANHCUOI_DB');
if ($_db_override !== false && $_db_override !== '' && strpos($_db_override, '/') !== false) {
$_db_path = $_db_override;
}
$active_group = 'default';
$query_builder = TRUE;
$db['default'] = array(
'dsn' => '',
'hostname' => '',
'username' => '',
'password' => '',
'database' => $_db_path,
'dbdriver' => 'sqlite3',
'dbprefix' => '',
'pconnect' => FALSE,
'db_debug' => (ENVIRONMENT !== 'production'),
'cache_on' => FALSE,
'cachedir' => '',
'char_set' => 'utf8',
'dbcollat' => 'utf8_general_ci',
'swap_pre' => '',
'encrypt' => FALSE,
'compress' => FALSE,
'stricton' => FALSE,
'failover' => array(),
'save_queries' => FALSE
);
unset($_db_path, $_db_override);