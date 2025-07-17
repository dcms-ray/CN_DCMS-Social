<?php
require_once '../../sys/inc/start.php';
require_once '../../sys/inc/compress.php';
require_once '../../sys/inc/sess.php';
require_once '../../sys/inc/home.php';
require_once '../../sys/inc/settings.php';
require_once '../../sys/inc/db_connect.php';
require_once '../../sys/inc/ipua.php';
require_once '../../sys/inc/fnc.php';
require_once '../../sys/inc/user.php';

only_reg();
$set['title'] = '设置-关于我的';
require_once '../../sys/inc/thead.php';
title();

$notSet = dbarray(dbquery("SELECT * FROM `notification_set` WHERE `id_user` = '" . $user['id'] . "' LIMIT 1"));
if (isset($_POST['save'])) {
    // 评论
    if (isset($_POST['komm']) && ($_POST['komm'] == 0 || $_POST['komm'] == 1)) {
        dbquery("UPDATE `notification_set` SET `komm` = '" . intval($_POST['komm']) . "' WHERE `id_user` = '$user[id]'");
    }
    $_SESSION['message'] = '更改成功';
    header('Location: settings.php');
    exit;
}

err();
aut();
echo "<div id='comments' class='menus'>";
echo "<div class='webmenu'>";
echo "<a href='../info/settings.php'>通用</a>";
echo "</div>";
echo "<div class='webmenu last'>";
echo "<a href='../tape/settings.php'>通知消息</a>";
echo "</div>";
echo "<div class='webmenu last'>";
echo "<a href='../discussions/settings.php'>讨论</a>";
echo "</div>";
echo "<div class='webmenu last'>";
echo "<a href='../notification/settings.php' class='activ'>关于我的</a>";
echo "</div>";
echo "<div class='webmenu last'>";
echo "<a href='../info/settings.privacy.php' >隐私保护</a>";
echo "</div>";
echo "<div class='webmenu last'>";
echo "<a href='../info/secure.php' >更改密码</a>";
echo "</div>";
echo "</div>";
echo "<form action='?' method=\"post\">";
// Лента фото
echo "<div class='mess'>";
echo "关于评论中的回复的通知";
echo "</div>";
echo "<div class='nav1'>";
echo "<input name='komm' type='radio' " . ($notSet['komm'] == 1 ? ' checked="checked"' : null) . " value='1' /> 开启 ";
echo "<input name='komm' type='radio' " . ($notSet['komm'] == 0 ? ' checked="checked"' : null) . " value='0' /> 关闭 ";
echo "</div>";
echo "<div class='main'>";
echo "<input type='submit' name='save' value='保存' />";
echo "</div>";
echo "</form>";
echo "<div class='foot'>";
echo "<img src="/style/icons/str2.gif" alt="*"> <?= user::nick($user['id'],1,0,0) ?></a> | <b>关于我的</b>";
echo "</div>";
require_once '../../sys/inc/tfoot.php';
