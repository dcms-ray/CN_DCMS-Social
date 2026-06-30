<?php
call_user_func(function() use (&$user) {
	$db = \GuGuan123\dcms\Core\Database::getInstance();
	$set = \GuGuan123\dcms\Core\Settings::getInstance();

	// 根据设置项决定展示：统计数据 还是 在线人数
	if (!$set->get('forum_counter')) {
		$sql_where = "";
		$params = [];

		// 权限判断
		if (empty($user) || $user['level'] == 0) {
			$adm_forums = $db->queryAll("SELECT `id` FROM `forum_f` WHERE `adm` = '1'") ?: [];
			if (!empty($adm_forums)) {
				$exclude_ids = array_column($adm_forums, 'id');
				$placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));
				$sql_where = " WHERE `id_forum` NOT IN ($placeholders)";
				$params = $exclude_ids;
			}
		}

		// 获取主题数和帖子数
		$count_t = $db->queryColumn("SELECT COUNT(*) FROM `forum_t`{$sql_where}", $params) ?: 0;
		$count_p = $db->queryColumn("SELECT COUNT(*) FROM `forum_p`{$sql_where}", $params) ?: 0;
		echo '(' . $count_t . '/' . $count_p . ')';

	} else {
		// 获取当前论坛板块的在线人数
		$online_time = time() - 600;
		$count_online = $db->queryColumn("SELECT COUNT(*) FROM `user` WHERE `date_last` > ? AND `url` LIKE '/forum/%'", [$online_time]) ?: 0;

		echo $count_online . ' 人';
	}
});
