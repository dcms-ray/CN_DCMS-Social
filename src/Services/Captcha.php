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

namespace GuGuan123\dcms\Services;

class Captcha
{
	public function __construct(private array $set, private \GuGuan123\dcms\Database $db) {
		$this->set = $set;
		$this->db = $db;
	}

	// 获取 Captcha URL 和 Captcha token
	public function createToken() {
		// 生成5位验证码
		$captcha_value = rand(10000, 99999);
		$expiry_time = time() + 600;  // 设置过期时间为 10 分钟后

		// 生成随机的 iv（初始化向量）
		$iv = openssl_random_pseudo_bytes(16);

		// 给验证码添加过期时间，加密后进行 base64 编码，与 base64 编码过的 iv 拼装在一起作为 captcha_token
		$token = rtrim(strtr(base64_encode(openssl_encrypt($captcha_value . '.' . (time() + 600), 'aes-256-cbc', $this->set['shif'], 0, $iv)), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode($iv), '+/', '-_'), '=');

		// 插入数据库，保存生成的 token，状态为 'unused'
		$this->db->insert("INSERT INTO captcha_tokens (captcha_token, expires_at, status) VALUES (?, FROM_UNIXTIME(?), 'unused')", [
			$token,
			$expiry_time
		]);

		return [
			'status'  => 'success',
			'token'   => $token,
			'url'     => "/captcha.php?captcha_token={$token}",
			'expires' => $expiry_time
		];
	}

	// 核对验证码
	function validateToken($user_input, $captcha_token) {
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
		$decrypted_captcha_token = explode('.', openssl_decrypt(base64_decode(strtr($token_parts[0], '-_', '+/')), 'aes-256-cbc', $this->set['shif'], 0, base64_decode(strtr($token_parts[1], '-_', '+/'))));
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
		$token_record = $this->db->query("SELECT * FROM captcha_tokens WHERE captcha_token = ? AND status = 'unused'", [$captcha_token]);

		if (isset($token_record['captcha_token']) && $token_record['captcha_token'] != $captcha_token) {
			// captcha_token 无效或已使用
			return ['status' => 'error', 'message' => 'captcha_token invalid or used'];
		}
		// 验证解密后的验证码是否正确（与用户输入的验证码比较）
		if ($decrypted_captcha_token[0] === $user_input) {
			// 验证通过，更新 token 状态为 'used'
			$this->db->update("UPDATE captcha_tokens SET status = 'used' WHERE captcha_token = ?", [$captcha_token]);
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
}