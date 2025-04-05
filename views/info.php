<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $version
 * @var list<object{class:string,key:string,arg:string,statekey:string}> $checks
 * @var list<object{name:string,url:string}> $forums
 * @var list<array{string}> $errors
 */
?>
<h1>Forum <?=$this->esc($version)?></h1>
<div class="forum_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?foreach ($checks as $check):?>
  <p class="<?=$this->esc($check->class)?>"><?=$this->text($check->key, $check->arg)?><?=$this->text($check->statekey)?></p>
<?endforeach?>
<?if ($forums):?>
  <h2>Migration</h2>
<?  foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?  endforeach?>
<?  foreach ($forums as $forum):?>
  <form method="post" action="<?=$this->esc($forum->url)?>">
    <span><?=$this->esc($forum->name)?></span>
    <button name="forum_do">Migrate</button>
  </form>
<?  endforeach?>
<?endif?>
</div>
