<?php

namespace GuGuan123\dcms\Utils;

/**
 * 主要用户功能
 *  nick()-显示昵称和在线图标
 * 头像-显示头像和用户图标
 * 所有函数都有参数输出什么和不输出什么
 * 2022年2月23日23点42分修改nick()方法用户组输出
 */
class user
{
	/**
	 * / 参考文献及用户昵称
	 */
	// 所有用户字段
	public static function user_db($user = 0) {
		static $nicks = [];
		$db = \GuGuan123\dcms\Core\Database::getInstance();
		if (empty($nicks[$user])) {
			$ank = $db->query('SELECT `nick`, `date_last`, `rating`, `browser` FROM `user` WHERE `id` = ? LIMIT 1 ', [$user]);
			$ank['date_last'] = $db->query('SELECT ul.last_online FROM `user_log` ul WHERE ul.id_user = ? AND ul.ban = ? ORDER BY ul.last_online DESC LIMIT 1', [$user, 0]);
			$nicks[$user] = $ank;
		} else {
			$ank = $nicks[$user];
		}
	}

	/**
	 * 返回用户昵称展示的HTML
	 *
	 * @param int   $user  用户ID
	 * @param bool  $url   设置昵称为链接到用户页
	 * @param bool  $on    显示 Nick 旁边的在线图标和用户组图标
	 * @param bool  $medal 在线输出图标旁边的奖牌
	 * @return string HTML结构
	 */
	public static function nick(int $user = 0, $url = true, $on = false, $medal = false): string {
		/*
		* $url == 0		只输出昵称
		* $url == 1		输出昵称并链接到用户页的
		* $on  == 1		显示 Nick 旁边的在线图标和用户组图标
		* $medal == 1	在线输出图标旁边的奖牌
		*/
		static $nicks = [];
		$set = \GuGuan123\dcms\Core\Settings::getInstance();
		$db = \GuGuan123\dcms\Core\Database::getInstance();
		if (empty($nicks[$user])) {
			$ank = $db->query('SELECT `id`, `group_access`, `pol`, `nick`, `rating` FROM `user` WHERE `id` = ? LIMIT 1', [$user]);
			if (isset($ank['id'])) {
				$ank_login_lsat = $db->query('SELECT ul.last_online, ul.browser FROM `user_log` ul WHERE ul.id_user = ? AND ul.ban = ? ORDER BY ul.last_online DESC LIMIT 1', [$user, '0']);
				if (isset($ank_login_lsat['last_online'])) {
					$ank['date_last'] = strtotime($ank_login_lsat['last_online']);
					$ank['browser'] = $ank_login_lsat['browser'];
				}
			}
			$nicks[$user] = $ank;
		} else {
			$ank = $nicks[$user];
		}
		$icon = null;
		$nick = null;
		$online = null;
		$icon_medal = null;
		// 用户名引线
		if ($user == 0) {
			$ank = array('id' => '0', 'nick' => '系统', 'pol' => '1', 'rating' => '0', 'browser' => 'wap', 'date_last' => time());
		} elseif (!$ank) {
			$ank = array('id' => '0', 'nick' => '[已删除]', 'pol' => '1', 'rating' => '0', 'browser' => 'wap');
		}

		if ($url == true) {
			$nick = ' <a href="' . $set->get('siteurl') . '/user/info.php?id=' . $user . '">' . stripcslashes(htmlspecialchars($ank['nick'])) . '</a> ';
		} else {
			$nick = text($ank['nick']);
		}

		// 用户组图标
		if ($on == true) {
			$is_ban = $db->queryColumn('SELECT COUNT(*) FROM `ban` WHERE `id_user` = ? AND (`time` > ? OR `navsegda` = ?)', [$user, time(), '1']);
			if ($is_ban != 0) {
				$icon = ' <img src="' . $set->get('siteurl') . '/style/user/ban.png" alt="*" class="icon" id="icon_group" /> ';
			} else {
				if (isset($ank['group_access']) && ($ank['group_access'] > 7 && ($ank['group_access'] < 10 || $ank['group_access'] > 14))) {
					if ($ank['pol'] == 1) {
						$icon = '<img src="' . $set->get('siteurl') . '/style/user/1.png" alt="*" class="icon" id="icon_group" /> ';
					} else {
						$icon = '<img src="' . $set->get('siteurl') . '/style/user/2.png" alt="" class="icon" id="icon_group"/> ';
					}
				} elseif (isset($ank['group_access']) && (($ank['group_access'] > 1 && $ank['group_access'] <= 7) || ($ank['group_access'] > 10 && $ank['group_access'] <= 14))) {
					if ($ank['pol'] == 1) {
						$icon = '<img src="' . $set->get('siteurl') . '/style/user/3.png" alt="*" class="icon" id="icon_group" /> ';
					} else {
						$icon = '<img src="' . $set->get('siteurl') . '/style/user/4.png" alt="*" class="icon" id="icon_group" /> ';
					}
				} else {
					if (isset($ank['pol']) && $ank['pol'] == 1) {
						$icon = '<img src="' . $set->get('siteurl') . '/style/user/5.png" alt="" class="icon" id="icon_group" /> ';
					} else {
						$icon = '<img src="' . $set->get('siteurl') . '/style/user/6.png" alt="" class="icon" id="icon_group" /> ';
					}
				}
			}
		}

		// 在线图标输出
		if ($user != 0 && !empty($ank['date_last']) && $ank['date_last'] > time() - 600 && $on == true) {
			if ($ank['browser'] == 'wap') {
				$online = ' <img src="' . $set->get('siteurl') . '/style/icons/online.gif" alt="WAP" /> ';
			} else {
				$online = ' <img src="' . $set->get('siteurl') . '/style/icons/online_web.gif" alt="WEB" /> ';
			}
		}

		// 奖牌输出
		$R = $ank['rating'];
		if ($medal == true && $ank['rating'] >= 6) {
			if ($ank['rating'] >= 6 && $ank['rating'] <= 11) {
				$img = 1;
			} elseif ($ank['rating'] >= 12 && $ank['rating'] <= 19) {
				$img = 2;
			} elseif ($ank['rating'] >= 20 && $ank['rating'] <= 27) {
				$img = 3;
			} elseif ($ank['rating'] >= 28 && $ank['rating'] <= 37) {
				$img = 4;
			} elseif ($ank['rating'] >= 38 && $ank['rating'] <= 47) {
				$img = 5;
			} elseif ($ank['rating'] >= 48 && $ank['rating'] <= 59) {
				$img = 6;
			} elseif ($ank['rating'] >= 60) {
				$img = 7;
			} else {
				$img = 0;
			}
			$icon_medal = ' <img src="' . $set->get('siteurl') . '/style/medal/' . $img . '.png" alt="*" /> ';
		}
		return $icon . $nick . $icon_medal . $online;
	}

