<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

?><!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars(strip_tags($heading), ENT_QUOTES, 'UTF-8') ?></title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#faf6f1;color:#3b3030;font:16px/1.6 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;padding:16px}
main{max-width:560px;background:#fff;border:1px solid #eadfd6;border-radius:14px;padding:28px}
h1{font:600 1.5rem Georgia,"Times New Roman",serif;margin:0 0 .5rem}
a{color:#9a6a73;display:inline-flex;align-items:center;min-height:44px}
</style></head>
<body><main><h1><?= $heading ?></h1><div><?= $message ?></div><p><a href="/">Xem trang cưới →</a></p></main></body></html>