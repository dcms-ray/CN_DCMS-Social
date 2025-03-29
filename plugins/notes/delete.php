<?php
include_once '../../sys/inc/start.php';
include_once '../../sys/inc/compress.php';
include_once '../../sys/inc/sess.php';
include_once '../../sys/inc/home.php';
include_once '../../sys/inc/settings.php';
include_once '../../sys/inc/db_connect.php';
include_once '../../sys/inc/ipua.php';
include_once '../../sys/inc/fnc.php';
include_once '../../sys/inc/user.php';

// 删除日记
if (isset($_GET['id']) && dbresult(dbquery("SELECT COUNT(*) FROM `notes` WHERE `id` = '" . intval($_GET['id']) . "'"), 0) == 1) {
	$post=dbassoc(dbquery("SELECT * FROM `notes` WHERE `id` = '" . intval($_GET['id']) . "' LIMIT 1"));
	$ank=dbassoc(dbquery("SELECT * FROM `user` WHERE `id` = $post[id_user] LIMIT 1"));
	if (isset($user) && (user_access('notes_delete') || $user['id'] == $ank['id'])) {
		dbquery("DELETE FROM `notes` WHERE `id` = '$post[id]'");
		dbquery("DELETE FROM `notes_count` WHERE `id_notes` = '$post[id]'");
		dbquery("DELETE FROM `notes_komm` WHERE `id_notes` = '$post[id]'");
		dbquery("DELETE FROM `mark_notes` WHERE `id_list` = '$post[id]'");
		$_SESSION['message'] = '日记成功删除';
		header("Location: index.php?" . session_id());
		exit;
	}
} else {
	echo output_text('操作请求无效,请重新尝试');
}

// 删除评论
if (isset($_GET['komm']) && dbresult(dbquery("SELECT COUNT(*) FROM `notes_komm` WHERE `id` = '" . intval($_GET['komm']) . "'"), 0) == 1) {
	$post = dbassoc(dbquery("SELECT * FROM `notes_komm` WHERE `id` = '".intval($_GET['komm'])."' LIMIT 1"));
	$notes = dbassoc(dbquery("SELECT * FROM `notes` WHERE `id` = '$post[id_notes]' LIMIT 1"));
	$ank = dbassoc(dbquery("SELECT * FROM `user` WHERE `id` = $notes[id_user] LIMIT 1"));
	if (isset($user) && (user_access('notes_delete') || $user['id'] == $ank['id'] || $user['id'] == $post['id_user'])) {
		dbquery("DELETE FROM `notes_komm` WHERE `id` = '$post[id]'");
		$_SESSION['message'] = '评论成功删除';
		header("Location: " . htmlspecialchars($_SERVER['HTTP_REFERER']));
		exit;
	} else {
		echo output_text('操作请求无效，你没有权限删除此评论');
	}
} else {
	echo output_text('操作请求无效');
}

// 删除类别
if (isset($_GET['dir']) && dbresult(dbquery("SELECT COUNT(*) FROM `notes_dir` WHERE `id` = '" . intval($_GET['dir']) . "'"), 0) == 1) {
	if (isset($user) && user_access('notes_delete')) {
		$q = dbquery("SELECT * FROM `notes_dir` WHERE `id` = '" . intval($_GET['dir']) . "' LIMIT 1");
		while ($post = dbassoc($q)) {
			$notes = dbassoc(dbquery("SELECT * FROM `notes` WHERE `id_dir` = '$post[id]'"));
			dbquery("DELETE FROM `notes_count` WHERE `id_notes` = '$notes[id]'");
			dbquery("DELETE FROM `notes_komm` WHERE `id_notes` = '$notes[id]'");
			dbquery("DELETE FROM `mark_notes` WHERE `id_list` = '$notes[id]'");
		}
		$post = dbassoc(dbquery("SELECT * FROM `notes_dir` WHERE `id` = '" . intval($_GET['dir']) . "' LIMIT 1"));
		dbquery("DELETE FROM `notes_count` WHERE `id_notes` = '$notes[id]'");
		dbquery("DELETE FROM `notes_komm` WHERE `id_notes` = '$notes[id]'");
		dbquery("DELETE FROM `mark_notes` WHERE `id_list` = '$notes[id]'");
		dbquery("DELETE FROM `notes` WHERE `id_dir` = '$post[id]'");
		dbquery("DELETE FROM `notes_dir` WHERE `id` = '$post[id]'");
		$_SESSION['message'] = '类别已成功删除';
		header("Location: " . htmlspecialchars($_SERVER['HTTP_REFERER']));
		exit;
	} else {
		echo output_text('操作请求无效,请重新尝试');
	}
} else {
	echo output_text('操作请求无效,请重新尝试');
}
