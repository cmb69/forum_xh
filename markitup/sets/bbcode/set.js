// ----------------------------------------------------------------------------
// markItUp!
// ----------------------------------------------------------------------------
// Copyright (C) 2008 Jay Salvat
// http://markitup.jaysalvat.com/
// ----------------------------------------------------------------------------
// BBCode tags example
// http://en.wikipedia.org/wiki/Bbcode
// ----------------------------------------------------------------------------
// Feel free to add more tags
// ----------------------------------------------------------------------------
Forum.settings = {
	previewParserPath:	'?&forum_preview', // path to your BBCode parser
	previewPosition: 'before',
	//previewInWindow: 'width=800, height=600, resizable=yes, scrollbars=yes',
	//afterInsert: function() {$('iframe').attr('frameBorder', 0)},
	markupSet: [
		{name:Forum.TX.BOLD, key:'B', openWith:'[b]', closeWith:'[/b]'},
		{name:Forum.TX.ITALIC, key:'I', openWith:'[i]', closeWith:'[/i]'},
		{name:Forum.TX.UNDERLINE, key:'U', openWith:'[u]', closeWith:'[/u]'},
		{separator:'---------------' },
		{name:Forum.TX.EMOTICON,
		dropMenu:[
			{name:Forum.TX.SMILE, replaceWith:':)'},
			{name:Forum.TX.WINK, replaceWith:';)'},
			{name:Forum.TX.HAPPY, replaceWith:':))'},
			{name:Forum.TX.GRIN, replaceWith:':D'},
			{name:Forum.TX.TONGUE, replaceWith:':P'},
			{name:Forum.TX.SURPRISED, replaceWith:':o'},
			{name:Forum.TX.UNHAPPY, replaceWith:':('}
		]},
		{name:Forum.TX.PICTURE, key:'P', replaceWith:'[img][![Url]!][/img]'},
		{name:Forum.TX.LINK, key:'L', openWith:'[url=[![Url]!]]', closeWith:'[/url]', placeHolder:Forum.TX.LINK_TEXT},
		{separator:'---------------' },
		{name:Forum.TX.SIZE, key:'S', openWith:'[size=[![Text size]!]]', closeWith:'[/size]',
		dropMenu :[
			{name:Forum.TX.BIG, openWith:'[size=150]', closeWith:'[/size]' },
			{name:Forum.TX.NORMAL, openWith:'[size=100]', closeWith:'[/size]' },
			{name:Forum.TX.SMALL, openWith:'[size=67]', closeWith:'[/size]' }
		]},
		{separator:'---------------' },
		{name:Forum.TX.BULLETED_LIST, openWith:'[list]\n', closeWith:'\n[/list]'},
		{name:Forum.TX.NUMERIC_LIST, openWith:'[list=[![Starting number]!]]\n', closeWith:'\n[/list]'},
		{name:Forum.TX.LIST_ITEM, replaceWith:'[*]'},
		{separator:'---------------' },
		{name:Forum.TX.QUOTES, openWith:'[quote]', closeWith:'[/quote]'},
		{name:Forum.TX.CODE, openWith:'[code]', closeWith:'[/code]'},
		{separator:'---------------' },
		{name:Forum.TX.CLEAN, className:"clean", replaceWith:function(markitup) { return markitup.selection.replace(/\[(.*?)\]/g, "") } },
		{name:Forum.TX.PREVIEW, className:"preview", call:'preview' }
	]
}