<?php

defined('BASEPATH') OR exit('No direct script access allowed');





class MY_Exceptions extends CI_Exceptions
{
public function show_404($page = '', $log_error = TRUE)
{
parent::show_404($page, FALSE);
}
}