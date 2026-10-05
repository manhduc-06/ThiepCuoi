<?php
 defined('BASEPATH') OR exit('No direct script access allowed');



if ($part === 'cover'): ?>
<span class="pp" aria-hidden="true">
  <span class="pp-inside"></span>
  <span class="pp-lid"><span class="pp-lid-f"><span class="pp-window"><span class="pp-sun"><?= e($cv['m1']) ?><i>&amp;</i><?= e($cv['m2']) ?></span></span></span><span class="pp-lid-b"></span></span>
</span>
<?php elseif ($part === 'deco'): ?>
<div class="pp-scene" aria-hidden="true"><div class="pp-world">
  <span class="pp-floor"></span>
  <span class="pp-l pp-wall"></span>
  <span class="pp-l pp-arch"></span>
  <span class="pp-l pp-bush pp-bush-l"></span>
  <span class="pp-l pp-bush pp-bush-r"></span>
  <span class="pp-l pp-plaque"><b><?= e($cv['groom']) ?></b><i>&amp;</i><b><?= e($cv['bride']) ?></b></span>
  <span class="pp-l pp-hearts"></span>
</div></div>
<?php endif;