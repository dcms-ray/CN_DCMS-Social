<?php
class AuthManager
{
	private Database $db;
	private array $set;
	private const JWT_ALGORITHM = 'HS256';

	public function __construct(Database $db, array $set) {
		$this->db = $db;
		$this->set = $set;
	}

	/**
	 * 检查用户登录状态
	 * @return array
	 */
	public function checkLoginStatus(): array {
		// 优先检查 Session
		if ($this->isSessionValid()) {
			return $this->processSessionLogin();
		}

		// 检查 Cookie 中的 Token
		if ($this->isCookieTokenValid()) {
			return $this->processCookieLogin();
		}

		// 检查 Authorization 头中的 Bearer Token
		if ($this->isBearerTokenValid()) {
			return $this->processBearerTokenLogin();
		}

		return ['status' => false, 'message' => 'No authentication parameters provided'];
	}

	/**
	 * 处理用户登录后的后续操作
	 * @param array $user
	 * @param array $clientDetails
	 * @return array
	 */
	public function processAuthenticatedUser(array $user, array $clientDetails): array {
		// 获取最后在线时间
		$lastOnline = $this->db->query("SELECT ul.last_online
		                                 FROM `user_log` ul
		                                 WHERE ul.id_user = :user_id
		                                     AND ul.ban = 0
		                                 ORDER BY ul.last_online DESC
		                                 LIMIT 1",
									    [':user_id' => $user['id']]
		);

		// 计算活跃时间
		$timeActive = time() - strtotime($lastOnline['last_online']);
		if ($timeActive < 120) {
			$this->db->update(
				'UPDATE user SET time = time + :time_active WHERE id = :user_id LIMIT 1',
				[
					':time_active' => $timeActive,
					':user_id' => $user['id']
				]
			);
		}

		// 更新用户日志
		$this->db->update(
			'UPDATE user_log SET last_online = :last_online, url = :url, ip = :ip WHERE id = :login_id LIMIT 1',
			[
				':last_online' => date('Y-m-d H:i:s'),
				':url' => $_SERVER['SCRIPT_NAME'],
				':ip' => $clientDetails['ip'],
				':login_id' => $user['login_id']
			]
		);

		// 更新用户代理信息
		if (!empty($clientDetails['ua'])) {
			$this->db->update(
				'UPDATE user_log SET ua = :ua WHERE id = :login_id LIMIT 1',
				[
					':ua' => $clientDetails['ua'],
					':login_id' => $user['login_id']
				]
			);
		}

		return ['status' => true, 'data' => $user];
	}

	private function isSessionValid(): bool {
		return isset($_SESSION['id_user'], $_SESSION['login_id']);
	}

	private function isCookieTokenValid(): bool {
		return isset($_COOKIE['auth_token']);
	}

	private function isBearerTokenValid(): bool {
		return !empty($_SERVER['HTTP_AUTHORIZATION']) && strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0;
	}

	private function processSessionLogin(): array {
		$userInfo = $this->getUserInfo($_SESSION['id_user'], $_SESSION['login_id']);
		if ($userInfo['status']) {
			$userInfo['info']['type_input'] = 'session';
			return ['status' => true, 'data' => $userInfo['info']];
		}

		session_unset();
		return ['status' => false, 'message' => 'Session error: ' . $userInfo['message']];
	}

	private function processCookieLogin(): array {
		$userInfo = $this->jwtGetUserInfo($_COOKIE['auth_token']);
		if ($userInfo['status']) {
			$userInfo['info']['type_input'] = 'cookie';
			return ['status' => true, 'data' => $userInfo['info']];
		}

		setcookie('auth_token', '', time() - 3600, '/');
		return ['status' => false, 'message' => 'Cookie error: ' . $userInfo['message']];
	}

	private function processBearerTokenLogin(): array {
		$jwt = preg_split('/\s+/', $_SERVER['HTTP_AUTHORIZATION'])[1];
		$userInfo = $this->jwtGetUserInfo($jwt);
		if ($userInfo['status']) {
			$userInfo['info']['type_input'] = 'authorization';
			return ['status' => true, 'data' => $userInfo['info']];
		}

		return ['status' => false, 'message' => 'JWT invalid: ' . $userInfo['message']];
	}

	private function jwtGetUserInfo(string $jwt): array {
		try {
			$decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($this->set['shif'], self::JWT_ALGORITHM));
		} catch (Exception $e) {
			return ['status' => false, 'message' => 'Failed to decode JWT: ' . $e->getMessage()];
		}

		if ($decoded->exp <= time()) {
			return ['status' => false, 'message' => 'JWT expired'];
		}

		return $this->getUserInfo($decoded->user_id, $decoded->jwt_id);
	}

	private function getUserInfo(int $userId, int $logId): array {
		// 查询用户信息
		$userData = $this->db->query(
			'SELECT * FROM user WHERE id = :id LIMIT 1',
			[':id' => $userId]
		);

		if (!$userData) {
			return ['status' => false, 'message' => 'User does not exist'];
		}

		// 检查登录日志
		$userLog = $this->db->query(
			'SELECT ban FROM user_log WHERE id = :log_id AND id_user = :user_id',
			[
				':log_id' => $logId,
				':user_id' => $userId
			]
		);

		if (!$userLog) {
			return ['status' => false, 'message' => 'Login log not found'];
		}

		if ($userLog['ban'] != 0) {
			return ['status' => false, 'message' => 'Login log is banned'];
		}

		// 更新最后在线时间
		$this->db->update(
			'UPDATE user_log SET last_online = :last_online WHERE id = :id LIMIT 1',
			[
				':last_online' => date('Y-m-d H:i:s'),
				':id' => $logId
			]
		);

		$userData['login_id'] = $logId;
		return [
			'status' => true,
			'info' => $userData
		];
	}
}
