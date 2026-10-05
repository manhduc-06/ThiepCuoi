<?php
 defined('BASEPATH') OR exit('No direct script access allowed');



$gf_art = '<svg class="gf-art" viewBox="0 0 100 280" preserveAspectRatio="none" aria-hidden="true" focusable="false">'
. '<g fill="none" stroke="currentColor" stroke-width="1" vector-effect="non-scaling-stroke">'
. '<path d="M100 8H8V272H100M100 13H13V267H100"/>'
. '<path d="M26 250V122A74 74 0 0 1 100 48M33 250V122A67 67 0 0 1 100 55M20 250H100"/>'
. '<path d="M26 230c-9-2-14-9-13-17 8 2 13 8 13 17zM26 205c-9-3-13-10-11-18 7 3 11 9 11 18zM27 180c-8-4-11-11-8-19 7 4 9 11 8 19zM30 156c-7-5-9-12-5-19 6 5 7 12 5 19zM37 133c-6-6-6-13-1-19 4 6 4 13 1 19zM48 112c-4-7-3-14 3-19 3 7 1 14-3 19zM26 218c8-3 13-9 13-17-8 2-13 8-13 17zM27 193c8-4 12-10 11-18-7 3-11 9-11 18z"/>'
. '<circle cx="100" cy="30" r="3"/><circle cx="30" cy="30" r="1.6"/><circle cx="30" cy="250" r="1.6"/></g></svg>';
if ($part === 'cover'): ?>
<span class="gf" aria-hidden="true">
  <span class="gf-inner"><span class="gf-inner-mono"><?= e($cv['m1']) ?><i>&amp;</i><?= e($cv['m2']) ?></span><span class="gf-inner-greet"><?= e($cv['greet']) ?></span></span>
  <span class="gf-door gf-l"><span class="gf-face"><?= $gf_art ?></span><span class="gf-back"></span></span>
  <span class="gf-door gf-r"><span class="gf-face"><?= $gf_art ?></span><span class="gf-back"></span></span>
  <span class="gf-band"></span>
  <span class="gf-medal"><?= e($cv['m1']) ?><i>&amp;</i><?= e($cv['m2']) ?></span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="gf-wing gf-wing-l" aria-hidden="true"></div><div class="gf-wing gf-wing-r" aria-hidden="true"></div>
<svg class="gf-garland" viewBox="0 0 220 70" aria-hidden="true" focusable="false"><g fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round">
  <path d="M14 62C40 22 78 8 110 8s70 14 96 54"/>
  <path d="M30 44c-8-1-13-6-14-13 7 1 12 6 14 13zM46 30c-7-3-11-9-10-16 7 3 10 9 10 16zM64 20c-6-4-8-11-5-17 6 4 7 11 5 17zM84 13c-5-5-6-12-1-17 4 5 4 12 1 17zM190 44c8-1 13-6 14-13-7 1-12 6-14 13zM174 30c7-3 11-9 10-16-7 3-10 9-10 16zM156 20c6-4 8-11 5-17-6 4-7 11-5 17zM136 13c5-5 6-12 1-17-4 5-4 12-1 17z"/>
  <circle cx="110" cy="8" r="4"/></g></svg>
<?php endif;