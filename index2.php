<?php
// DCMS 核心科技😎😎😋，屏蔽报错就没有错误啦
// if (function_exists('error_reporting')) error_reporting(0); // 禁用错误显示

// 将脚本执行限制为 60 秒
//if (function_exists('set_time_limit')) set_time_limit(60);
if (function_exists('ini_set')) {
	//ini_set('display_errors', false); // 禁用错误显示
	//ini_set('register_globals', false); // 消除全局变量
	ini_set('session.use_cookies', true); // 使用 Cookie 进行会话
	//ini_set('session.use_trans_sid', true); // 使用 URL 传输会话
	ini_set('arg_separator.output', "&amp;"); // URL 中的变量分隔符（用于与 XML 匹配）

}

list($msec, $sec) = explode(chr(32), microtime()); // 脚本启动时间
$conf['headtime'] = $sec + $msec;

// ========================================================

// 引入第三方库
require_once __DIR__ . '/vendor/autoload.php';

// ========================================================

// 初始化设置
$settings = \GuGuan123\dcms\Core\Settings::getInstance();

// 检查是否已安装
if (!$settings->isInstalled()) {
	if (file_exists(H . 'install/index.php') && isset($current_page) && $current_page == 'index') {
		header('Location: install/');
		exit;
	} else {
		http_response_code(500);
		echo 'sys/dat/settings.php 消失了' . PHP_EOL;
		exit;
	}
}

$set = $settings->getAll();

// 错误显示处理
if ($settings->get('show_err_php')) {
	error_reporting(E_ALL);
	ini_set('display_errors', true);
}

// 临时设置
$set['web'] = false;

// 解析 User-Agent 检查设备类型是否为 PC
if (!empty($_SERVER["HTTP_USER_AGENT"]) && !(new \Detection\MobileDetect())->isMobile()) {
	$webbrowser = true;
} else {
	$webbrowser = false;
}

// ========================================================

try {
	\GuGuan123\dcms\Core\Database::getInstance([
		'driver'   => 'mysql',
		'host'     => $settings->get('sql_host'),
		'dbname'   => $settings->get('sql_db_name'),
		'username' => $settings->get('sql_user'),
		'password' => $settings->get('sql_pass'),
		'timezone' => date('P')
	]);
} catch (Exception $e) {
	// 连接失败时，输出错误信息并终止脚本执行
	http_response_code(506);
	die("Error: " . $e->getMessage());
}

// ========================================================

$ua = (new GuGuan123\dcms\Core\ClientDetails())->getUserAgent();
$ip = (new GuGuan123\dcms\Core\ClientDetails())->getClientIp();

// ========================================================

// 检查登录状态
$authManager = \GuGuan123\dcms\Services\AuthManager::getInstance();
$authManagerCheckStatusResult = $authManager->checkStatus();
if ($authManagerCheckStatusResult['status']) {
	$user = $authManagerCheckStatusResult['data'];
	$processAuthenticatedResult = $authManager->processAuthenticatedUser($user['login_id']);
	// 处理已认证用户
}

// ========================================================

// 载入路由配置文件
$router = new \GuGuan123\dcms\Core\Router();

// 路由表
$router->get('/', 'Home@index');

// 获取原始的 PATH 路径
$uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// 除脚本文件名
if (strpos($uriPath, $_SERVER['SCRIPT_NAME']) === 0) $uriPath = substr($uriPath, strlen($_SERVER['SCRIPT_NAME']));

// 执行路由
$router->dispatch($_SERVER['REQUEST_METHOD'], '/' . ltrim($uriPath, '/'));
