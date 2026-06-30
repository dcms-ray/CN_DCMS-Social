<?php
$menuList = $db->queryAll("SELECT * FROM `menu` ORDER BY `pos` ASC") ?: [];
foreach ($menuList as $post_menu) {
	if ($post_menu['type'] == 'link') {
		echo '<div class="main_menu">';
		echo '<img src="style/icons/' . htmlspecialchars($post_menu['icon']) . '" alt="*" /> ';
		echo '<a href="' . htmlspecialchars($post_menu['url']) . '">' . htmlspecialchars($post_menu['name']) . '</a> ';
	} else {
		// 如果不是 link，那就是 'razd'
		echo '<div class="menu_razd">';
		echo htmlspecialchars($post_menu['name']);
	}
	// 动态引入计数器文件
	if ($post_menu['counter'] != NULL && is_file(check_replace($post_menu['counter']))) {
		include $post_menu['counter'];
	}
	echo '</div>';
}
if (user_access('adm_panel_show')) {
	echo '<div class="main2">';
	echo '<img src="style/icons/adm.gif" alt="DS" /> <a href="plugins/admin/">网站管理</a> ';
	include_once check_replace('plugins/admin/count.php');
	echo '</div>';
}
