<?php
 defined('BASEPATH') OR exit('No direct script access allowed');

if ($part === 'cover'): ?>
<span class="ic-env-body" aria-hidden="true"></span>
<span class="ic-env-flap" aria-hidden="true"></span>
<span class="ic-seal" aria-hidden="true"><?= e($cv['m1']) ?><i>&amp;</i><?= e($cv['m2']) ?></span>
<?php endif;