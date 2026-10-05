<?php
 defined('BASEPATH') OR exit('No direct script access allowed');




$wc_photo = $cv['photo'] !== ''
? '<img src="' . e($cv['photo']) . '"' . ($cv['photo_srcset'] !== '' ? ' srcset="' . e($cv['photo_srcset']) . '" sizes="' . e($cv['photo_sizes']) . '"' : '')
. ' alt="" decoding="async" style="object-position:' . e($cv['photo_pos']) . '">'
: '<span class="wc-ph">' . e($cv['m1']) . '<i>&amp;</i>' . e($cv['m2']) . '</span>';
$wc_cap = e($cv['groom']) . ' &amp; ' . e($cv['bride']);
if ($part === 'cover'): ?>
<span class="wc" aria-hidden="true">
  <span class="wc-card2"></span>
  <span class="wc-card"><span class="wc-bloom wc-bloom-a"></span><span class="wc-bloom wc-bloom-b"></span><span class="wc-leaf wc-leaf-a"></span><span class="wc-leaf wc-leaf-b"></span></span>
  <span class="wc-pola"><span class="wc-photo"><?= $wc_photo ?></span><span class="wc-cap"><?= $wc_cap ?></span><span class="wc-tape"></span></span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="ic-deco wc-deco" aria-hidden="true"><span class="wc-bloom wc-bloom-a"></span><span class="wc-bloom wc-bloom-b"></span><span class="wc-bloom wc-bloom-c"></span><span class="wc-leaf wc-leaf-a"></span><span class="wc-leaf wc-leaf-b"></span></div>
<div class="wc-top" aria-hidden="true"><div class="wc-pola"><span class="wc-photo"><?= $wc_photo ?></span><span class="wc-cap"><?= $wc_cap ?></span><span class="wc-tape"></span></div></div>
<?php endif;