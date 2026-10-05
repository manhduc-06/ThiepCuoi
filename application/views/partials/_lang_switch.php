<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

$ls_q = (array) $this->input->get();
$ls_cur = lang_cur();
?>
<span class="lang-switch" role="group" aria-label="<?= e(__('Ngôn ngữ')) ?>">
<?php foreach (array('vi' => 'VI', 'en' => 'EN') as $ls_k => $ls_t): $ls_q['lang'] = $ls_k; ?>
  <a href="<?= e(current_url() . '?' . http_build_query($ls_q)) ?>" hreflang="<?= $ls_k ?>" lang="<?= $ls_k ?>"<?= $ls_cur === $ls_k ? ' class="on" aria-current="true"' : '' ?>><?= $ls_t ?></a>
<?php endforeach; ?>
</span>