<?php

/**
 * Front-End of Forum_XH.
 *
 * Copyright (c) 2012 Christoph M. Becker (see license.txt)
 */


if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}


define('FORUM_VERSION', '1beta2');


/**
 * Fully qualified absolute URL to CMSimple's index.php.
 */
define('FORUM_URL', 'http://'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
	.$_SERVER['SERVER_NAME'].preg_replace('/index.php$/', '', $_SERVER['PHP_SELF']));


require_once $pth['folder']['plugin_classes'] . 'Contents.php';
$temp = $plugin_cf['forum']['folder_data'] != ''
    ? $pth['folder']['base'] . $plugin_cf['forum']['folder_data']
    : $pth['folder']['plugins'] . 'forum/data/';
$_Forum_Contents = new Forum_Contents($temp);

/**
 * Returns the numerus suffix for the language keys.
 *
 * @param int $count
 * @return string
 */
function forum_numerus($count) {
    if ($count == 1) {
	return '_singular';
    } elseif ($count >= 2 and $count < 5) {
	return '_plural_2-4';
    } else{
	return '_plural_5';
    }
}


/**
 * Returns the currently logged in user (Memberpages or Register).
 *
 * @return string
 */
function forum_user() {
    if (session_id() == '') {session_start();}
    return isset($_SESSION['Name']) ? $_SESSION['Name'] : (
	    isset($_SESSION['username']) ? $_SESSION['username'] : FALSE);
}


/**
 * Processes a posted comment.
 * Returns the topic ID, if the comment could be posted,
 * FALSE otherwise.
 *
 * @param string $forum  The name of the forum.
 * @param string $tid  The topic ID (NULL means new topic).
 * @return string  The topic ID.
 */
function forum_post_comment($forum, $tid = NULL) {
    global $_Forum_Contents;

    if (!isset($tid) && empty($_POST['forum_title'])
	    || forum_user() === FALSE || empty($_POST['forum_comment'])) {
	return FALSE;
    }
    $tid = isset($tid) ? $_Forum_Contents->cleanId($tid) : uniqid();
    if ($tid === FALSE ) {return FALSE;}

    $_Forum_Contents->lock($forum, LOCK_EX);

    $comments = $_Forum_Contents->getTopic($forum, $tid);
    $cid = uniqid();
    $rec = array(
	    'user' => forum_user(),
	    'time' => time(),
	    'comment' => stsl($_POST['forum_comment']));
    $comments[$cid] = $rec;
    $_Forum_Contents->setTopic($forum, $tid, $comments);

    $topics = $_Forum_Contents->getTopics($forum);
    $rec = array(
	    'title' => isset($_POST['forum_title'])
		    ? stsl($_POST['forum_title']) : $topics[$tid]['title'],
	    'comments' => count($comments),
	    'user' => $rec['user'],
	    'time' => $rec['time']);
    $topics[$tid] = $rec;
    $_Forum_Contents->setTopics($forum, $topics);

    $_Forum_Contents->lock($forum, LOCK_UN);

    return $tid;
}


/**
 * Deletes a comment
 * Returns the topic ID, if the topic has further comments,
 * otherwise NULL, or FALSE, if the comment couldn't be deleted.
 *
 * @param string $forum  The name of the forum.
 * @param string $tid  The topic ID.
 * @param string $cid  The comment ID.
 * @return string $tid  The topic ID.
 */
function forum_delete_comment($forum, $tid, $cid) {
    global $adm, $_Forum_Contents;

    if ($tid === FALSE || $cid === FALSE) {return FALSE;}
    $_Forum_Contents->lock($forum, LOCK_EX);
    $topics = $_Forum_Contents->getTopics($forum);
    $comments = $_Forum_Contents->getTopic($forum, $tid);
    if (!$adm && forum_user() != $comments[$cid]['user']) {
	return FALSE;
    }
    unset($comments[$cid]);
    if (count($comments) > 0) {
	$_Forum_Contents->setTopic($forum, $tid, $comments);
	$rec = end($comments);
	$topics[$tid]['comments'] = count($comments);
	$topics[$tid]['user'] = $rec['user'];
	$topics[$tid]['time'] = $rec['time'];
    } else {
	unlink($_Forum_Contents->dataFolder($forum).$tid.'.dat');
	unset($topics[$tid]);
	$tid = NULL;
    }
    $_Forum_Contents->setTopics($forum, $topics);
    $_Forum_Contents->lock($forum, LOCK_UN);
    return $tid;
}


/**
 * Includes js and css to the <head>.
 *
 * @global string $hjs
 * @return void
 */
