<?php
call_user_func(function() use (&$user) {
	$db = \GuGuan123\dcms\Core\Database::getInstance();
	echo '<div style="padding: 6px 10px;" class="foot"><a href="/forum/"><b>论坛</b></a></div>';

	if (($db->queryColumn("SELECT COUNT(`id`) FROM `forum_t`") ?: 0) > 0) {
		echo '<div class="mess">';

		$themes = $db->queryAll("SELECT * FROM `forum_t` ORDER BY `time_create` DESC LIMIT 5") ?: [];
		$num = 0;

		foreach ($themes as $them) {
			// 背景颜色交替
			$nav_class = ($num === 0) ? 'nav1' : 'nav2';
			$num = ($num === 0) ? 1 : 0;
			
			echo '<div class="' . $nav_class . '">';
			echo '<a href="/forum/' . $them['id_forum'] . '/' . $them['id_razdel'] . '/' . $them['id'] . '/"><b>' . \htmlspecialchars($them['name']) . '</b></a>';
			echo '作者' . \GuGuan123\dcms\Utils\user::nick($them['id_user'], 1, 0, 0);
			echo '</div>';
		}
		echo "</div>";
	}

	// 日记统计与展示
	$cutoff_time = time() - 86000;
	$new_notes_count = $db->queryColumn("SELECT COUNT(`id`) FROM `notes` WHERE `time` > ?", [$cutoff_time]) ?: 0;
	$total_notes_count = $db->queryColumn("SELECT COUNT(`id`) FROM `notes`") ?: 0;
	$notes_badge = ($new_notes_count > 0) ? "{$total_notes_count} + {$new_notes_count}" : $total_notes_count;
	echo '<div style="padding: 6px 10px;" class="foot"><a href="/plugins/notes/"><b>日记</b> (' . $notes_badge . ')</a></div>';

	$notes = $db->queryAll("SELECT * FROM `notes` ORDER BY `time` DESC LIMIT 3") ?: [];
	if (empty($notes)) {
		echo '<div class="nav2 main_no_notes_nav2">没有记录</div>';
	} else {
		foreach ($notes as $post) {
			$allowViewNote = false;

			if ($post['private'] == 0) {
				$allowViewNote = true; // 公开日记
			} elseif (isset($user)) {
				// 如果是作者本人，或者拥有管理员删除日记权限，直接放行
				if ($user['id'] == $post['id_user'] || \user_access('notes_delete')) {
					$allowViewNote = true;
				} elseif ($post['private'] == 1) {
					// 查询双方好友关系是否达成
					$frend_check = $db->queryColumn(
						'SELECT COUNT(*) FROM `frends` WHERE (`user` = ? AND `frend` = ?) OR (`user` = ? AND `frend` = ?) LIMIT 1',
						[$user['id'], $post['id_user'], $post['id_user'], $user['id']]
					) ?: 0;
					if ($frend_check == 2) $allowViewNote = true;
				}
			}

			echo "<div class='nav2'>";
			echo \GuGuan123\dcms\Utils\user::nick($post['id_user'], 1, 1, 0);
			echo ' : <a href="/plugins/notes/list.php?id=' . $post['id'] . '"><span style="color:#06f">';

			if ($allowViewNote) {
				echo \text($post['name']);
			} else {
				echo '[不可见]';
			}
			echo '</span></a><br />';

			if ($allowViewNote) {
				echo \rez_text($post['msg'], 80) . '<br />';
				if ($post['share'] == 1) {
					echo "(!) <i>转发</i><br/>";
				}
				// 获取当前日记评论数
				$count_comm = $db->queryColumn("SELECT COUNT(`id`) FROM `notes_komm` WHERE `id_notes` = ?", [$post['id']]) ?: 0;
				echo '<img src="/style/icons/comm_num_gray.png">' . $count_comm . '<span style="float:right;color:#666;"><small>';
				echo \vremja($post['time']);
			} elseif ($post['private'] == 1) {
				echo '<font color="#999">[内容仅好友可见]</font><span style="float:right;color:#666;"><small>';
			} else {
				echo '<font color="#999">[内容仅作者可见]</font><span style="float:right;color:#666;"><small>';
			}
			echo '</small></div>';
		}
	}

	// 页脚快捷导航栏
	echo '<div class="nav1">';
	if (isset($user)) echo '<a href="/plugins/notes/add.php">写日记</a>';
	echo '<span style="float:right;"><a href="/plugins/notes/">所有日记&rarr;</a></span><br /></div>';
});
