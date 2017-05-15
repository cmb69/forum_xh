<h6 class="forum_heading" id="<?=$this->anchor()?>"><?=$this->title()?></h6>
<ul class="forum_topic">
<?php foreach ($this->topic as $cid => $comment):?>
    <li class="<?=$this->escape($comment['class'])?>">
<?php 	if ($comment['mayDelete']):?>
        <form class="forum_delete" action="." method="POST"
              onsubmit="return confirm(&quot;<?=$this->text('msg_confirm_delete')?>&quot;)">
	    <?=$this->csrfTokenInput()?>
            <input type="hidden" name="selected" value="<?=$this->su()?>">
	    <input type="hidden" name="forum_actn" value="delete">
	    <input type="hidden" name="forum_topic" value="<?=$this->tid()?>">
	    <input type="hidden" name="forum_comment" value="<?=$this->escape($cid)?>">
	    <input type="image" src="<?=$this->deleteImg()?>"
                   alt="<?=$this->text('lbl_delete')?>" title="<?=$this->text('lbl_delete')?>">
	</form>
	<a class="forum_edit" href="<?=$this->escape($comment['editUrl'])?>#<?=$this->anchor()?>">
	    <img src="<?=$this->editImg()?>" alt="<?=$this->text('lbl_edit')?>"
		 title="<?=$this->text('lbl_edit')?>" />
	</a>
<?php 	endif?>
        <div class="forum_details"><?=$this->escape($comment['details'])?></div>
	<div class="forum_comment"><?=$this->escape($comment['comment'])?></div>
    </li>
<?php endforeach?>
</ul>
<?php if ($this->isUser):?>
    <?=$this->commentForm()?>
<?php endif?>
<div class="forum_navlink">
    <a href="<?=$this->href()?>"><?=$this->text('msg_back')?></a>
</div>
