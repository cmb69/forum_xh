<?php

use Forum\Infra\View;

/**
 * @var View $this
 * @var bool $newTopic
 * @var string|null $tid
 * @var string|null $cid
 * @var string $action
 * @var string $previewUrl
 * @var string $backUrl
 * @var string $headingKey
 * @var mixed $comment
 * @var string $csrfTokenInput
 * @var string|false $i18n
 * @var array<string,string> $emoticons
 */
?>
<div class="forum_container">
    <form class="forum_comment" action="<?=$this->esc($action)?>" method="post" accept-charset="UTF-8" data-i18n="<?=$this->esc($i18n)?>">
        <div class="forum_preview_container"></div>
        <?=$this->raw($csrfTokenInput)?>
<?php if ($newTopic):?>
        <h6 class="forum_heading"><?=$this->text($headingKey)?></h6>
        <div class="forum_title">
            <label for="forum_title"><?=$this->text('msg_title')?></label>
            <input type="text" id="forum_title" name="forum_title">
        </div>
<?php else:?>
        <h6 class="forum_heading"><?=$this->text($headingKey)?></h6>
        <input type="hidden" name="forum_topic" value="<?=$this->esc($tid)?>">
        <input type="hidden" name="forum_comment" value="<?=$this->esc($cid)?>">
<?php endif?>
        <script type="text/x-template" id="forum_toolbar">
            <div>
                <button type="button" class="forum_bold_button" title="<?=$this->text('lbl_bold')?>" accesskey="b"><i class="fa fa-bold"></i></button>
                <button type="button" class="forum_italic_button" title="<?=$this->text('lbl_italic')?>" accesskey="i"><i class="fa fa-italic"></i></button>
                <button type="button" class="forum_underline_button" title="<?=$this->text('lbl_underline')?>"><i class="fa fa-underline"></i></button>
                <button type="button" class="forum_strikethrough_button" title="<?=$this->text('lbl_strikethrough')?>"><i class="fa fa-strikethrough"></i></button>
                <button type="button" class="forum_emoticon_button" title="<?=$this->text('lbl_emoticon')?>"><i class="fa fa-smile-o"></i></button>
                <div class="forum_emoticons">
                    <button type="button" class="forum_smile_button" title="<?=$this->text('lbl_smile')?>"><img src="<?=$this->esc($emoticons['smile'])?>" alt="<?=$this->text('lbl_smile')?>"></button>
                    <button type="button" class="forum_wink_button" title="<?=$this->text('lbl_wink')?>"><img src="<?=$this->esc($emoticons['wink'])?>" alt="<?=$this->text('lbl_wink')?>"></button>
                    <button type="button" class="forum_happy_button" title="<?=$this->text('lbl_happy')?>"><img src="<?=$this->esc($emoticons['happy'])?>" alt="<?=$this->text('lbl_happy')?>"></button>
                    <button type="button" class="forum_grin_button" title="<?=$this->text('lbl_grin')?>"><img src="<?=$this->esc($emoticons['grin'])?>" alt="<?=$this->text('lbl_grin')?>"></button>
                    <button type="button" class="forum_tongue_button" title="<?=$this->text('lbl_tongue')?>"><img src="<?=$this->esc($emoticons['tongue'])?>" alt="<?=$this->text('lbl_tongue')?>"></button>
                    <button type="button" class="forum_surprised_button" title="<?=$this->text('lbl_surprised')?>"><img src="<?=$this->esc($emoticons['surprised'])?>" alt="<?=$this->text('lbl_surprised')?>"></button>
                    <button type="button" class="forum_unhappy_button" title="<?=$this->text('lbl_unhappy')?>"><img src="<?=$this->esc($emoticons['unhappy'])?>" alt="<?=$this->text('lbl_unhappy')?>"></button>
                </div>
                <button type="button" class="forum_picture_button" title="<?=$this->text('lbl_picture')?>"><i class="fa fa-picture-o"></i></button>
                <button type="button" class="forum_iframe_button" title="<?=$this->text('lbl_iframe')?>"><i class="fa fa-hand-rock-o"></i></button>
                <button type="button" class="forum_link_button" title="<?=$this->text('lbl_link')?>"><i class="fa fa-link"></i></button>
                <button type="button" class="forum_font_button" title="<?=$this->text('lbl_size')?>"><i class="fa fa-font"></i></button>
                <div class="forum_font_sizes">
                    <button type="button" class="forum_big_button"><?=$this->text('lbl_big')?></button>
                    <button type="button" class="forum_small_button"><?=$this->text('lbl_small')?></button>
                </div>
                <button type="button" class="forum_bulleted_list_button" title="<?=$this->text('lbl_bulleted_list')?>"><i class="fa fa-list-ul"></i></button>
                <button type="button" class="forum_numeric_list_button" title="<?=$this->text('lbl_numeric_list')?>"><i class="fa fa-list-ol"></i></button>
                <button type="button" class="forum_list_item_button" title="<?=$this->text('lbl_list_item')?>"><i class="fa fa-asterisk"></i></button>
                <button type="button" class="forum_quotes_button" title="<?=$this->text('lbl_quotes')?>"><i class="fa fa-quote-right"></i></button>
                <button type="button" class="forum_code_button" title="<?=$this->text('lbl_code')?>"><i class="fa fa-code"></i></button>
                <button type="button" class="forum_preview_button" title="<?=$this->text('lbl_preview')?>" data-url="<?=$this->esc($previewUrl)?>"><i class="fa fa-eye"></i></button>
            </div>
        </script>
        <textarea name="forum_text" cols="80" rows="10"><?=$this->esc($comment)?></textarea>
        <p class="forum_submit">
            <input type="submit" class="submit" value="<?=$this->text('lbl_submit')?>">
        </p>
    </form>
    <div class="forum_navlink">
        <a href="<?=$this->esc($backUrl)?>"><?=$this->text('msg_back')?></a>
    </div>
</div>
