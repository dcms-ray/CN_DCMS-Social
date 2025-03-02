<?php
include_once '../sys/inc/start.php';
include_once '../sys/inc/compress.php';
include_once '../sys/inc/sess.php';
include_once '../sys/inc/home.php';
include_once '../sys/inc/settings.php';
include_once '../sys/inc/db_connect.php';
include_once '../sys/inc/ipua.php';
include_once '../sys/inc/fnc.php';
$show_all=true;
include_once '../sys/inc/user.php';
only_unreg();

// 检查用户是否成功登录
if (isset($_GET['id']) && isset($_GET['pass'])) {
	// 从数据库获取用户信息
	$user = dbassoc(dbquery("SELECT `id`, `pass` FROM `user` WHERE `id` = '" . intval($_GET['id']) . "' LIMIT 1"));

	if ($user && password_verify($_GET['pass'], $user['pass'])) {
		$_SESSION['id_user'] = $user['id'];
		dbquery("INSERT INTO `user_log` (`id_user`, `date`, `ua`, `ip`, `method`) values('$user[id]', '" . date('Y-m-d H:i:s') . "', '$ua' , '$ip', '0')");
	} else {
		$_SESSION['err'] = '用户名或密码不正确';
	}
} elseif (isset($_POST['nick']) && isset($_POST['pass'])) {    // 检查用户是否已经提交登录表单
	// 从数据库获取用户信息
	$user = dbassoc(dbquery("SELECT `id`, `pass` FROM `user` WHERE `nick` = '" . my_esc($_POST['nick']) . "' LIMIT 1"));

	if ($user && password_verify($_POST['pass'], $user['pass'])) {
		$_SESSION['id_user'] = $user['id'];
		$user = user::get_user($user['id']);
		if (isset($_POST['aut_save']) && $_POST['aut_save']) {
			$expiration = time() + 60 * 60 * 24 * 365;
		} else {
			$expiration = time() + 60 * 60 * 2;
		}
		dbquery("INSERT INTO `user_log` (`id_user`, `date`, `expire_date`, `last_online`, `ua`, `ip`, `method`) values('{$user['id']}', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s', $expiration) . "', '" . date('Y-m-d H:i:s') . "', '{$ua}' , '{$ip}', '1')");
		$log_id = dbinsertid();
		$_SESSION['login_id'] = $log_id;

		// 在COOKIE中保存数据
		$payload = array(
			"iat" => time(),
			"exp" => $expiration,
			"jwt_id" => $log_id,
			"user_id" => $user['id'],
			"username" => $_POST['nick']
		);
		$jwt = \Firebase\JWT\JWT::encode($payload, $set['shif'], 'HS256');
		setcookie('auth_token', $jwt, $expiration, '/');
	} else {
		$_SESSION['err'] = '用户名或密码不正确';
	}
} else {
	$_SESSION['err'] = '授权错误';
}

// 检查用户是否登录失败
if (!isset($user)) {
	header('Location: /user/aut.php');
	exit;
}

// 记录用户的 ip
dbquery("UPDATE `user_log` SET `ip` = '{$ip}' WHERE `id` = '{$log_id}' LIMIT 1");

// 记录用户的 ua
if ($ua) dbquery("UPDATE `user_log` SET `ua` = '" . my_esc($ua) . "' WHERE `id` = '{$log_id}' LIMIT 1");

// 难以理解的会话
dbquery("UPDATE `user_log` SET `sess` = '{$sess}' WHERE `id` = '{$log_id}' LIMIT 1");

// 浏览器类型
dbquery("UPDATE `user_log` SET `browser` = '" . ($webbrowser == true ? "web" : "wap") . "' WHERE `id` = '{$log_id}' LIMIT 1");

// 检查相似的昵称
// 一定时间范围内检查是否有多个用户在相同的IP、相同的用户代理和相似的登录时间（10分钟内）之间产生了碰撞，如果有碰撞，则将这两个用户的信息记录在 user_collision 表中
$collision_q = dbquery("SELECT * FROM `user_log` WHERE `last_online` > '" . date("Y-m-d H:i:s", (time() - 600)) . "' AND `ip` = '$ip' AND `ua` = '" . my_esc($ua) . "' AND `id_user` <> '$user[id]'");
while ($collision = dbassoc($collision_q)) {
	if (dbresult(dbquery("SELECT COUNT(*) FROM `user_collision` WHERE (`id_user` = '$user[id]' AND `id_user2` = '$collision[id_user]') OR (`id_user2` = '$user[id]' AND `id_user` = '$collision[id_user]')"), 0) == 0) {
		dbquery("INSERT INTO `user_collision` (`id_user`, `id_user2`, `type`) values('$user[id]', '$collision[id_user]', 'ip_ua_time')");
	}
}

/*
========================================
等级: 0
========================================
*/
if (isset($user) && $user['rating_tmp'] > 1000) {
	// 活动柜台
	$col = $user['rating_tmp']; 
	// 百分比除以百分比
	$col = $col / 1000; 
	// 四舍五入
	$col = intval($col); 
	// 添加% 级别
	dbquery("update `user` set `rating` = '" . ($user['rating'] + $col) . "' where `id` = '$user[id]' limit 1");
	// 通知
	$_SESSION['message'] = "祝贺你！你的活动是值得的 {$col}% 评级!"; 
	// 活动柜台余额计算
	$col = $user['rating_tmp'] - ($col * 1000); 
	// 重新设定
	dbquery("update `user` set `rating_tmp` = '{$col}' where `id` = '{$user['id']}' limit 1");
}
if (isset($_GET['return'])) {
	header('Location: '.urldecode($_GET['return']));
} else {
	header('Location: /user/umenu.php');
}