function forum_hjs() {
    global $pth, $hjs, $plugin_cf, $plugin_tx;

    $ptx = $plugin_tx['forum'];
    $dir = $pth['folder']['plugins'].'forum/markitup/';
    $hjs .= tag('link rel="stylesheet" type="text/css" href="'.$dir.'skins/simple/style.css"')."\n"
	    .tag('link rel="stylesheet" type="text/css" href="'.$dir.'sets/bbcode/style.css"')."\n";
    require_once $pth['folder']['plugins'].'jquery/jquery.inc.php';
    include_jquery();
    include_jqueryplugin('markitup', $dir.'jquery.markitup.js');
    $hjs .= '<script type="text/javascript">/* <![CDATA[ */'."\n"
	    .'Forum = {TX: {';
    foreach (array('title_missing', 'comment_missing', 'bold', 'italic', 'underline', 'emoticon', 'smile', 'wink', 'happy', 'grin',
	    'tongue', 'surprised', 'unhappy', 'picture', 'link', 'size', 'big', 'normal', 'small',
	    'bulleted_list', 'numeric_list', 'list_item', 'quotes', 'code', 'clean', 'preview', 'link_text') as $i => $key) {
	if ($i > 0) {$hjs .= ', ';}
	$hjs .= strtoupper($key).': \''.addcslashes($ptx['lbl_'.$key], "\0..\37\\\'").'\'';
    }
    $hjs .= '}}'."\n"
	    .'jQuery(function() {jQuery(\'form.forum_comment textarea\').markItUp(Forum.settings)})'."\n"
	    .'/* ]]> */</script>'."\n";
    $hjs .= '<script type="text/javascript" src="'.$dir.'sets/bbcode/set.js"></script>'."\n";
}


/**
 * Returns the powered by link.
 *
 * @return string  The (X)HTML.
 */
function forum_powered_by() {
    global $plugin_tx;

    return '<div class="forum_powered_by">'.$plugin_tx['forum']['msg_powered_by'].'</div>';
}


/**
 * Returns the comment form.
 *
 * @param string $tid  The topic ID.
 * @return string  The (X)HTML.
 */
function forum_comment_form($tid = NULL) {
    global $su, $plugin_tx;

    if (forum_user() === FALSE) {return FALSE;}
    $ptx = $plugin_tx['forum'];
    forum_hjs();
    $href = "?$su&amp;forum_actn=post";
    $o = '<form class="forum_comment" action="'.$href.'" method="POST" accept-charset="UTF-8" onsubmit="return Forum.validate()">';
    if (!isset($tid)) {
	$o .= '<h6 class="forum_heading">'.$ptx['msg_new_topic'].'</h6>';
	$o .= '<div class="forum_title">'.'<label for="forum_title">'.$ptx['msg_title'].'</label>'
		.tag('input type="text" id="forum_title" name="forum_title"').'</div>';
    } else {
	$o .= '<h6 class="forum_heading">'.$ptx['msg_add_comment'].'</h6>';
	$o .= tag('input type="hidden" name="forum_topic" value="'.$tid.'"');
    }
    $o .= '<textarea name="forum_comment" cols="80" rows="10">'.'</textarea>'
	    .'<div class="forum_submit">'
	    .tag('input type="submit" class="submit" value="'.$ptx['lbl_submit'].'"').'</div>'
	    .'</form>';
    if (!isset($tid)) {
	$o .= '<div class="forum_navlink">'.'<a href="?'.$su.'">'.$ptx['msg_back'].'</a>'.'</div>';
    }
    return $o;
}


/**
 * Returns the posted by/on/at view.
 *
 * @param array $rec  The topic or comment record.
 * @return string  The (X)HTML.
 */
function forum_posted($rec) {
    global $plugin_tx;

    $ptx = $plugin_tx['forum'];
    $date = date($ptx['format_date'], $rec['time']);
    $time = date($ptx['format_time'], $rec['time']);
    return str_replace(array('{user}', '{date}', '{time}'),
	    array($rec['user'], $date, $time), $ptx['msg_posted']);

}


/**
 * Returns the topics overview.
 *
 * @param string $forum  The name of the forum.
 * @return string  The (X)HTML.
 */
function forum_view_topics($forum) {
    global $su, $plugin_tx, $_Forum_Contents;

    $ptx = $plugin_tx['forum'];
    $_Forum_Contents->lock($forum, LOCK_SH);
    $topics = $_Forum_Contents->getTopics($forum);
    $_Forum_Contents->lock($forum, LOCK_UN);
    uasort($topics, create_function('$a, $b', "return \$b['time'] - \$a['time'];"));
    $o = '<h6 class="forum_heading">'.$ptx['msg_topics'].'</h6>'
	    .'<ul class="forum_topics">';
    $i = 1;
    foreach ($topics as $tid => $topic) {
	$href = "?$su&amp;forum_topic=$tid";
	$comments = sprintf($ptx['msg_comments'.forum_numerus($topic['comments'])], $topic['comments']);
	$details = str_replace(array('{comments}', '{posted}'), array($comments, forum_posted($topic)),
		$ptx['msg_topic_details']);
	$o .= '<li class="forum_'.($i & 1 ? 'odd' : 'even').'">'
		.'<div class="forum_title"><a href="'.$href.'">'.$topic['title'].'</a></div>'
		.'<div class="forum_details">'.$details.'</div>'
		.'</li>';
	$i++;
    }
    $o .= '</ul>';
    if (forum_user() !== FALSE) {
	$href = "?$su&amp;forum_actn=new";
	$o .= '<div class="forum_navlink"><a href="'.$href.'">'.$ptx['msg_start_topic'].'</a></div>';
    }
    $o .= forum_powered_by();
    return $o;
}


