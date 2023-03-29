<?php

use Forum\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var list<array{state:string,label:string,stateLabel:string}> $checks
 */
?>
<h1>Forum <?=$version?></h1>
<div class="forum_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?foreach ($checks as $check):?>
  <p class="xh_<?=$check['state']?>"><?=$this->text('syscheck_message', $check['label'], $check['stateLabel'])?></p>
<?endforeach?>
</div>
