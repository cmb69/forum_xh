<div class="forum_preview"></div>
<form class="forum_comment" action="<?=$this->action()?>" method="post" accept-charset="UTF-8" onsubmit="return Forum.validate()">
    <?=$this->csrfTokenInput()?>
<?php if ($this->newTopic):?>
    <h6 class="forum_heading" id="<?=$this->anchor()?>"><?=$this->text($this->headingKey)?></h6>
    <div class="forum_title">
        <label for="forum_title"><?=$this->text('msg_title')?></label>
	<input type="text" id="forum_title" name="forum_title">
    </div>
<?php else:?>
    <h6 class="forum_heading" id="<?=$this->anchor()?>"><?=$this->text($this->headingKey)?></h6>
    <input type="hidden" name="forum_topic" value="<?=$this->tid()?>">
    <input type="hidden" name="forum_comment" value="<?=$this->cid()?>">
<?php endif?>
    <textarea name="forum_text" cols="80" rows="10"><?=$this->comment()?></textarea>
    <div class="forum_submit">
        <input type="submit" class="submit" value="<?=$this->text('lbl_submit')?>">
    </div>
</form>
<?php if ($this->newTopic):?>
<div class="forum_navlink">
    <a href="<?=$this->overviewUrl()?>"><?=$this->text('msg_back')?></a>
</div>
<?php endif?>
