<?php if (!empty($news) && isset($db) && (empty($user) || $user['news_read'] == 0)): ?>
<div class="mess">
	<img src="style/icons/blogi.png" alt="*">
	<a href="news/news.php?id=<?php echo $news['id']; ?>"><?php echo text($news['title']); ?></a>
	<br>

	<?php echo output_text($news['msg']); ?>
	<br>

	<?php if ($news['link'] != NULL): ?>
		<a href="<?php echo htmlentities($news['link'], ENT_QUOTES, 'UTF-8'); ?>">详情</a>
		<br>
	<?php endif; ?>

	作者: <?php echo \GuGuan123\dcms\Utils\user::nick($news['id_user'], 1, 1, 0) . ' ' . vremja($news['time']) . ' '; ?> <img src="style/icons/komm.png" alt="*" /> (<?php echo $db->queryColumn('SELECT COUNT(*) FROM `news_komm` WHERE `id_news` = ?', [$news['id']]); ?>)<br />';

	<?php if (isset($user)): ?>
		<div style="text-align:right;"><a href="?news_read">隐藏</a></div>
	<?php endif; ?>
</div>
<?php endif;
