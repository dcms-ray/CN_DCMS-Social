<?php
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