<?php
 defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (!empty($flash)): ?>
<div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?><button type="button" class="flash-x" aria-label="<?= e(__('Đóng')) ?>">×</button></div>
<?php endif;