<?php
/*
 * MIT License
 * 
 * Copyright (c) 2025 GuGuan123
 * 
 * 本软件基于 MIT 许可证发布。具体许可条款如下：
 * 
 * 允许在本软件及其附带文档文件（以下简称“软件”）的基础上进行修改、复制、分发及/或销售，
 * 且在提供软件的副本时，需附上此许可证声明和版权声明。
 * 
 * 本软件按“原样”提供，不作任何形式的明示或暗示的担保，包括但不限于对适销性、适合某一特定用途的担保。
 * 在任何情况下，无论是在合同诉讼、侵权或其他诉讼中，作者或版权持有者对因使用本软件或其他交易的结果
 * 所产生的任何索赔、损害或其他责任不承担任何责任。
 * 
 * 你可以在 https://choosealicense.com/licenses/mit/ 查看详细的 MIT 原始许可证条款。
 */

require_once 'sys/inc/start.php';
require_once 'sys/inc/compress.php';
require_once 'sys/inc/sess.php';
require_once 'sys/inc/home.php';
require_once 'sys/inc/settings.php';
require_once 'sys/inc/db_connect.php';
require_once 'sys/inc/ipua.php';
require_once 'sys/inc/fnc.php';
require_once 'sys/inc/user.php';

// 检测是否启用了 API
if (empty($set['api']) || $set['api'] == '0') {
	http_response_code(403);
	header('Content-type: application/json');
	die(json_encode([
		'status' => 'error',
		'error' => 'The administrator turned off the API'
	]));
}

// 核对验证码
function validateCaptchaToken($user_input, $captcha_token) {
	global $set;
	global $db;
	// 解析 captcha_token
	$token_parts = explode('.', $captcha_token);
	if (count($token_parts) !== 2) {
		// captcha_token 格式错误
		return [
			'status' => 'error',
			'message' => 'captcha_token format error'
		];
	}

	// 解密并拆分 Token
	$decrypted_captcha_token = explode('.', openssl_decrypt(base64_decode($token_parts[0]), 'aes-256-cbc', $set['shif'], 0, base64_decode($token_parts[1])));
	if (count($decrypted_captcha_token) !== 2) {
		return [
			'status' => 'error',
			'message' => 'captcha_token format error'
		];
	} elseif ($decrypted_captcha_token[1] < time()) {
		return [
			'status' => 'error',
			'message' => 'captcha_token expired'
		];
	}
	// 查询数据库，检查 token 是否存在且未使用
	$token_record = $db->query("SELECT * FROM captcha_tokens WHERE captcha_token = ? AND status = 'unused'", [$captcha_token]);

	if (isset($token_record['captcha_token']) && $token_record['captcha_token'] != $captcha_token) {
		// captcha_token 无效或已使用
		return ['status' => 'error', 'message' => 'captcha_token invalid or used'];
	}
	// 验证解密后的验证码是否正确（与用户输入的验证码比较）
	if ($decrypted_captcha_token[0] === $user_input) {
		// 验证通过，更新 token 状态为 'used'
		$db->update("UPDATE captcha_tokens SET status = 'used' WHERE captcha_token = ?", [$captcha_token]);
		return [
			'status' => 'success'
		];
	} else {
		// 验证失败
		return [
			'status' => 'error',
			'message' => 'incorrect verification code'
		];
	}
}

