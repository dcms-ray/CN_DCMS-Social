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
			$db->query('update `user` set `news_read` = ? where `id` = ? limit 1', 1, $user['id']);
			msg("该消息已成功隐藏");
		}

		if ($set->get('index_use_them') != true) {
			// 获取在线用户数量
			$ol_user = $db->queryColumn('SELECT COUNT(DISTINCT ul.id_user) AS online_users FROM `user_log` ul WHERE ul.last_online > NOW() - INTERVAL 10 MINUTE AND ul.ban = 0 AND ul.last_online = (SELECT MAX(last_online) FROM `user_log` ul2 WHERE ul2.id_user = ul.id_user AND ul2.last_online > NOW() - INTERVAL 10 MINUTE AND ul2.ban = 0)');
			// 在线游客数量
			$ol_guest = $db->queryColumn('SELECT COUNT(*) FROM `guests` WHERE `date_last` > ? AND `pereh` > ?', [time() - 600, 0]);
			include 'resources/assets/main_header.php';

			// 获取新公告
			$news = $db->query('SELECT * FROM `news` WHERE `main_time` > ? ORDER BY `id` DESC LIMIT 1', [time()]);
			include 'resources/assets/main_news.php';

			include_once 'sys/fnc/check_replace.php';
			include_once 'sys/fnc/user_access.php';

			// 主菜单
			$menuList = $db->queryAll("SELECT * FROM `menu` ORDER BY `pos` ASC") ?: [];
			include 'resources/assets/main_menu.php';

			include_once 'sys/fnc/output_text.php';
			include_once 'sys/fnc/vremja.php';
			include 'sys/inc/main_notes.php';
		} else {
			// 主要网页主题
			include 'style/themes/' . $set->get('set_them') . '/index.php'; 
		}

		require_once 'resources/views/wap/layouts/footer.php';

	}
}
