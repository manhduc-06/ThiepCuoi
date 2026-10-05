<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$heading = 'Có lỗi xảy ra';
$message = '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
include VIEWPATH . 'errors/html/_page.php';