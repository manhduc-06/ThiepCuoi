<?php
 defined('BASEPATH') OR exit('No direct script access allowed');



if ($part === 'cover'): ?>
<span class="bk" aria-hidden="true">
  <span class="bk-page"><span class="bk-page-greet"><?= e($cv['greet']) ?></span><span class="bk-page-name"><?= e($cv['guest']) ?></span><span class="bk-page-mono"><?= e($cv['m1']) ?> &amp; <?= e($cv['m2']) ?></span></span>
  <span class="bk-cover"><span class="bk-front"><span class="bk-frame"></span><span class="bk-title ic-foil-text"><?= e($cv['greet']) ?></span><span class="bk-mono ic-foil-text"><?= e($cv['m1']) ?><i>&amp;</i><?= e($cv['m2']) ?></span></span><span class="bk-back"></span></span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="bk-turn" aria-hidden="true"></div><div class="bk-ribbon" aria-hidden="true"></div>
<?php endif;