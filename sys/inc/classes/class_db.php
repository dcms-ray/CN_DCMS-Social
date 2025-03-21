<?php
/**
 * Database 类用于简化与 MySQL 数据库的交互。
 * 通过 PDO (PHP Data Objects) 提供的 API 提供常见的数据库操作方法，
 * 包括查询单条记录、查询多条记录、插入、更新和删除操作。
 * 
 * 使用示例：
 * 
 * // 创建数据库连接
 * $db = new Database('localhost', 'test_db', 'root', 'password');
 * 
 * // 查询单条记录
 * $result = $db->query('SELECT * FROM users WHERE id = ?', [1]);
 * print_r($result);
 * 
 * // 查询多条记录
 * $results = $db->queryAll('SELECT * FROM users');
 * print_r($results);
 * 
 * // 插入新记录并获取插入的 ID
 * $insertId = $db->insert('INSERT INTO users (name, email) VALUES (?, ?)', ['John Doe', 'john@example.com']);
 * echo "Inserted ID: " . $insertId;
 * 
 * // 更新记录
 * $updated = $db->update('UPDATE users SET email = ? WHERE id = ?', ['newemail@example.com', 1]);
 * echo $updated ? 'Update successful' : 'Update failed';
 * 
 * // 删除记录
 * $deleted = $db->delete('DELETE FROM users WHERE id = ?', [1]);
 * echo $deleted ? 'Delete successful' : 'Delete failed';
 */
class Database {
	// PDO 实例，负责与数据库的实际连接
	private $pdo;

	/**
	 * 构造函数，用于建立数据库连接
	 * 
	 * @param string $host 数据库主机地址
	 * @param string $dbname 数据库名
	 * @param string $username 数据库用户名
	 * @param string $password 数据库密码
	 * 
	 * 构造函数会在类实例化时尝试连接数据库，若连接失败则抛出异常并终止执行。
	 */
	public function __construct($host, $dbname, $username, $password) {
		try {
			// 创建 PDO 实例并进行数据库连接
			$this->pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
			
			// 设置 PDO 错误模式为异常
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			// 设置默认的查询结果获取模式为关联数组
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

			// 设置数据库会话时区为为 PHP 时区
			$this->pdo->exec("SET time_zone = '" . date('P') . "';");
		} catch (PDOException $e) {
			// 连接失败时，输出错误信息并终止脚本执行
			http_response_code(506);
			die(json_encode([
				'status' => 'error',
				'error' => "Database connection failed: " . $e->getMessage()
			]));
		}
	}

	/**
	 * 执行查询并返回单个结果。
	 * 
	 * @param string $sql SQL 查询语句
	 * @param array $params 查询时的参数，默认为空数组
	 * 
	 * @return mixed 返回查询结果，如果没有结果则返回 false
	 */
	public function query($sql, $params = []) {
		$stmt = $this->pdo->prepare($sql);	// 准备 SQL 语句
		$stmt->execute($params);			// 执行查询，传入参数
		return $stmt->fetch();				// 获取单行结果
	}

	/**
	 * 执行查询并返回多个结果。
	 * 
	 * @param string $sql SQL 查询语句
	 * @param array $params 查询时的参数，默认为空数组
	 * 
	 * @return array 返回查询结果的数组，如果没有结果则返回空数组
	 */
	public function queryAll($sql, $params = []) {
		$stmt = $this->pdo->prepare($sql);	// 准备 SQL 语句
		$stmt->execute($params);			// 执行查询，传入参数
		return $stmt->fetchAll();			// 获取所有结果
	}

	/**
	 * 插入数据到数据库，并返回插入的记录的 ID。
	 * 
	 * @param string $sql 插入数据的 SQL 语句
	 * @param array $params 插入时的参数，默认为空数组
	 * 
	 * @return string 返回插入数据的最后插入 ID
	 */
	public function insert($sql, $params = []) {
		$stmt = $this->pdo->prepare($sql);	// 准备 SQL 语句
		$stmt->execute($params);			// 执行插入操作，传入参数
		return $this->pdo->lastInsertId();	// 返回最后插入记录的 ID
	}

	/**
	 * 更新数据库中的数据。
	 * 
	 * @param string $sql 更新数据的 SQL 语句
	 * @param array $params 更新时的参数，默认为空数组
	 * 
	 * @return bool 返回执行成功与否，成功则返回 true，失败返回 false
	 */
	public function update($sql, $params = []) {
		$stmt = $this->pdo->prepare($sql);	// 准备 SQL 语句
		return $stmt->execute($params);		// 执行更新操作
	}

	/**
	 * 删除数据库中的数据。
	 * 
	 * @param string $sql 删除数据的 SQL 语句
	 * @param array $params 删除时的参数，默认为空数组
	 * 
	 * @return bool 返回执行成功与否，成功则返回 true，失败返回 false
	 */
	public function delete($sql, $params = []) {
		$stmt = $this->pdo->prepare($sql);	// 准备 SQL 语句
		return $stmt->execute($params);		// 执行删除操作
	}
}

// 初始化全局变量
$db = new Database($set['mysql_host'], $set['mysql_db_name'], $set['mysql_user'], $set['mysql_pass']);