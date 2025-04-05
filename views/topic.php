<?php

use Plib\View;

if (!defined("CMSIMPLE_XH_VERSION")) {http_response_code(403); exit;}

/**
 * @var View $this
 * @var string $title
 * @var list<object{cid:string,user:string,mayDeleteComment:bool,commentDate:string,html:string,commentEditUrl:string,deleteUrl:string}> $topic
 * @var string $tid
 * @var string $token
 * @var bool $isUser
 * @var string $replyUrl
 * @var string $href
 * @var string $script
 * @var string $level
 */
?>
<script type="module" src="<?=$this->esc($script)?>"></script>
<div class="forum_container">
  <<?=$this->esc($level)?> class="forum_heading"><?=$this->esc($title)?></<?=$this->esc($level)?>>
  <div class="forum_topic">
<?foreach ($topic as $comment):?>
    <div>
<?  if ($comment->mayDeleteComment):?>
      <form class="forum_delete" action="<?=$this->esc($comment->deleteUrl)?>" method="POST" data-message="<?=$this->text('msg_confirm_delete')?>">
        <input type="hidden" name="forum_token" value="<?=$this->esc($token)?>">
        <button name="forum_do" title="<?=$this->text('lbl_delete')?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="1em" height="1em" fill="currentColor"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M135.2 17.7L128 32 32 32C14.3 32 0 46.3 0 64S14.3 96 32 96l384 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-96 0-7.2-14.3C307.4 6.8 296.3 0 284.2 0L163.8 0c-12.1 0-23.2 6.8-28.6 17.7zM416 128L32 128 53.2 467c1.6 25.3 22.6 45 47.9 45l245.8 0c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg></button>
      </form>
      <a class="forum_edit" href="<?=$this->esc($comment->commentEditUrl)?>">
        <button title="<?=$this->text('lbl_edit')?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="1em" height="1em" fill="currentColor"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M410.3 231l11.3-11.3-33.9-33.9-62.1-62.1L291.7 89.8l-11.3 11.3-22.6 22.6L58.6 322.9c-10.4 10.4-18 23.3-22.2 37.4L1 480.7c-2.5 8.4-.2 17.5 6.1 23.7s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L387.7 253.7 410.3 231zM160 399.4l-9.1 22.7c-4 3.1-8.5 5.4-13.3 6.9L59.4 452l23-78.1c1.4-4.9 3.8-9.4 6.9-13.3l22.7-9.1 0 32c0 8.8 7.2 16 16 16l32 0zM362.7 18.7L348.3 33.2 325.7 55.8 314.3 67.1l33.9 33.9 62.1 62.1 33.9 33.9 11.3-11.3 22.6-22.6 14.5-14.5c25-25 25-65.5 0-90.5L453.3 18.7c-25-25-65.5-25-90.5 0zm-47.4 168l-144 144c-6.2 6.2-16.4 6.2-22.6 0s-6.2-16.4 0-22.6l144-144c6.2-6.2 16.4-6.2 22.6 0s6.2 16.4 0 22.6z"/></svg></button>
      </a>
<?  endif?>
      <div class="forum_details">
        <span class="forum_user"><?=$this->esc($comment->user)?></span>
        <span class="forum_separator"><?=$this->text('lbl_separator')?></span>
        <span class="forum_date"><?=$this->esc($comment->commentDate)?></span>
      </div>
      <div class="forum_comment"><?=$this->raw($comment->html)?></div>
    </div>
<?endforeach?>
  </div>
  <div class="forum_navlink">
<?if ($isUser):?>
    <a href="<?=$this->esc($replyUrl)?>"><?=$this->text('msg_reply')?></a>
<?endif?>
    <a href="<?=$this->esc($href)?>"><?=$this->text('msg_back')?></a>
  </div>
</div>
