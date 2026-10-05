<?php
 defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/head'); ?>
<body class="bare">
<style>
.bare-lang { position: fixed; top: 12px; right: 12px; z-index: 5; }
.lang-switch { display: inline-flex; border: 1px solid var(--line, #e5ddd5); border-radius: 999px; overflow: hidden; background: var(--paper, #fff); font: 600 .78rem/1 system-ui, sans-serif; }
.lang-switch a { padding: 8px 10px; color: inherit; text-decoration: none; opacity: .65; }
.lang-switch a:hover { opacity: 1; }
.lang-switch a.on { opacity: 1; background: color-mix(in srgb, var(--accent, #b0726b) 16%, #fff); }
</style>
<?php if (empty($hide_lang)): ?><div class="bare-lang"><?php $this->load->view('partials/_lang_switch'); ?></div><?php endif; ?>
<main class="bare-card">
<?php $this->load->view($content_view); ?>
</main>
<script src="<?= asset_url('js/app.js') ?>"></script>
</body>
</html>