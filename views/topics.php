<?php

use Plib\View;

/**
 * @var View $this
 * @var bool $isUser
 * @var string $href
 * @var list<array{tid:string,title:string,user:string,comments:int,date:string,url:string}> $topics
 * @var string $script
 */
?>
<script type="module" src="<?=$this->esc($script)?>"></script>
<div class="forum_container">
  <h6 class="forum_heading"><?=$this->text('msg_topics')?></h6>
  <div class="forum_topics">
<?foreach ($topics as $topic):?>
  <div>
    <div class="forum_title">
      <a href="<?=$this->esc($topic['url'])?>"><?=$this->esc($topic['title'])?></a>
    </div>
    <div class="forum_details">
      <span><?=$this->plural('msg_comments', $topic['comments'])?></span>
      <span class="forum_separator"><?=$this->text('lbl_separator')?></span>
      <span><?=$this->text('msg_topic_details', $topic['user'])?></span>
      <span class="forum_separator"><?=$this->text('lbl_separator')?></span>
      <span class="forum_date"><?=$this->esc($topic['date'])?></span>
    </div>
  </div>
<?endforeach?>
  </div>
<?if ($isUser):?>
  <div class="forum_navlink">
    <a href="<?=$this->esc($href)?>"><?=$this->text('msg_start_topic')?></a>
  </div>
<?endif?>
</div>
