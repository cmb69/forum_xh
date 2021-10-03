<?php

use Forum\Topic;

/**
 * @var \Forum\View $this
 * @var bool $isUser
 * @var string $href
 * @var callable $topicUrl
 * @var callable $topicDate
 * @var array<string,Topic> $topics
 */
?>
<div class="forum_container">
    <h6 class="forum_heading"><?=$this->text('msg_topics')?></h6>
    <div class="forum_topics">
<?php foreach ($topics as $tid => $topic):?>
    <div>
        <div class="forum_title">
            <a href="<?=$this->esc($topicUrl($tid))?>"><?=$this->esc($topic->title())?></a>
        </div>
        <div class="forum_details">
            <span><?=$this->plural('msg_comments', $topic->comments())?></span>
            <span class="forum_separator"><?=$this->text('lbl_separator')?></span>
            <span><?=$this->text('msg_topic_details', $topic->user())?></span>
            <span class="forum_separator"><?=$this->text('lbl_separator')?></span>
            <span class="forum_date"><?=$this->esc($topicDate($topic))?></span>
        </div>
    </div>
<?php endforeach?>
    </div>
<?php if ($isUser):?>
    <div class="forum_navlink">
        <a href="<?=$this->esc($href)?>"><?=$this->text('msg_start_topic')?></a>
    </div>
<?php endif?>
</div>