	/**
	 * 输出用户头像，用户组图标
	 * 
	 * @param \GuGuan123\dcms\Database $db
	 * @param  array  $set  网站设置
	 * @param  int    $user 用户ID
	 * @param  int    $type 0-将头像和图标一起输出; 1-只输出头像; 2-只输出图标
	 * @return string 包含用户图标的HTML结构
	 */
	public static function avatar(\GuGuan123\dcms\Database $db, array $set, $user = 0, $type = 1) {
		static $avatars = [];
		// Аватар
		if ($type == 0 || $type == 1) {
			if (empty($avatars[$user])) {
				$avatar = $db->query('SELECT id,ras FROM `gallery_photo` WHERE `id_user` = ? AND `avatar` = ? LIMIT 1', [$user, '1']);
				$avatars[$user] = $avatar;
			} else {
				$avatar = $avatars[$user];
			}
			if (isset($avatar['id']) && test_file(H . 'files/gallery/50/' . $avatar['id'] . '.jpg')) {
				$AVATAR = ' <img class="avatar" src="' . $set['siteurl'] . '/photo/photo50/' . $avatar['id'] . '.jpg" alt="Avatar" /> ';
			} else {
				$AVATAR = '<img class="avatar" src="' . $set['siteurl'] . '/style/user/avatar.gif" height= "50" width="50" alt="No Avatar" />';
			}
		}
		return $AVATAR ?? null;
	}

