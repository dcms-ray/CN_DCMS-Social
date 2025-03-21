<?php
// 全局函数，兼容原有接口

/**
 * 执行数据库查询
 * 
 * @param $query SQL 查询语句
 * 
 * @return mixed 返回查询结果 PDOStatement 对象
 */
function dbquery($query, $params = []) {
	global $db;
	return $db->dbquery($query, $params);
}

/**
 * 获取查询结果的行数
 * 
 * @param $result 查询结果资源
 * 
 * @return int 返回查询结果的总行数
 */
function dbrows($result) {
	return $result->rowCount(); // PDOStatement 的 rowCount 方法
}

/**
 * 获取查询结果的特定行和字段值
 * 
 * @param $result 查询结果资源
 * @param $row 要获取的行索引
 * @param $field 要获取的字段索引或字段名称，默认为0
 * 
 * @return mixed 返回查询结果的指定字段值，如果没有找到则返回 null
 */
function dbresult($result, $row, $field = 0) {
	$currentRow = 0;

	// 如果 $field 是数字，使用索引数组（FETCH_NUM）；否则使用关联数组（FETCH_ASSOC）
	$fetchMode = is_numeric($field) ? PDO::FETCH_NUM : PDO::FETCH_ASSOC;

	while ($data = $result->fetch($fetchMode)) {
		if ($currentRow == $row) {
			return isset($data[$field]) ? $data[$field] : null;
		}
		$currentRow++;
	}
	return null;
}

function dbarray($result) {
	return $result->fetch(PDO::FETCH_BOTH); // 返回索引和关联数组
}

function dbassoc($result) {
	return $result->fetch(PDO::FETCH_ASSOC); // 返回关联数组
}

function dbinsertid() {
	global $db;
	return $db->lastInsertId();
}

/**
 * 优化数据库表
 * 
 * 遍历所有数据库表，并对每个表执行优化操作
 */
function db_optimize() {
	global $db;

	// 获取所有表的列表
	$tables = $db->queryAll('SHOW TABLES');
	
	// 遍历并优化每个表
	foreach ($tables as $table) {
		$tableName = reset($table); // 获取表名（第一个键值）
		$db->dbquery("OPTIMIZE TABLE `$tableName`");
	}
}


// 初始化查询计数器和时间变量（与原代码保持一致）
$query_number = 0;
$tpassed = 0;
