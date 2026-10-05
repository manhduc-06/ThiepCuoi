<?php

defined('BASEPATH') OR exit('No direct script access allowed');
$route['default_controller'] = 'home';
$route['404_override'] = 'errors/not_found';
$route['translate_uri_dashes'] = FALSE;

$route['a/(:any)/unlock'] = 'home/unlock_album/$1';
$route['a/(:any)/zip'] = 'home/zip/$1';
$route['a/(:any)'] = 'home/album/$1';
$route['anh-goc/([0-9a-f]{32})\.(jpg|png|webp|gif)'] = 'home/original/$1';
$route['unlock'] = 'home/unlock_site';
$route['gui-anh'] = 'guest/index';
$route['gui-anh/upload'] = 'guest/upload';
$route['loi-chuc'] = 'guest/wish';
$route['moi/(:any)'] = 'home/invite/$1';
$route['xac-nhan'] = 'guest/rsvp';

$route['admin'] = 'admin/dashboard';
$route['admin/login'] = 'auth/login';
$route['admin/logout'] = 'auth/logout';



$route['(?i)(?!(?:admin|a|moi|xac-nhan|gui-anh|loi-chuc|unlock|setup|health|auth|home|guest|errors|index|assets|uploads)$)([a-z0-9][a-z0-9-]{0,38}[a-z0-9])'] = 'home/slug/$1';