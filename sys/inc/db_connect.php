<?php
require_once __DIR__ . '/classes/database.php'; // 引入数据库操作类

// 初始化全局变量
try {
	$db = new Database($set['sql_host'], $set['sql_db_name'], $set['sql_user'], $set['sql_pass']);
} catch (Exception $e) {
	// 连接失败时，输出错误信息并终止脚本执行
	http_response_code(506);
	die("Error: " . $e->getMessage());
}

if ($set['use_mysqli'] == 1) {
	require_once __DIR__ . '/parties/mysqli_db.php'; // 引入 MySQL 数据库操作类
} else {
	require_once __DIR__ . '/parties/compatible_db.php'; // 引入兼容数据库操作类
}