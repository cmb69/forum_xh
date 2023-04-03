<?php

use Forum\Infra\View;

/**
 * @var View $this
 * @var string $version
 * @var list<array{class:string,key:string,arg:string,statekey:string}> $checks
 * @var list<array{name:string,url:string}> $forums
 * @var list<array{string}> $errors
 */
?>
<h1>Forum <?=$version?></h1>
<div class="forum_syscheck">
  <h2><?=$this->text('syscheck_title')?></h2>
<?foreach ($checks as $check):?>
  <p class="<?=$check['class']?>"><?=$this->text($check['key'], $check['arg'])?><?=$this->text($check['statekey'])?></p>
<?endforeach?>
<?if ($forums):?>
  <h2>Migration</h2>
<?  foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?  endforeach?>
<?  foreach ($forums as $forum):?>
  <form method="post" action="<?=$forum['url']?>">
    <span><?=$forum['name']?></span>
    <button name="forum_do">Migrate</button>
  </form>
<?  endforeach?>
<?endif?>
</div>
