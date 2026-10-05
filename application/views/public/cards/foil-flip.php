<?php
 defined('BASEPATH') OR exit('No direct script access allowed');



if ($part === 'cover'): ?>
<span class="ff" aria-hidden="true">
  <span class="ff-ply ff-ply-3"></span><span class="ff-ply ff-ply-2"></span><span class="ff-ply ff-ply-1"></span>
  <span class="ff-back"><span class="ff-frame"></span></span>
  <span class="ff-front ic-sheen"><span class="ff-frame"></span><span class="ff-fan"></span>
    <span class="ff-eyebrow ic-foil-text"><?= e($cv['greet']) ?></span>
    <span class="ff-mono ic-foil-text"><?= e($cv['m1']) ?><i>&amp;</i><?= e($cv['m2']) ?></span>
    <?php if ($cv['day']): ?><span class="ff-date ic-foil-text"><?= e($cv['day']['d']) ?> · <?= e($cv['day']['m']) ?> · <?= e($cv['day']['y']) ?></span><?php endif; ?>
  </span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="ic-deco ff-deco" aria-hidden="true"><span class="ff-rim"></span><span class="ff-c ff-c1"></span><span class="ff-c ff-c2"></span><span class="ff-c ff-c3"></span><span class="ff-c ff-c4"></span><span class="ff-fan"></span><span class="ff-gloss"></span></div>
<?php endif;