<?php

/**
 * 处理带有 [url] 标签的链接并生成 HTML 锚标签
 *
 * 该函数通过正则表达式匹配处理传入的数组，判断链接是否为当前主机地址，
 * 并根据全局配置 $set['web'] 决定是否添加 target="_blank" 属性。
 *
 * @param array $arr 包含链接和文本的数组，$arr[1] 为链接地址，$arr[2] 为链接文本
 * @return string 返回生成的 HTML 锚标签字符串
 */
function links_preg1($arr) {
	global $set;
	if (preg_match('#^https://' . preg_quote($_SERVER['HTTP_HOST']) . '#', $arr[1]) || !preg_match('#://#', $arr[1])) {
		return '<a href="' . $arr[1] . '">' . $arr[2] . '</a>';
	} else {
		return '<a' . ($set['web'] ? ' target="_blank"' : null) . ' href="/go.php?go=' . base64_encode(html_entity_decode($arr[1])) . '">' . $arr[2] . '</a>';
	}
}

/**
 * 处理普通文本中的 URL 并生成 HTML 锚标签
 *
 * 该函数通过正则表达式匹配处理传入的数组，判断链接是否为当前主机地址（http 或 https），
 * 并根据全局配置 $set['web'] 决定是否添加 target="_blank" 属性。
 * 对于外部链接，会使用 base64 编码并跳转到 /go.php。
 *
 * @param array $arr 包含匹配文本的数组，$arr[1] 为前置文本，$arr[2] 为链接地址，$arr[4] 为后置文本
 * @return string 返回生成的 HTML 字符串
 */
function links_preg2($arr) {
	global $set;
	if (preg_match('#^https://' . preg_quote($_SERVER['HTTP_HOST']) . '#', $arr[2])) {
		return $arr[1] . '<a href="' . $arr[2] . '">' . $arr[2] . '</a>' . $arr[4];
	}
	else if (preg_match('#^http://' . preg_quote($_SERVER['HTTP_HOST']) . '#', $arr[2])) {
		return $arr[1] . '<a href="' . $arr[2] . '">' . $arr[2] . '</a>' . $arr[4];
	} else {
		 return $arr[1] . '<a' . ($set['web'] ? ' target="_blank"' : null) . ' href="/go.php?go=' . base64_encode(html_entity_decode($arr[2])) . '">外部链接</a>' . $arr[4];
	}
}

/**
 * 将文本中的 URL 转换为 HTML 锚标签
 *
 * 该函数根据全局配置 $set['bb_url'] 和 $set['bb_http']，分别处理 [url] 标签和普通文本中的链接，
 * 并调用 links_preg1 和 links_preg2 函数进行具体转换。
 *
 * @param string $msg 输入的原始文本
 * @return string 返回处理后的文本，包含 HTML 锚标签
 */
function links($msg) {
	global $set;
	if ($set['bb_url']) $msg = preg_replace_callback('/\[url=((?!javascript:|data:|document.cookie).+)\](.+)\[\/url\]/isU', 'links_preg1', $msg);
	if ($set['bb_http']) $msg = preg_replace_callback('~(^|\s)([a-z]+://([^ \r\n\t`\'"]+))(\s|$)~iu', 'links_preg2', $msg);
	return $msg;
}
