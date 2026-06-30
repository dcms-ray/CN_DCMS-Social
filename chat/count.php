<?php
if (isset($user) && empty($in_chat_room)) $db->executeStatement('DELETE FROM `chat_who` WHERE `id_user` = ?', [$user['id']]);
$db->executeStatement('DELETE FROM `chat_who` WHERE `time` < ?', [time() - 120]);
echo '(' . $db->queryColumn('SELECT COUNT(*) FROM `chat_who`') ?: 0 . ' 人)';
