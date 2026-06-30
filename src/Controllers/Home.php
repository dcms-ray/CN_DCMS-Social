<?php
namespace GuGuan123\dcms\Controllers;

class Home
{
	// 网站首页
	public function index() {
		// 隐藏新闻
		$set = \GuGuan123\dcms\Core\Settings::getInstance();
		$db = \GuGuan123\dcms\Core\Database::getInstance();
		if (isset($user) && isset($_GET['news_read'])) {
			$db->query("update `user` set `news_read` = '1' where `id` = '$user[id]' limit 1");
			msg("该消息已成功隐藏");
		}

		if ($set->get('index_use_them') != true) {
			// 获取在线用户数量
			$ol_user = $db->queryColumn('SELECT COUNT(DISTINCT ul.id_user) AS online_users FROM `user_log` ul WHERE ul.last_online > NOW() - INTERVAL 10 MINUTE AND ul.ban = 0 AND ul.last_online = (SELECT MAX(last_online) FROM `user_log` ul2 WHERE ul2.id_user = ul.id_user AND ul2.last_online > NOW() - INTERVAL 10 MINUTE AND ul2.ban = 0)');
			// 在线游客数量
			$ol_guest = $db->queryColumn('SELECT COUNT(*) FROM `guests` WHERE `date_last` > ? AND `pereh` > ?', [time() - 600, 0]);
			include 'resources/assets/index.php';

			// 新闻&事件
			$news = $db->query('SELECT * FROM `news` WHERE `main_time` > ? ORDER BY `id` DESC LIMIT 1', [time()]);
			if ($news !== null && !$set->get('web') && (empty($user) || $user['news_read'] == 0)) {
				echo '<div class="mess">';
				echo '<img src="style/icons/blogi.png" alt="*" /> <a href="news/news.php?id=' . $news['id'] . '">' . text($news['title']) . '</a><br/> ';
				echo output_text($news['msg']) . '<br />';
				if ($news['link'] != NULL) echo '<a href="' . htmlentities($news['link'], ENT_QUOTES, 'UTF-8') . '">详情</a><br />';
				echo '作者: ' . \GuGuan123\dcms\Utils\user::nick($news['id_user'], 1, 1, 0) . ' ' . vremja($news['time']) . ' ';
				echo ' <img src="style/icons/komm.png" alt="*" /> (' . $db->queryColumn('SELECT COUNT(*) FROM `news_komm` WHERE `id_news` = ?', [$news['id']]) . ')<br />';
				if (isset($user)) echo '<div style="text-align:right;"><a href="?news_read">隐藏</a></div>';
				echo '</div>';
			}

			// 主菜单
			include_once 'sys/fnc/check_replace.php';
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

			// 网站管理面板
			if (user_access('adm_panel_show')) {
				echo '<div class="main2">';
				echo '<img src="style/icons/adm.gif" alt="DS" /> <a href="plugins/admin/">网站管理</a> ';
				include_once check_replace('plugins/admin/count.php');
				echo '</div>';
			}
			include 'sys/inc/main_notes.php';
		} else {
			// 主要网页主题
			include 'style/themes/' . $set->get('set_them') . '/index.php'; 
		}

		require_once 'resources/views/wap/layouts/footer.php';

	}
}
