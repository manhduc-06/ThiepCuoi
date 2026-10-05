<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Share extends Admin_Controller
{
public function index()
{
$_v3ee5w5 = rtrim(public_url(), '/');
$_v5gye5y = tunnel_config();
$this->render('admin/share', array(
'title' => __('Chia sẻ & mã QR'),
'home_url' => $_v3ee5w5 . '/',
'upload_url' => $_v3ee5w5 . '/gui-anh',
'share_text' => share_invite_text($this->settings_model->couple_title(), (string) setting('wedding_date')),
'tunnel' => array('mode' => $_v5gye5y['mode'], 'hostname' => $_v5gye5y['hostname'], 'has_token' => $_v5gye5y['token'] !== '', 'auto' => $_v5gye5y['auto']),
'is_local' => !hosted() && strpos($_v3ee5w5, 'trycloudflare.com') === FALSE && $_v5gye5y['mode'] !== 'token',
'hosted' => hosted(),
));
}
}