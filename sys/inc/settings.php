<?php
// 加载网站设置
function getSet() {
	$set = array();
	$set_default = array();
	$set_dynamic = array();
	$set_replace = array();

	// 正在加载默认设置。消除未定义变量的缺失
	$default = parse_ini_file(__DIR__ . '/../dat/default.ini', true);
	$set_default = $default['DEFAULT'];
	$set_replace = $default['REPLACE'];

	// 检查 install 目录是否存在，如果存在就转跳到引擎安装界面
	if (file_exists(__DIR__ . '/../dat/settings.php')) {
		$set_dynamic = require_once(__DIR__ . '/../dat/settings.php');
	} elseif (file_exists(__DIR__ . '/../../install/index.php')) {
		header('Location: /install/');
		exit;
	}

	return array_merge($set_default, $set_dynamic, $set_replace);
}

$set = getSet();
if ($set['show_err_php']) {
	error_reporting(E_ALL); // 启用错误显示
	ini_set('display_errors', true); // 启用错误显示
}
$set['web'] = false;
if (empty($set['hostname'])) {
	$set['hostname'] = filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL);
}

// 解析 User-Agent 检查设备类型是否为 PC
if (!empty($_SERVER["HTTP_USER_AGENT"]) && !(new Detection\MobileDetect())->isMobile()) {
	$webbrowser = true;
} else {
	$webbrowser = false;
}