	/**
	 * 获取用户信息
	 * 
	 * @param  int   $ID 用户ID
	 * @return array 用户信息
	 */
	static function get_info(int $ID = 0) {
		$db = \GuGuan123\dcms\Core\Database::getInstance();
		if ($ID == 0) {
			// 机器人
			$ank['id'] = 0;
			$ank['nick'] = '系统';
			$ank['level'] = 999;
			$ank['pol'] = 1;
			$ank['group_name'] = '系统机器人';
			$ank['ank_o_sebe'] = '为通知创建';
			return $ank;
		} else {
			$user_id = intval($ID);
			if (empty($ank) || !is_array($ank)) {
				$ank = [];  // 初始化为一个空数组
			}
			$ank[0] = false;
			if (!isset($ank[$user_id])) {
				$ank[$user_id] = $db->query('SELECT * FROM `user` WHERE `id` = ? LIMIT 1', [$user_id]);

				if (empty($ank[$user_id]['id'])) {
					// 用户不存在
					$ank[$user_id] = false;
				} elseif ($ank[$user_id]['id'] != 0) {

					// 查询获取在user_log表中的用户数据
					$query = dbquery("SELECT ul.last_online, ul.ip, ul.ua, ul.url
					                  FROM `user_log` ul
					                  WHERE ul.id_user = $user_id
					                    AND ul.ban = '0'
					                  ORDER BY ul.last_online DESC
					                  LIMIT 1");
					if ($row = dbassoc($query)) {
						// 用户最后在线时间
						$ank[$user_id]['date_last'] = strtotime($row['last_online']);
						// 用户最后的IP
						$ank[$user_id]['ip'] = $row['ip'];
						// 用户最后的UA
						$ank[$user_id]['ua'] = $row['ua'];
						// 用户最后的URL
						$ank[$user_id]['url'] = $row['url'];
					}

					$tmp_us = $db->query('SELECT `level`,`name` AS `group_name` FROM `user_group` WHERE `id` = ? LIMIT 1', [$ank[$user_id]['group_access']]);

					if (!isset($tmp_us) or empty($tmp_us['group_name'])) {
						$ank[$user_id]['level'] = 0;
						$ank[$user_id]['group_name'] = '用户';
					} else {
						$ank[$user_id]['level'] = $tmp_us['level'];
						$ank[$user_id]['group_name'] = $tmp_us['group_name'];
					}
				} else {
					$ank[$user_id] = FALSE;
				}
			}
			return $ank[$user_id];
		}
	}

	/**
	 * 检测用户是否有相应的权限
	 *
	 * @param  string   $access 权限名称
	 * @param  int|null $u_id   用户ID
	 * @return bool
	 */
	public static function user_access(string $access, $u_id = null) {
		// 如果未传递用户 ID，则使用全局变量 `$user`
		if ($u_id == null) {
			global $user;
		} else {
			// 否则通过传递的 ID 获取用户数据
			$user = self::get_info($u_id);
			if (empty($user)) return false;
		}

		// 初始化用户权限的默认值
		if (isset($user)) $user['group_access2'] = 0;

		// 检查用户是否有组权限
		if (!isset($user['group_access']) || $user['group_access'] == null) return false;

		$db = \GuGuan123\dcms\Core\Database::getInstance();
		// 返回权限检查结果
		return ($db->queryColumn("SELECT COUNT(*) FROM `user_group_access` WHERE (`id_group` = ? or `id_group` = ?) and `id_access` = ?", [$user['group_access'], $user['group_access2'], my_esc($access)]) == 1 ? true : false);
	}
}
