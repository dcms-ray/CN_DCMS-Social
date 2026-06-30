<?php
$k_p = $db->queryColumn('SELECT COUNT(*) FROM `news`');
$k_n = $db->queryColumn('SELECT COUNT(*) FROM `news` WHERE `time` > ?', [mktime(0, 0, 0)]);
if ($k_n == 0) {
	$k_n = NULL;
} else {
	$k_n = '+' . $k_n;
}
echo '(' . $k_p . ') <font color="red">' . $k_n . '</font>';
