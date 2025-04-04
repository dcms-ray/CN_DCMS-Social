<?php

// 定义常量 H，如果未定义，则设置为当前文件的上两级目录路径加上斜杠
if (!defined('H')) define("H", dirname(dirname(__DIR__)) . "/");

// 定义常量 I，如果未定义，则设置为空字符串
if (!defined('I')) define("I", "");

// 定义常量 REPLACE，如果未定义，则设置为 H 目录下的 "replace/" 子目录
if (!defined('REPLACE')) define("REPLACE", H . "replace/");

// 扫描 H 目录下的 "sys/inc" 子目录，获取文件列表（不包括子目录）
$includes = scandir(H . "sys/inc", 0);

// 检查当前文件是否存在并执行相关逻辑
check_file(__FILE__);

// 遍历 $includes 数组中的每个文件
foreach ($includes as $file) {
	// 构造文件的相对路径
	$file_path = "sys/inc/" . $file;
	// 将文件名转换为大写常量名，去除 ".php" 后缀
	$file_constant = strtoupper(str_replace(".php", "", $file));
	// 如果该常量尚未定义，则进行定义
	if (!defined(strtoupper($file_constant))) {
		// 检查是否启用了替换功能（通过 setget 函数）
		if (setget('replace', 1) == 1) {
			// 如果替换目录中存在该文件，则定义常量为替换路径
			if (file_exists(REPLACE . $file_path)) define($file_constant, REPLACE . $file_path);
			// 否则定义常量为原始路径
			else define($file_constant, H . $file_path);
		} else {
			// 未启用替换功能时，直接定义常量为原始路径
			define($file_constant, H . $file_path);
		}
	}
}

/**
 * 检查并返回文件的替换路径或原始路径
 *
 * @param string $source2 输入的文件路径
 * @return string 返回替换后的文件路径或原始路径
 */
function check_replace($source2) {
	// 获取文件的真实路径，如果不存在则使用原始输入
	$source = realpath($source2);
	if (!file_exists($source)) $source = $source2;
	// 将路径中的目录分隔符统一替换为正斜杠
	$source = str_ireplace(DIRECTORY_SEPARATOR, "/", (string)$source);
	$h = str_ireplace(DIRECTORY_SEPARATOR, "/", H);
	$replace = str_ireplace(DIRECTORY_SEPARATOR, "/", REPLACE);
	// 计算替换路径
	$replace_file = str_ireplace($h, $replace, (string)$source);
	// 检查是否启用了替换功能
	if (setget('replace', 1) == 1) {
		// 如果替换文件存在，返回替换路径
		if (file_exists($replace_file)) {
			return $replace_file;
		} else {
			// 否则返回原始路径
			return $source;
		}
	} else {
		// 未启用替换功能时，返回原始路径
		return $source;
	}
}

/**
 * 测试文件是否为普通文件（使用 check_replace 检查路径）
 *
 * @param string $file 文件路径
 * @return bool 如果是普通文件返回 true，否则返回 false
 */
function test_file($file) {
	return (is_file(check_replace($file)));
}

/**
 * 测试文件是否存在（使用 check_replace 检查路径）
 *
 * @param string $file 文件路径
 * @return bool 如果文件存在返回 true，否则返回 false
 */
function test_file2($file) {
	return (file_exists(check_replace($file)));
}

/**
 * 检查并包含指定文件（如果替换路径存在）
 *
 * @param string $source 文件路径
 */
function check_file($source) {
	// 使用静态变量记录已包含的文件
	static $includes;
	// 如果替换路径中的文件存在，则包含该文件
	if (file_exists(REPLACE . $source)) {
		include_once REPLACE . $source;
		$includes[$source] = TRUE;
		// 如果文件已包含，则退出脚本
		if ($includes[$source] === TRUE) exit();
	}
}

/**
 * 获取或设置全局配置变量的值
 *
 * @param string $name 配置项名称
 * @param mixed $default 默认值，默认为 NULL
 * @return mixed 返回配置项的值
 */
function setget($name, $default = NULL) {
	global $set;
	// 如果配置项未设置，则初始化为默认值
	if (!isset($set[$name])) {
		if ($default === NULL) $set[$name] = NULL;
		else $set[$name] = $default;
	}
	return $set[$name];
}

// 初始化变量 $num 为 0
$num = 0;

// 设置配置数组 $conf 的 'home' 键为 TRUE
$conf['home'] = TRUE;
