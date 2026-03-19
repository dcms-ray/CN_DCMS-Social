<?php
require_once '../sys/inc/start.php';
require_once '../sys/inc/compress.php';
require_once '../sys/inc/sess.php';
require_once '../sys/inc/home.php';
require_once '../sys/inc/settings.php';
require_once '../sys/inc/db_connect.php';
require_once '../sys/inc/ipua.php';
require_once '../sys/inc/fnc.php';
require_once '../sys/inc/user.php';

if (isset($user) && dbresult(dbquery("SELECT COUNT(`id`) FROM `ban` WHERE `razdel` = 'forum' AND `id_user` = '$user[id]' AND (`time` > '$time' OR `view` = '0' OR `navsegda` = '1')"), 0) != 0) {
	header('Location: /user/ban.php?' . session_id());
	require_once '../sys/inc/tfoot.php';
}

$set['title'] = '分享至日记';
require_once '../sys/inc/thead.php';
title();
aut();

$not = dbquery("SELECT * FROM `forum_t` WHERE `id`='" . intval($_GET['id']) . "' LIMIT 1");
if (dbrows($not) == 0) {
	echo "<div class='error'>帖子不存在</div>";
	require_once '../sys/inc/tfoot.php';
}
if (dbresult(dbquery("SELECT COUNT(`id`)FROM `notes` WHERE `id_user`='" . $user['id'] . "' AND `share_id`='" . intval($_GET['id']) . "' AND `share_type`='forum' LIMIT 1"), 0) == 1) {
	echo "<div class='error'>分享成功</div>";
	require_once '../sys/inc/tfoot.php';
} else {
	$notes = dbassoc($not);
	$avtor = user::get_user($notes['id_user']);
	if (isset($_POST['ok'])) {
		dbquery("INSERT INTO `notes`(`id_user`,`name`,`msg`,`share`,`share_text`,`share_id`,`share_id_user`,`share_name`,`time`,`share_type`) values('" . $user['id'] . "','" . text($notes['name']) . "','" . my_esc($_POST['share_text']) . "','1','" . my_esc($notes['text']) . "','" . $notes['id'] . "','" . $notes['id_user'] . "','" . my_esc($notes['name']) . "','" . $time . "','forum')");
		$id = dbinsertid();
		msg('分享成功!');
		header('Location:/plugins/notes/list.php?id=' . $id);
		require_once '../sys/inc/tfoot.php';
	}
	?>

	<div class='nav2'>
		<div class="friends_access_list attach_block mt_0 grey"> <?php echo group($avtor['id']) . " "; ?> <a href="/user/info.php?id=<?php echo $notes['id_user']; ?>"><span style="color:#79358c"><b><?php echo " " . $avtor['nick'] . " "; ?> </b></span></a> : <?php echo '<a href="/forum/' . $notes['id_forum'] . '/' . $notes['id_razdel'] . '/' . $notes['id'] . '/">'; ?>
			<span style="color:#06F;"><?php echo $notes['name']; ?></span></a>
		</div>

		<form method='post' action='share.php?id=<?php echo intval($_GET['id']); ?>'>
			<?php echo $tPanel; ?>
			<textarea name='share_text'></textarea>
			<br/>
			<input type='submit' name='ok' value='分享'>
		</form>
	</div>
	<?php
}
require_once '../sys/inc/tfoot.php';
