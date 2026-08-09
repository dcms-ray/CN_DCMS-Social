<div class="title" sytle="text-align: center;">
	<a href="./user/online.php" title="查看在线用户" class="user-count-online-link">
		<span class="user-count-small-text">在线 </span>
		<span class="user-count"><?php echo $ol_user ?? 'unknow'; ?></span>
	</a>
	<a href="./user/online_g.php" title="查看在线游客" class="user-count-online-link">
		<span class="user-count-small-text"> (</span>
		<span class="user-count">+<?php echo $ol_guest ?? 'unknow'; ?></span>
		<span class="user-count-small-text"> 游客 )</span>
	</a>
</div>

<div class="main_menu">';
	<?php if (isset($user)): ?>
	<div align="right">
		<img src="style/icons/icon_stranica.gif" alt="DS"><?php echo \GuGuan123\dcms\Utils\user::nick($user['id']) ?> | <a href="user/exit.php"><font color="#ff0000">退出</font></a>
	</div>';
	<?php else: ?>
		<div align="right"><a href="user/aut.php">登录</a> | <a href="./user/reg.php">注册</a></div>
	<?php endif; ?>
</div>
