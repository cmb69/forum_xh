<?php
/**
 * @var \Forum\View $this
 * @var string $title
 * @var array<string,array{user:string,time:int,comment:string,mayDelete:bool,editUrl:string,date:string}> $topic
 * @var string $tid
 * @var \Forum\HtmlString $csrfTokenInput
 * @var bool $isUser
 * @var string $replyUrl
 * @var string $href
 */
?>
<div class="forum_container">
    <h6 class="forum_heading"><?=$this->esc($title)?></h6>
    <div class="forum_topic">
<?php foreach ($topic as $cid => $comment):?>
        <div>
<?php   if ($comment['mayDelete']):?>
            <form class="forum_delete" action="<?=$this->esc($href)?>" method="POST" data-message="<?=$this->text('msg_confirm_delete')?>">
            <?=$this->esc($csrfTokenInput)?>
                <input type="hidden" name="forum_actn" value="delete">
                <input type="hidden" name="forum_topic" value="<?=$this->esc($tid)?>">
                <input type="hidden" name="forum_comment" value="<?=$this->esc($cid)?>">
                <button title="<?=$this->text('lbl_delete')?>"><i class="fa fa-trash"></i></button>
            </form>
            <a class="forum_edit" href="<?=$this->esc($comment['editUrl'])?>">
                <button title="<?=$this->text('lbl_edit')?>"><i class="fa fa-pencil"></i></button>
            </a>
<?php   endif?>
            <div class="forum_details">
				<span class="forum_user"><?=$this->esc($comment['user'])?></span>
                <span class="forum_separator"><?=$this->text('lbl_separator')?></span>
                <span class="forum_date"><?=$this->esc($comment['date'])?></span>
            </div>
            <div class="forum_comment"><?=$this->esc($comment['comment'])?></div>
        </div>
<?php endforeach?>
    </div>
    <div class="forum_navlink">
<?php if ($isUser):?>
        <a href="<?=$this->esc($replyUrl)?>"><?=$this->text('msg_reply')?></a>
<?php endif?>
        <a href="<?=$this->esc($href)?>"><?=$this->text('msg_back')?></a>
    </div>
</div>
