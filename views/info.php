<?php

use Forum\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var list<array{state:string,label:string,stateLabel:string}> $checks
 */
?>
<h1>Forum <?=$this->esc($version)?></h1>
<div class="forum_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?php foreach ($checks as $check):?>
  <p class="xh_<?=$this->esc($check['state'])?>"><?=$this->text('syscheck_message', $check['label'], $check['stateLabel'])?></p>
<?php endforeach?>
</div>
