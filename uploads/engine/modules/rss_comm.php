<?php
/*
=============================================================================
RSS comments Pro - RSS для комментариев в DLE 9.x и выше (компонент вывода метатегов RSS)
=============================================================================
Автор:  ПафНутиЙ 
URL:    http://pafnuty.name/
ICQ:    817233 
email:  pafnuty10@gmail.com
=============================================================================
*/

if (!defined('DATALIFEENGINE')) {
	die("Go fuck yourself!");
}

$loginName = $db->safesql(strip_tags(stripcslashes($_REQUEST['user'])));

$cfg = array(
	'newsId'      => ($_REQUEST['newsid']) ? (int)$_REQUEST['newsid'] : false,
	'userName'    => ($_REQUEST['user']) ? $loginName : false,
	'cachePrefix' => !empty($cachePrefix) ? $cachePrefix : 'r_s_s_comm',
	'cacheSuffix' => !empty($cacheSuffix) ? $cacheSuffix : false
);

$cacheName = md5(implode('_', $cfg));
$rssCommMeta = false;
$rssCommMeta = dle_cache($cfg['cachePrefix'], $cacheName . $config['skin'], $cfg['cacheSuffix']);

if (!$rssCommMeta) {

	$rssCommTitle = 'Все комментарии';

	if ($cfg['newsId'] > 0) {
		$rsQuery = $db->super_query("SELECT id, title FROM " . PREFIX . "_post WHERE id = '" . $cfg['newsId'] . "'");

		if ($rsQuery['id']) {
			$rssCommTitle = '[Комментарии] ' . stripslashes($rsQuery['title']);
			$newsIdAltUrl = '_' . $cfg['newsId'];
			$newsIdNotAltUrl = '?newsid=' . $cfg['newsId'];
		}
	}

	if ($cfg['userName']) {
		$rsQuery = $db->super_query("SELECT user_id, name, comm_num FROM " . USERPREFIX . "_users WHERE name = '" . $cfg['userName'] . "'");

		if ($rsQuery['user_id'] && $rsQuery['comm_num'] > 0) {
			$rssCommTitle = 'Комментарии пользователя ' . stripslashes($rsQuery['name']);
			$newsIdAltUrl = '_u_' . $rsQuery['user_id'];
			$newsIdNotAltUrl = '?newsid=' . $rsQuery['user_id'];
		}
	}


	if ($config['allow_alt_url'] == "yes") {
		$rssCommUrl = $config['http_home_url'] . 'rss_comm' . $newsIdAltUrl . '.xml';
	}
	else {
		$rssCommUrl = $config['http_home_url'] . 'engine/rss_comm' . $newsIdNotAltUrl . '.php';
	}

	$rssCommMeta = '<link rel="alternate" type="application/rss+xml" title="' . $rssCommTitle . '" href="' . $rssCommUrl . '" />';

	// Результат работы модуля.
	create_cache($cfg['cachePrefix'], $rssCommMeta, $cacheName . $config['skin'], $cfg['cacheSuffix']);
}
echo $rssCommMeta;
?>