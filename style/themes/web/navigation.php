<?php
if (isset($user)) {
	echo '<a href="/user/info.php?"><span class="link_title"><img src="/style/themes/web/images/user.png" alt=""/>
		<br/>个人主页</span></a>';

	/*
	=================================
	邮件
	=================================
	*/
	$k_new=dbresult(dbquery("SELECT COUNT(`mail`.`id`) FROM `mail`
	LEFT JOIN `users_konts` ON `mail`.`id_user` = `users_konts`.`id_kont` AND `users_konts`.`id_user` = '$user[id]'
	WHERE `mail`.`id_kont` = '$user[id]' AND (`users_konts`.`type` IS NULL OR `users_konts`.`type` = 'common' OR `users_konts`.`type` = 'favorite') AND `mail`.`read` = '0'"),0);


	$k_new_fav=dbresult(dbquery("SELECT COUNT(`mail`.`id`) FROM `mail`
	LEFT JOIN `users_konts` ON `mail`.`id_user` = `users_konts`.`id_kont` AND `users_konts`.`id_user` = '$user[id]'
	WHERE `mail`.`id_kont` = '$user[id]' AND (`users_konts`.`type` = 'favorite') AND `mail`.`read` = '0'"),0);

	if ($k_new!=0 && $k_new_fav==0){
		echo "<a href='/user/new_mess.php'><span class='link_title'><img src='/style/themes/web/images/mail.png' alt=''/>  <b class='count'>+$k_new</b><br/> 邮件 </span></a>";
	} else {
		echo "<a href='/user/conts.php'><span class='link_title'><img src='/style/themes/web/images/mail.png' alt=''/><br/>邮件</span></a>";
	}

	/*
	=================================
	信息中心
	=================================
	*/
	$lenta = dbresult(dbquery("SELECT COUNT(*) FROM `tape` WHERE `id_user` = '$user[id]' AND `read` = '0' "),0);
	echo "<a href='/user/tape/index.php'><span class='link_title'><img src='/style/themes/web/images/lenta.png' alt=''/>";
	$k_l = $lenta;
	if($k_l>0)echo " <b class='count'>+$k_l</b>";
	echo "<br/>信息中心";
	echo "</span></a>";

	/*
	=================================
	讨论
	=================================
	*/
	$discuss = dbresult(dbquery("SELECT COUNT(`count`) FROM `discussions` WHERE `id_user` = '$user[id]' AND `count` > '0' "),0); // Обсуждения
	$k_l = $discuss;
	echo "<a href='/user/discussions/index.php'><span class='link_title'><img src='/style/themes/web/images/disc.png' alt=''/>";
	if($k_l>0)echo " <b class='count'>+$k_l</b>";
	echo "<br/>讨论";
	echo "</span></a>";

	/*
	=================================
	关于我的
	=================================
	*/
	$k_notif = dbresult(dbquery("SELECT COUNT(`read`) FROM `notification` WHERE `id_user` = '$user[id]' AND `read` = '0'"), 0); // 通知
	$k_l = $k_notif;
	if($k_l>0){
		echo "<a href='/user/notification/index.php'><span class='link_title'><img src='/style/themes/web/images/notif2.png' alt=''/><b class='count'>+$k_l</b>
			<br/>关于我的";
		echo "</span></a>";
	}

	/*
	=================================
	好友列表
	=================================
	*/
	$k_f = dbresult(dbquery("SELECT COUNT(id) FROM `frends_new` WHERE `to` = '$user[id]' LIMIT 1"), 0);
	if ($k_f>0) {
		echo "<a href='/user/frends/new.php'><span class='link_title'><img src='/style/themes/web/images/frend.png' alt=''/><b class='count'>+$k_f</b>
			<br/>好友列表 </span></a>";
	} else {
		echo '<a href="/user/frends/?id='.$user['id'].'"><span class="link_title"><img src="/style/themes/web/images/frend.png" alt=""/>
			<br/>好友列表</span></a>';
	}

	/*
	=================================
	刷新
	=================================
	*/
	echo '<a href="'.htmlspecialchars($_SERVER['REQUEST_URI']).'"><span class="link_title"><img src="/style/themes/web/images/refresh.png"/>
	<br/>刷新</span></a>';

} elseif ($_SERVER['PHP_SELF'] != '/user/aut.php' && $_SERVER['PHP_SELF'] != '/user/reg.php') {
	echo '<a href="#user" rel="facebox"><span class="link_title2"><img src="/style/themes/web/images/key.png" alt=""/><br />登录/注册</span></a>';
	echo '<div id="user" style="display:none;">';
	echo "<div class = 'foot'>登录账号</div>";
	echo "<form class='mess' method='post' action='/user/login.php'>
			  用户名:<br /><input type='text' name='nick' maxlength='32' /><br />
			  密码:<br /><input type='password' name='pass' maxlength='32' /><br />
			  <label><input type='checkbox' name='aut_save' value='1' /> 保存cookie</label><br />
			  <input type='submit' value='登录' /> <a href='/user/pass.php'>密码恢复</a> <br />
		  </form><br />";
	echo "<div class = 'foot'>注册</div>";
	echo "<form class='mess' method='post' action='/user/reg.php?$passgen'>";
	echo "选择用户名 [A-z0-9 -_]:<br /><input type='text' name='nick' maxlength='32' /><br />";
	echo "注册即代表你同意网站<a href='/user/rules.php'>规则</a><br />";
	echo "<input type='submit' value='继续' />";
	echo "</form><br />";
	echo'</div>';
}