<?php
$k_p = $db->queryColumn('SELECT COUNT(*) FROM `liders` WHERE `time` > ?', [time()]);
$k_n = $db->queryColumn('SELECT COUNT(*) FROM `liders` WHERE `time` > ? AND `time_p` > ?', [time(), mktime(0, 0, 0)]);
if ($k_n == 0) {
	$k_n = NULL;
} else {
	$k_n = '+' . $k_n;
}
echo '(' . $k_p . ') <font color="red">' . $k_n . '</font>';
