<form class="forum_comment" action="<?=$this->action()?>" method="post" accept-charset="UTF-8" data-i18n="<?=$this->i18n()?>">
	<div class="forum_preview_container"></div>
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
	<script type="text/x-template" id="forum_toolbar">
		<div>
			<button type="button" class="forum_bold_button" title="<?=$this->text('lbl_bold')?>" accesskey="b"><i class="fa fa-bold"></i></button>
			<button type="button" class="forum_italic_button" title="<?=$this->text('lbl_italic')?>" accesskey="i"><i class="fa fa-italic"></i></button>
			<button type="button" class="forum_underline_button" title="<?=$this->text('lbl_underline')?>"><i class="fa fa-underline"></i></button>
			<button type="button" class="forum_strikethrough_button" title="<?=$this->text('lbl_strikethrough')?>"><i class="fa fa-strikethrough"></i></button>
			<button type="button" class="forum_emoticon_button" title="<?=$this->text('lbl_emoticon')?>"><i class="fa fa-smile-o"></i></button>
			<div class="forum_emoticons">
				<button type="button" class="forum_smile_button" title="<?=$this->text('lbl_smile')?>"><img src="<?=$this->escape($this->emoticons['smile'])?>" alt="<?=$this->text('lbl_smile')?>"></button>
				<button type="button" class="forum_wink_button" title="<?=$this->text('lbl_wink')?>"><img src="<?=$this->escape($this->emoticons['wink'])?>" alt="<?=$this->text('lbl_wink')?>"></button>
				<button type="button" class="forum_happy_button" title="<?=$this->text('lbl_happy')?>"><img src="<?=$this->escape($this->emoticons['happy'])?>" alt="<?=$this->text('lbl_happy')?>"></button>
				<button type="button" class="forum_grin_button" title="<?=$this->text('lbl_grin')?>"><img src="<?=$this->escape($this->emoticons['grin'])?>" alt="<?=$this->text('lbl_grin')?>"></button>
				<button type="button" class="forum_tongue_button" title="<?=$this->text('lbl_tongue')?>"><img src="<?=$this->escape($this->emoticons['tongue'])?>" alt="<?=$this->text('lbl_tongue')?>"></button>
				<button type="button" class="forum_surprised_button" title="<?=$this->text('lbl_surprised')?>"><img src="<?=$this->escape($this->emoticons['surprised'])?>" alt="<?=$this->text('lbl_surprised')?>"></button>
				<button type="button" class="forum_unhappy_button" title="<?=$this->text('lbl_unhappy')?>"><img src="<?=$this->escape($this->emoticons['unhappy'])?>" alt="<?=$this->text('lbl_unhappy')?>"></button>
			</div>
			<button type="button" class="forum_picture_button" title="<?=$this->text('lbl_picture')?>"><i class="fa fa-picture-o"></i></button>
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
			<button type="button" class="forum_preview_button" title="<?=$this->text('lbl_preview')?>" data-url="<?=$this->previewUrl()?>"><i class="fa fa-eye"></i></button>
		</div>
	</script>
    <textarea name="forum_text" cols="80" rows="10"><?=$this->comment()?></textarea>
    <p class="forum_submit">
        <input type="submit" class="submit" value="<?=$this->text('lbl_submit')?>">
    </p>
</form>
<?php if ($this->newTopic):?>
<div class="forum_navlink">
    <a href="<?=$this->overviewUrl()?>"><?=$this->text('msg_back')?></a>
</div>
<?php endif?>