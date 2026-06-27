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

namespace GuGuan123\dcms\Core;

class Router 
{
	// 存放路由映射关系的变量
	private $routes = [];

	// 注册 GET 路由
	public function get($path, $handler) {
		$this->routes['GET'][$path] = $handler;
	}

	// 注册 POST 路由
	public function post($path, $handler) {
		$this->routes['POST'][$path] = $handler;
	}

	// 开始匹配并执行
	public function dispatch($currentMethod, $currentUri) {
		// 检查这个请求方法和路径是否已注册
		if (isset($this->routes[$currentMethod][$currentUri])) {
			$handler = $this->routes[$currentMethod][$currentUri]; // 拿到 "TopicController@show"

			// 解析出控制器类名和具体执行的方法名
			list($controllerName, $action) = explode('@', $handler);

			// 补全命名空间，变成 \App\Controllers\TopicController
			$fullControllerClass = "\\GuGuan123\\dcms\\Controllers\\" . $controllerName;

			// 动态实例化控制器并调用方法
			$controllerInstance = new $fullControllerClass();
			return $controllerInstance->$action(); 
		}
		header("HTTP/1.1 404 Not Found");
	}
}
