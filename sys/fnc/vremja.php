<?php
// 时间输出
function vremja($time = NULL) {
	global $user;
	if ($time == NULL) $time = time();
	if (isset($user)) $time = $time + $user['set_timesdvig'] * 60 * 60;
	$timep = "" . date("Y/m/d H:i", $time) . "";
	$time_p[0] = date("Y/m/d", $time);
	$time_p[1] = date("H:i", $time);
	if ($time_p[0] == date("Y/m/d")) $timep = date("H:i:s", $time);
	if (isset($user)) {
		if ($time_p[0] == date("Y/m/d", time() + $user['set_timesdvig'] * 60 * 60)) $timep = date("H:i:s", $time);
		if ($time_p[0] == date("Y/m/d", time() - 60 * 60 * (24 - $user['set_timesdvig']))) $timep = "昨天$time_p[1]";
	} else {
		if ($time_p[0] == date("Y/m/d")) $timep = date("H:i:s", $time);
		if ($time_p[0] == date("Y/m/d", time() - 60 * 60 * 24)) $timep = "昨天$time_p[1]";
	}
	$timep = str_replace("Jan", "1", $timep);
	$timep = str_replace("Feb", "2", $timep);
	$timep = str_replace("Mar", "3", $timep);
	$timep = str_replace("May", "4", $timep);
	$timep = str_replace("Apr", "5", $timep);
	$timep = str_replace("Jun", "6", $timep);
	$timep = str_replace("Jul", "7", $timep);
	$timep = str_replace("Aug", "8", $timep);
	$timep = str_replace("Sep", "9", $timep);
	$timep = str_replace("Oct", "10", $timep);
	$timep = str_replace("Nov", "11", $timep);
	$timep = str_replace("Dec", "12", $timep);
	return $timep;
}
