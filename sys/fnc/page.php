<?php
// 返回当前页面
function page($k_page=1) {
	$page = 1;
	if (isset($_GET['page'])) {
		if ($_GET['page'] == 'end')
			$page = intval($k_page);
		elseif(is_numeric($_GET['page'])) 
		$page = intval($_GET['page']);
	}
	if ($page < 1) $page = 1;
	if ($page > $k_page) $page = $k_page;
	return $page;
}

// 计算页数
function k_page($k_post = 0, $k_p_str = 10) {
	if ($k_post != 0) {
		$v_pages = ceil($k_post / $k_p_str);
		return $v_pages;
	}
	else return 1;
}

// 页码显示（乍一看似乎很难;)）
function str($link = '?', $k_page = 1,$page = 1) {
	if ($page < 1) $page = 1;
	echo '<div class="c2">';
	if ($page != 1) echo '<span class="page"><a href="' . $link . 'page=1" title="第 1 页">&lt;</a></span> ';
	if ($page != 1) {
		echo '<span class="page"><a href="' . $link . 'page=1" title="第 1 页">1</a></span>';
	} else {
		echo ' <span class="str"><b>1</b></span>';
	}
	for ($ot = -3; $ot <= 3; $ot++) {
		if ($page + $ot > 1 && $page + $ot < $k_page) {
			if ($ot == -3 && $page + $ot > 2) echo '<span class="page"> ..';
			if ($ot != 0) {
				echo ' <span class="page"><a href="' . $link . 'page=' . ($page + $ot) . '" title="第 ' . ($page + $ot) . ' 页">' . ($page + $ot) . '</a></span>';
			} else {
				echo ' <span class="str"><b>' . ($page + $ot) . '</b></span>';
			}
			if ($ot == 3 && $page + $ot < $k_page - 1) echo '<span class="page"> ..';
		}
	}
	if ($page != $k_page) {
		echo ' <span class="page"><a href="' . $link . 'page=end" title="第 ' . $k_page . ' 页">' . $k_page . '</a></span>';
	} elseif ($k_page > 1) {
		echo ' <span class="str"><b>' . $k_page . '</b></span>';
	}
	if ($page != $k_page) echo ' <span class="page"><a href="' . $link . 'page=end" title="最后一页">&gt;</a></span>';
	echo '</div>';
}