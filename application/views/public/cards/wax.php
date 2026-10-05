<?php
 defined('BASEPATH') OR exit('No direct script access allowed');




$wx_day = $cv['day'] ? $cv['day']['d'] . '.' . $cv['day']['m'] . '.' . $cv['day']['y'] : '';

$wx_day2 = $cv['day'] ? '<span>' . e($cv['day']['d'] . '.' . $cv['day']['m']) . '</span><span>' . e($cv['day']['y']) . '</span>' : '';
$wx_mono = e($cv['m1']) . '<i>&amp;</i>' . e($cv['m2']);
if ($part === 'cover'): ?>
<span class="wx" aria-hidden="true">
  <span class="wx-front"><span class="wx-stamp"><span><?= $wx_mono ?></span></span><span class="wx-post"><?= $wx_day2 ?></span></span>
  <span class="wx-back">
    <span class="wx-liner"></span>
    <span class="wx-letter"><span class="wx-letter-mono"><?= $wx_mono ?></span></span>
    <span class="wx-pocket"></span>
    <span class="wx-flap"></span>
    <span class="wx-sprig"></span>
    <span class="wx-seal"><span class="wx-half wx-half-l"><span><?= $wx_mono ?></span></span><span class="wx-half wx-half-r"><span><?= $wx_mono ?></span></span></span>
  </span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="wx-shadow" aria-hidden="true"></div><div class="wx-paper" aria-hidden="true"></div>
<div class="wx-remnant" aria-hidden="true"><span class="wx-half wx-half-l"><span><?= $wx_mono ?></span></span><span class="wx-half wx-half-r"><span><?= $wx_mono ?></span></span></div>
<?php if ($wx_day !== ''): ?><div class="wx-postmark" aria-hidden="true"><?= $wx_day2 ?></div><?php endif; ?>
<?php elseif ($part === 'end'): ?>
<p class="wx-sign" aria-hidden="true">— <?= e($cv['groom']) ?> &amp; <?= e($cv['bride']) ?></p>
<?php endif;