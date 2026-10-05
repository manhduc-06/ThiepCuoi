<?php
 defined('BASEPATH') OR exit('No direct script access allowed');



if ($part === 'cover'): ?>
<span class="sc" aria-hidden="true">
  <span class="sc-roll"><span class="sc-dowel"></span><span class="sc-paper"></span><span class="sc-band"></span><span class="sc-bow"></span></span>
  <span class="sc-string"></span>
  <span class="sc-tag"></span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="sc-rod sc-rod-t" aria-hidden="true"></div>
<div class="sc-rodwrap" aria-hidden="true"><div class="sc-rod sc-rod-b"></div></div>
<?php elseif ($part === 'end'): ?>
<div class="sc-chop" aria-hidden="true"><span><?= e($cv['m1']) ?></span><span><?= e($cv['m2']) ?></span></div>
<?php endif;