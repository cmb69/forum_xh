<h6 class="forum_heading" id="<?=$this->anchor()?>"><?=$this->title()?></h6>
<div class="forum_topic">
<?php foreach ($this->topic as $cid => $comment):?>
    <div>
<?php 	if ($comment['mayDelete']):?>
        <form class="forum_delete" action="." method="POST" data-message="<?=$this->text('msg_confirm_delete')?>">
	    <?=$this->csrfTokenInput()?>
            <input type="hidden" name="selected" value="<?=$this->su()?>">
			<input type="hidden" name="forum_actn" value="delete">
			<input type="hidden" name="forum_topic" value="<?=$this->tid()?>">
			<input type="hidden" name="forum_comment" value="<?=$this->escape($cid)?>">
			<button title="<?=$this->text('lbl_delete')?>"><i class="fa fa-trash"></i></button>
		</form>
		<a class="forum_edit" href="<?=$this->escape($comment['editUrl'])?>#<?=$this->anchor()?>">
			<button title="<?=$this->text('lbl_edit')?>"><i class="fa fa-pencil"></i></button>
		</a>
<?php 	endif?>
		<div class="forum_details">
			<span class="forum_user"><?=$this->escape($comment['user'])?></span>
			<span class="forum_separator"><?=$this->text('lbl_separator')?></span>
			<span class="forum_date"><?=$this->escape($comment['date'])?></span>
		</div>
		<div class="forum_comment"><?=$this->escape($comment['comment'])?></div>
    </div>
<?php endforeach?>
</div>
<?php if ($this->isUser):?>
    <?=$this->commentForm()?>
<?php endif?>
<div class="forum_navlink">
    <a href="<?=$this->href()?>"><?=$this->text('msg_back')?></a>
</div>