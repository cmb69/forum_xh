<?php

use Forum\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var list<array{class:string,key:string,arg:string,statekey:string}> $checks
 */
?>
<h1>Forum <?=$version?></h1>
<div class="forum_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?foreach ($checks as $check):?>
  <p class="<?=$check['class']?>"><?=$this->text($check['key'], $check['arg'])?><?=$this->text($check['statekey'])?></p>
<?endforeach?>
</div>