/**
 * Returns the topic view.
 *
 * @param string $forum  The name of the forum.
 * @param string $tid  The topic ID.
 * @return string  The (X)HTML.
 */
function forum_view_topic($forum, $tid) {
    global $su, $pth, $adm, $plugin_tx, $_Forum_Contents;

    $ptx = $plugin_tx['forum'];
    $_Forum_Contents->lock($forum, LOCK_SH);
    $topics = $_Forum_Contents->getTopics($forum);
    $topic = $_Forum_Contents->getTopic($forum, $tid);
    $_Forum_Contents->lock($forum, LOCK_UN);
    $href = "?$su";
    $o = '<h6 class="forum_heading">'.htmlspecialchars($topics[$tid]['title'], ENT_NOQUOTES, 'UTF-8').'</h6>'
	    .'<ul class="forum_topic">';
    $i = 1;
    include_once $pth['folder']['plugins'] . 'forum/classes/BBCode.php';
    $bbcode = new Forum_BBCode($pth['folder']['plugins'] . 'forum/images/');
    foreach ($topic as $cid => $comments) {
	$delform = $adm || $comments['user'] == forum_user()
		? '<form class="forum_delete" action="." method="POST"'
			.' onsubmit="return confirm(\''.$ptx['msg_confirm_delete'].'\')">'
		    .tag('input type="hidden" name="selected" value="'.$su.'"')
		    .tag('input type="hidden" name="forum_actn" value="delete"')
		    .tag('input type="hidden" name="forum_topic" value="'.$tid.'"')
		    .tag('input type="hidden" name="forum_comment" value="'.$cid.'"')
		    .tag('input type="image" src="'.$pth['folder']['plugins'].'forum/images/delete.png"'
			.' alt="'.$ptx['lbl_delete'].'" title="'.$ptx['lbl_delete'].'"')
		    .'</form>'
		: '';
	$o .= '<li class="forum_'.($i & 1 ? 'odd' : 'even').'">'
		.$delform
		.'<div class="forum_details">'.forum_posted($comments).'</div>'
		.'<div class="forum_comment">'.$bbcode->toHtml($comments['comment']).'</div>'
		.'</li>';
	$i++;
    }
    $o .= '</ul>';
    if (forum_user() !== FALSE) {$o .= forum_comment_form($tid);}
    $o .= '<div class="forum_navlink"><a href="'.$href.'">'.$ptx['msg_back'].'</a></div>';
    $o .= forum_powered_by();
    return $o;
}


/**
 * Handles the forum requests.
 *
 * @access public
 * @param string $forum  The name of the forum.
 * @return mixed
 */
function forum($forum) {
    global $su, $e, $plugin_tx, $_Forum_Contents;

    $ptx = $plugin_tx['forum'];
    if (!preg_match('/^[a-z0-9\-]+$/u', $forum)) {
	$e .= '<li><b>'.$ptx['msg_invalid_name'].'</b>'.tag('br').$forum.'</li>'."\n";
	return FALSE;
    }
    $action = isset($_REQUEST['forum_actn']) ? $_REQUEST['forum_actn'] : 'view';
    switch ($action) {
	case 'view':
	    if (empty($_GET['forum_topic']) || ($tid = $_Forum_Contents->cleanId($_GET['forum_topic'])) === FALSE
		    || !file_exists($_Forum_Contents->dataFolder($forum).$tid.'.dat')) {
		return forum_view_topics($forum);
	    } else {
		return forum_view_topic($forum, $tid);
	    }
	case 'new':
	    return forum_comment_form().forum_powered_by();
	case 'post':
	    $tid = forum_post_comment($forum, $_POST['forum_topic']);
	    $params = $tid ? "?$su&forum_topic=$tid" : "?$su";
	    header('Location: '.FORUM_URL.$params, TRUE, 303);
	    exit;
	case 'delete':
	    $tid = $_Forum_Contents->cleanId($_POST['forum_topic']);
	    $cid = $_Forum_Contents->cleanId($_POST['forum_comment']);
	    $params = forum_delete_comment($forum, $tid, $cid)
		    ? "?$su&forum_topic=$_POST[forum_topic]" : "?$su";
	    header('Location: '.FORUM_URL.$params, TRUE, 303);
	    exit;
    }
}


/**
 * Return the comment preview.
 */
if (isset($_GET['forum_preview'])) {
    include_once $pth['folder']['plugins'] . 'forum/classes/BBCode.php';
    $temp = new Forum_BBCode($pth['folder']['plugins'] . 'forum/images/');
?>
<?php if ($cf['xhtml']['endtags'] == 'true'): ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php else: ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<?php endif ?>
<head>
<?php echo tag('link rel="stylesheet" href="'.$pth['file']['stylesheet'].'" type="text/css"') ?>
<?php echo tag('link rel="stylesheet" href="'.$pth['folder']['plugins'].'forum/css/stylesheet.css" type="text/css"') ?>
</head>
<body>
<?php echo $temp->toHtml(stsl($_POST['data'])) ?>
</body>
</html>
<?php

    exit;
}

?>
