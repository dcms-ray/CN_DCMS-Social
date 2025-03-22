<?php
class Database {
	private $pdo;

	public function __construct(array $config) {
		try {
			$dsn = sprintf("%s:host=%s;dbname=%s", $config['driver'] ?? 'mysql', $config['host'], $config['dbname']);
			$this->pdo = new PDO($dsn, $config['username'], $config['password']);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			if (isset($config['timezone'])) {
				$this->pdo->exec("SET time_zone = '" . $config['timezone'] . "';");
			}
		} catch (PDOException $e) {
			throw new Exception("Database connection failed: " . $e->getMessage());
		}
	}

	private function executeStatement($sql, $params = []) {
		try {
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($params);
			return $stmt;
		} catch (PDOException $e) {
			throw new Exception("Statement execution failed: " . $e->getMessage());
		}
	}

	public function query($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC) {
		$result = $this->executeStatement($sql, $params)->fetch($fetchMode);
		return $result === false ? null : $result;
	}

	public function queryAll($sql, $params = [], $fetchMode = PDO::FETCH_ASSOC) {
		return $this->executeStatement($sql, $params)->fetchAll($fetchMode);
	}

	public function insert($sql, $params = []) {
		$this->executeStatement($sql, $params);
		return $this->pdo->lastInsertId();
	}

	public function update($sql, $params = []) {
		return $this->executeStatement($sql, $params)->rowCount() > 0;
	}

	public function delete($sql, $params = []) {
		return $this->executeStatement($sql, $params)->rowCount() > 0;
	}

	public function beginTransaction() {
		return $this->pdo->beginTransaction();
	}

	public function commit() {
		return $this->pdo->commit();
	}

	public function rollBack() {
		return $this->pdo->rollBack();
	}
}

$db = new Database([
	'driver' => 'mysql',
	'host' => $set['sql_host'],
	'dbname' => $set['sql_db_name'],
	'username' => $set['sql_user'],
	'password' => $set['sql_pass'],
	'timezone' => date('P')
]);