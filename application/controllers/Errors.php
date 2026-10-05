<?php

defined('BASEPATH') OR exit('No direct script access allowed');
class Errors extends MY_Controller
{
protected $allow_before_setup = TRUE;
public function not_found()
{
$this->output->set_status_header(404);
$this->render('public/not_found', array('title' => 'Không tìm thấy trang'));
}
}