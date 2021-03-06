<?php
/**
 * @var \Forum\View $this
 * @var string $version
 * @var array<int,array<string,mixed>> $checks
 */
?>
<h1>Forum <?=$this->esc($version)?></h1>
<div class="forum_syscheck">
    <h2><?=$this->text('syscheck_title')?></h2>
<?php foreach ($checks as $check):?>
    <p class="xh_<?=$this->esc($check['state'])?>"><?=$this->text('syscheck_message', $check['label'], $check['stateLabel'])?></p>
<?php endforeach?>
</div>
