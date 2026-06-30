<?php
$my_dir = $db->queryColumn("SELECT * FROM `downnik_dir` WHERE `my` = '1' LIMIT 1");
$k_p = $db->queryColumn('SELECT COUNT(*) FROM `downnik_files` WHERE `id_dir` != ?', [$my_dir['id']]);
$k_n = $db->queryColumn('SELECT COUNT(*) FROM `downnik_files` WHERE `id_dir` != ? AND `time_go` > ?', [$my_dir['id'], mktime(0, 0, 0)]);
if ($k_n == 0) {
	$k_n = NULL;
} else {
	$k_n = '+' . $k_n;
}
echo "($k_p) <font color='red'>$k_n</font>";
