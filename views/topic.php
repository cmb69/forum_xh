<?php

use Forum\Infra\View;

/**
 * @var View $this
 * @var string $title
 * @var list<array{cid:string,user:string,mayDeleteComment:bool,commentDate:string,html:string,commentEditUrl:string}> $topic
 * @var string $tid
 * @var string $csrfTokenInput
 * @var bool $isUser
 * @var string $replyUrl
 * @var string $deleteUrl
 * @var string $href
 */
?>
<div class="forum_container">
  <h6 class="forum_heading"><?=$title?></h6>
  <div class="forum_topic">
<?foreach ($topic as $comment):?>
    <div>
<?  if ($comment['mayDeleteComment']):?>
      <form class="forum_delete" action="<?=$deleteUrl?>" method="POST" data-message="<?=$this->text('msg_confirm_delete')?>">
      <?=$csrfTokenInput?>
        <input type="hidden" name="forum_topic" value="<?=$tid?>">
        <input type="hidden" name="forum_comment" value="<?=$comment['cid']?>">
        <button title="<?=$this->text('lbl_delete')?>"><i class="fa fa-trash"></i></button>
      </form>
      <a class="forum_edit" href="<?=$comment['commentEditUrl']?>">
        <button title="<?=$this->text('lbl_edit')?>"><i class="fa fa-pencil"></i></button>
      </a>
<?  endif?>
      <div class="forum_details">
        <span class="forum_user"><?=$comment['user']?></span>
        <span class="forum_separator"><?=$this->text('lbl_separator')?></span>
        <span class="forum_date"><?=$comment['commentDate']?></span>
      </div>
      <div class="forum_comment"><?=$comment['html']?></div>
    </div>
<?endforeach?>
  </div>
  <div class="forum_navlink">
<?if ($isUser):?>
    <a href="<?=$replyUrl?>"><?=$this->text('msg_reply')?></a>
<?endif?>
    <a href="<?=$href?>"><?=$this->text('msg_back')?></a>
  </div>
</div>
