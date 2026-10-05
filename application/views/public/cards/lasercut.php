<?php
 defined('BASEPATH') OR exit('No direct script access allowed');



if ($part === 'cover'): ?>
<span class="lc" aria-hidden="true">
  <span class="lc-back"></span>
  <span class="lc-insert"><span class="lc-insert-greet"><?= e($cv['greet']) ?></span><span class="lc-insert-names"><?= e($cv['groom']) ?><i>&amp;</i><?= e($cv['bride']) ?></span><?php if ($cv['day']): ?><span class="lc-insert-date"><?= e($cv['day']['d']) ?> · <?= e($cv['day']['m']) ?> · <?= e($cv['day']['y']) ?></span><?php endif; ?></span>
  <span class="lc-lace"></span>
  <span class="lc-medal"><b><?= e($cv['m1']) ?><i>&amp;</i><?= e($cv['m2']) ?></b></span>
  <span class="lc-tag"><span class="lc-pearl"></span></span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="ic-deco lc-deco" aria-hidden="true"><span class="lc-bg"></span><span class="lc-frame"></span><span class="lc-panel"></span><span class="lc-crown"></span></div>
<?php endif;