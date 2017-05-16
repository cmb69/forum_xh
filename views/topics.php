<h6 class="forum_heading" id="<?=$this->anchorLabel()?>"><?=$this->text('msg_topics')?></h6>
<div class="forum_topics">
<?php foreach ($this->topics as $topic):?>
    <div>
	<div class="forum_title">
            <a href="<?=$this->escape($topic['href'])?>"><?=$this->escape($topic['title'])?></a>
        </div>
	<div class="forum_details">
		<span><?=$this->plural('msg_comments', $topic['comments'])?></span>
		<span class="forum_separator"><?=$this->text('lbl_separator')?></span>
		<span><?=$this->text('msg_topic_details', $topic['user'])?></span>
		<span class="forum_separator"><?=$this->text('lbl_separator')?></span>
		<span class="forum_date"><?=$this->escape($topic['date'])?></span>
	</div>
    </div>
<?php endforeach?>
</div>
<?php if ($this->isUser):?>
<div class="forum_navlink">
    <a href="<?=$this->href()?>"><?=$this->text('msg_start_topic')?></a>
</div>
<?php endif?>