// 处理登录
if (isset($_GET['action']) && $_GET['action'] == 'login') {	// 检查用户是否已经提交登录表单
	if (isset($_POST['nick']) && isset($_POST['password'])) {
		// 使用参数化查询验证用户名和密码
		$user = $db->query("SELECT `id`, `pass` FROM `user` WHERE `nick` = :nick LIMIT 1", ['nick' => $_POST['nick']]);

		if ($user && password_verify($_POST['password'], $user['pass'])) {	// 比较密码
			// 登录成功

			// 选择了“记住我”
			if (isset($_POST['aut_save']) && $_POST['aut_save']) {
				$expiration = time() + 60 * 60 * 24 * 365;
			} else {
				$expiration = time() + 3600 * 24;
			}


			// 记录登录日志
			$logQuery = "INSERT INTO `user_log` (`id_user`, `date`, `expire_date`, `last_online`, `ua`, `ip`, `method`) VALUES (:id_user, :date, :expire_date, :last_online, :ua, :ip, '1')";
			// 设置默认值：如果没有指定 `last_online`，就用 `date`
			$log_id = $db->insert($logQuery, [
				'id_user' => $user['id'],
				'date' => date('Y-m-d H:i:s'),						// 当前时间
				'expire_date' => date('Y-m-d H:i:s', $expiration),	// 转换过期时间戳为 MySQL 时间格式
				'last_online' => date('Y-m-d H:i:s'),				// 如果没有指定 last_online，就设置为 date 字段的当前时间
				'ua' => $clientDetails['ua'],						// 从客户端获取 User-Agent
				'ip' => $clientDetails['ip']						// 从客户端获取 IP 地址
			]);

			// 在 session 存储用户ID与登录记录ID
			$_SESSION['id_user'] = $user['id'];
			$_SESSION['login_id'] = $log_id;

			$payload = array(
				"iat" => time(),
				"exp" => $expiration,
				"jwt_id" => $log_id,
				"user_id" => $user['id'],
				"username" => $_POST['nick']
			);

			$jwt = \Firebase\JWT\JWT::encode($payload, $set['shif'], 'HS256');

			setcookie('auth_token', $jwt, $expiration, '/');

			// 设置响应为成功
			$response['status'] = 'success';
			$response['message'] = 'login successful';
			$response['data']['user_id'] = $user['id'];
			$response['data']['token'] = $jwt;
		} else {
			// 登录失败
			http_response_code(403);
			$response['status'] = 'error';
			$response['message'] = 'incorrect username or password';
		}
	} else {
		http_response_code(403);
		$response['status'] = 'error';
		$response['message'] = 'missing required parameters';
	}



} elseif (isset($_GET['action']) && $_GET['action'] == 'logout') {
	// 退出登录
	setcookie('auth_token', '', time() - 3600, '/');
	session_destroy();
	$response['status'] = 'success';



} elseif (isset($_GET['action']) && $_GET['action'] == 'register') {
	// 注册
	try {
		if ($set['reg_select'] == 'close') {
			// 管理员已关闭注册
			throw new Exception('registration is closed');
		}

		// 验证验证码
		if (!isset($_POST['captcha']) || !isset($_POST['captcha_token'])) {
			throw new Exception('verification code is required');
		}
	
		// 优化验证码验证逻辑
		$validateCaptchaToken = validateCaptchaToken($_POST['captcha'], $_POST['captcha_token']);
		if ($validateCaptchaToken['status'] != 'success') {
			throw new Exception($validateCaptchaToken['message']);
		}
	
		// 检查必要参数
		if (!isset($_POST['reg_nick'])) {
			// 缺少昵称参数
			throw new Exception('nick is missing');
		}
		if (!isset($_POST['password'])) {
			// 缺少密码参数
			throw new Exception('password is missing');
		}
	
		// 先检查邮箱（如果启用了邮件验证）
		if ($set['reg_select'] == 'open_mail' && empty($_POST['email'])) {
			throw new Exception('email is missing');
		}
		if (isset($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception('invalid email address');
		}

		// 检查昵称
		if (!preg_match("#^([A-Za-z0-9\-\_\ ])+$#", $_POST['reg_nick'])) {
			// 昵称含有非法字符
			throw new Exception('invalid characters in nick');
		}
		$nickLength = strlen2($_POST['reg_nick']);
		if ($nickLength < 3) throw new Exception('nick too short');
		if ($nickLength > 32) throw new Exception('nick too long');

		// 检查用户昵称和电子邮件是否已存在
		if ($db->query("SELECT COUNT(*) FROM `user` WHERE `nick` = ?", [$_POST['reg_nick']])['COUNT(*)'] > 0) {
			throw new Exception('nick already registered');
		} elseif (isset($_POST['email']) && $db->query("SELECT COUNT(*) FROM `reg_mail` WHERE `mail` = ?", [$_POST['email']])['COUNT(*)'] != 0) {
			throw new Exception('email already registered');
		}

		// 检查密码
		$passwordLength = strlen2($_POST['password']);
		if ($passwordLength < 6) throw new Exception('password too short');
		if ($passwordLength > 32) throw new Exception('password too long');

		// 如果开启了邮箱验证，创建激活码
		if ($set['reg_select'] == 'open_mail') $activation = md5(random_bytes(16));

		// 注册用户
		$id_reg = $db->insert("INSERT INTO `user` (`nick`, `pass`, `date_reg`, `pol`, `activation`, `email`) VALUES (?, ?, ?, ?, ?, ?)", [
			$_POST['reg_nick'],
			password_hash($_POST['password'], PASSWORD_DEFAULT),
			time(),
			intval((isset($_POST['pol']) && ($_POST['pol'] == '1')) ? 1 : 0),
			($set['reg_select'] == 'open_mail') ? $activation : NULL,
			$_POST['email'] ?? null
		]);

		// 邮件激活逻辑
		if ($set['reg_select'] == 'open_mail') {
			$subject = "帐户激活";
			$regmail = "你好！ {$_POST['reg_nick']}<br />
						要激活您的帐户，请点击链接:<br />
						<a href='" . get_http_type() . "://{$_SERVER['HTTP_HOST']}/user/reg.php?id=$id_reg&amp;activation=$activation'>点击激活帐户</a><br />
						如果帐户在24小时内未激活，它将被删除。<br />
						真诚的，网站管理团队";


			// 调用封装的发送邮件函数
			$emailResult = sendEmail($subject, $regmail, $_POST['email'], $_POST['reg_nick']);

			if ($emailResult['status'] == 'success') {
				// 如果邮件发送成功
				$response['status'] = 'success';
				$response['data']['user_id'] = $id_reg;
				$response['message'] = "verification email sent";
			} else {
				// 如果邮件发送失败
				$response['status'] = 'error';
				$response['message'] = $emailResult['message'];
			}
		} else {
			// 如果没有开启邮箱验证，直接注册
			$response['message'] = 'registration successful';
			$response['data']['user_id'] = $id_reg;
			$response['status'] = 'success';
		}
	} catch (Exception $e) {
		$response['status'] = 'error';
		$response['message'] = $e->getMessage();
	}


} elseif (isset($_GET['action']) && $_GET['action'] == 'get_captcha_url') {
	// 获取 Captcha URL 和 Captcha token

	// 生成5位验证码
	$captcha_value = rand(10000, 99999);
	$expiry_time = time() + 600;  // 设置过期时间为 10 分钟后

	// 生成随机的 iv（初始化向量）
	$iv = openssl_random_pseudo_bytes(16);

	$response['status'] = 'success';
	// 给验证码添加过期时间，加密后进行 base64 编码，与 base64 编码过的 iv 拼装在一起作为 captcha_token
	$response['captcha_token'] = base64_encode(openssl_encrypt($captcha_value . '.' . (time() + 600), 'aes-256-cbc', $set['shif'], 0, $iv)) . '.' . base64_encode($iv);
	// 生成验证码图片 URL
	$response['captcha_url'] = "/captcha.php?captcha_token={$response['captcha_token']}";

	// 插入数据库，保存生成的 token，状态为 'unused'
	$db->insert("INSERT INTO captcha_tokens (captcha_token, expires_at, status) VALUES (?, FROM_UNIXTIME(?), 'unused')", [
		$response['captcha_token'],
		$expiry_time
	]);


} elseif (isset($_GET['action']) && $_GET['action'] == 'activation-account') {
	// 激活账号

	if ($set['reg_select'] == 'close') {
		$response['status'] = 'error';
		$response['message'] = "Registration is closed";
	} elseif (isset($_GET['id']) && isset($_GET['activation'])) {
		if ($db->query("SELECT COUNT(*) FROM `user` WHERE `id` = :id AND `activation` = :activation", [':id' => intval($_GET['id']), ':activation' => $_GET['activation']])['COUNT(*)'] == 1) {
			// 更新激活状态
			$db->update("UPDATE `user` SET `activation` = NULL WHERE `id` = :id LIMIT 1", [':id' => intval($_GET['id'])]);
	
			// 获取用户信息
			$user = $db->query("SELECT * FROM `user` WHERE `id` = :id LIMIT 1", [':id' => intval($_GET['id'])]);
	
			// 插入激活邮件记录
			$db->insert("INSERT INTO `reg_mail` (`id_user`, `mail`) VALUES (:id_user, :mail)", [
				':id_user' => $user['id'],
				':mail' => $user['email']
			]);
	
			// 显示激活成功消息并设置会话
			$response['status'] = 'success';
			$response['message'] = "account activated";
		}
	} else {
		$response['status'] = 'error';
		$response['message'] = 'missing parameters';
	}


} elseif (isset($_GET['action']) && $_GET['action'] == 'forgot-password') {
	// 忘记密码
	if (isset($_POST['nick']) && isset($_POST['email']) && isset($_POST['captcha']) && isset($_POST['captcha_token'])) {
		$result = $db->query("SELECT COUNT(*) FROM `user` WHERE `nick` = :nick", [':nick' => $_POST['nick']]);

		if ($result && $result['COUNT(*)'] == 1) {
			$result = $db->query("SELECT COUNT(*) FROM `user` WHERE `nick` = :nick AND `email` = :email", [
				':nick' => $_POST['nick'],
				':email' => $_POST['email']
			]);
			if ($result && $result['COUNT(*)'] == 1) {
				// 生成链接Token
				$token = bin2hex(random_bytes(32));
				// 插入数据库，存储 token 和创建时间
				$db->query("INSERT INTO `password_reset_tokens` (`user_id`, `token`) VALUES (:user_id, :token)", [
					':user_id' => $userId,
					':token' => $token
				]);

				$user2 = $db->query("SELECT * FROM `user` WHERE `nick` = :nick LIMIT 1", [':nick' => $_POST['nick']]);
				$subject = "密码恢复";
				$regmail = "你好！ $user2[nick]<br />
							您已激活密码恢复<br />
							要设置新密码，请点击链接:<br />
							<a href='" . get_http_type() . "://{$set['hostname']}/user/pass.php?id={$user2['id']}&amp;token={$token}'>" . get_http_type() . "://{$set['hostname']}/user/pass.php?id={$user2['id']}&amp;token={$token}</a><br />
							此链接有效，直到您的用户名下的第一个授权({$user2['nick']})<br />真诚的，网站管理<br />";

				// 调用封装的发送邮件函数
				$emailResult = sendEmail($subject, $regmail, $user2['email'], $user2['nick']);

				if ($emailResult['status'] == 'success') {
					// 如果邮件发送成功
					$response['status'] = 'success';
					$response['message'] = "password reset email sent";
				} else {
					// 如果邮件发送失败
					$response['status'] = 'error';
					$response['message'] = $emailResult['message'];
				}
			} else {
				$response['status'] = 'error';
				$response['message'] = 'invalid email address';
			}
		} else {
			$response['status'] = 'error';
			$response['message'] = 'nick not found';
		}
	} else {
		$response['status'] = 'error';
		$response['message'] = 'missing parameters';
	}


} else {
	// 检查登录状态
	if (isset($user)) {
		$response['status'] = 'success';
		$response['message'] = "Hello {$user['nick']}";
	} else {
		$response['status'] = 'error';
	}
}

header('Content-type: application/json');
echo json_encode($response);