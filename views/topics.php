<h6 class="forum_heading" id="<?=$this->anchorLabel()?>"><?=$this->text('msg_topics')?></h6>
<ul class="forum_topics">
<?php foreach ($this->topics as $topic):?>
    <li class="<?=$this->escape($topic['class'])?>">
	<div class="forum_title">
            <a href="<?=$this->escape($topic['href'])?>"><?=$this->escape($topic['title'])?></a>
        </div>
	<div class="forum_details"><?=$this->plural('msg_comments', $topic['comments'])?> <?=$this->text('msg_topic_details', $topic['details'])?></div>
    </li>
<?php endforeach?>
</ul>
<?php if ($this->isUser):?>
<div class="forum_navlink">
    <a href="<?=$this->href()?>"><?=$this->text('msg_start_topic')?></a>
</div>
<?php endif?>
