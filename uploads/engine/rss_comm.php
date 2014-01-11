<?php
/*
=============================================================================
RSS Comments Pro - RSS для комментариев в DLE 9.x и выше
=============================================================================
Автор:  ПафНутиЙ 
URL:    http://pafnuty.name/
ICQ:    817233 
email:  pafnuty10@gmail.com
=============================================================================
Файл:   rss_comm.php
=============================================================================
*/

define('DATALIFEENGINE', true);
define('ROOT_DIR', '..');
define('ENGINE_DIR', dirname(__FILE__));

@error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE);

$newsid = (isset ($_REQUEST['newsid'])) ? intval($_REQUEST['newsid']) : false;
$userid = (isset ($_REQUEST['userid'])) ? intval($_REQUEST['userid']) : false;

if (($_REQUEST['newsid'] && $newsid <= '0') || ($_REQUEST['userid'] && $userid <= '0')) {
	die('not_aviable');
}

include ENGINE_DIR . '/data/config.php';

if ($config['http_home_url'] == "") {

	$config['http_home_url'] = explode("engine/rss_comm.php", $_SERVER['PHP_SELF']);
	$config['http_home_url'] = reset($config['http_home_url']);
	$config['http_home_url'] = "http://" . $_SERVER['HTTP_HOST'] . $config['http_home_url'];

}

require_once ENGINE_DIR . '/classes/mysql.php';
include_once ENGINE_DIR . '/data/dbconfig.php';
include_once ENGINE_DIR . '/modules/functions.php';
include_once ROOT_DIR . '/language/' . $config['langs'] . '/website.lng';

check_xss();

$rssContent = false;

$config['allow_cache'] = true;

$rssContent = dle_cache('rss', 'comments_' . $newsid . $userid);

if (!$rssContent) {


	function getItem($item = array()) {
		global $config;
		$item['date'] = date("r", strtotime($item['date']) - ($config['date_adjust'] * 60));

		$rssItem = <<<XML

		<item>
			<title>{$item['title']}</title>
			<guid isPermaLink="true">{$item['link']}</guid>
			<link>{$item['link']}</link>
			<description><![CDATA[{$item['description']}]]></description>
			<category><![CDATA[{$item['category']}]]></category>
			<dc:creator>{$item['creator']}</dc:creator>
			<pubDate>{$item['date']}</pubDate>
		</item>
XML;
		return $rssItem;
	}

	function parseText($text) {
		// Функция возможно требует доработки
		if (preg_match_all('/<!--dle_spoiler(.*?)<!--\/dle_spoiler-->/is', $text, $spoilers)) {
			foreach ($spoilers as $spoiler) {
				$text = str_replace($spoiler, '<quote>Для просмотра содержимого спойлера, перейдите к выбранному комментарию.</quote>', $text);
			}
		}
		$text = preg_replace("'\[hide\](.*?)\[/hide\]'si", "<quote>Для просмотра скрытого текста, перейдите к выбранному комментарию.</quote>", $text);

		return $text;
	}

	function getUrl($news) {
		global $config;

		if (!isset($news)) {
			return false;
		}
		else {
			$news['date'] = strtotime($news['date']);
			$news['category'] = intval($news['category']);

			$page = false;
			if ($news['i'] > $config['comm_nummers']) {
				$page = 'page,1,' . ceil($news['i'] / $config['comm_nummers']) . ',';
			}

			if ($config['allow_alt_url'] == "yes") {
				if ($condition = $config['seo_type'] == 1 OR $config['seo_type'] == 2) {
					if ($news['category'] and $config['seo_type'] == 2) {
						$url = $config['http_home_url'] . get_url($news['category']) . "/" . $page . $news['id'] . "-" . $news['alt_name'] . ".html";
					}
					else {
						$url = $config['http_home_url'] . $page . $news['id'] . "-" . $news['alt_name'] . ".html";
					}
				}
				else {
					$url = $config['http_home_url'] . date('Y/m/d/', $news['date']) . $page . $news['alt_name'] . ".html";
				}
			}
			else {
				$url = $config['http_home_url'] . "index.php?newsid=" . $news['id'];
			}

			return $url;
		}
	}


	// Основной код модуля

	/**
	 * Получаем переменные
	 */
	$cat_info = get_vars('category');

	if (!$cat_info) {
		$cat_info = array();

		$db->query("SELECT * FROM " . PREFIX . "_category ORDER BY posi ASC");
		while ($row = $db->get_row()) {

			$cat_info[$row['id']] = array();

			foreach ($row as $key => $value) {
				$cat_info[$row['id']][$key] = $value;
			}

		}
		set_vars("category", $cat_info);
		$db->free();
	}

	$user_group = get_vars("usergroup");

	if (!$user_group) {
		$user_group = array();
		$db->query("SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC");
		while ($row = $db->get_row()) {
			$user_group[$row['id']] = array();
			foreach ($row as $key => $value) {
				$user_group[$row['id']][$key] = $value;
			}
		}
		set_vars("usergroup", $user_group);
		$db->free();
	}

	/**
	 * Формируем условия и переменные на основе конфига движка и модуля
	 */
	
	$member_id['user_group'] = 5;

	$allow_list = explode(',', $user_group[$member_id['user_group']]['allow_cats']);
	
	$not_cat = explode(',', $config['rss_not_category']);

	$not_id = explode(',', $config['rss_not_news_id']);
	
	$config['rss_comm_number'] = ($config['rss_comm_number']) ? intval($config['rss_comm_number']) : '10';

	$config['allow_rss_comm'] = ($config['allow_rss_comm']) ? $config['allow_rss_comm'] : 'no';

	$config['home_title'] = 'комментарии - ' . $config['home_title'];

	$rssItems = '';

	$stop = ($newsid && in_array($newsid, $not_id)) ? true : false ;

	/**
	 * Формируем запрос
	 */

	$join = "LEFT JOIN " . PREFIX . "_post ON " . PREFIX . "_comments.post_id=" . PREFIX . "_post.id ";

	if ($allow_list[0] != "all") {
		if ($config['allow_multi_category']) {
			$where[] = "category regexp '[[:<:]](" . implode('|', $allow_list) . ")[[:>:]]'";
		}
		else {
			$where[] = "category IN ('" . implode("','", $allow_list) . "')";
		}

	}
	
	if ($config['rss_not_category'] != "no") {
		if ($config['allow_multi_category']) {
			$where[] = "category NOT regexp '[[:<:]](" . implode('|', $not_cat) . ")[[:>:]]'";
		} else {
			$where[] = "category NOT IN ('" . implode("','", $not_cat) . "')";
		}
	}
		
	if ($config['rss_not_news_id'] != "no") {
		if ($config['allow_multi_category']) {
			$where[] = "id NOT regexp '[[:<:]](" . implode('|', $not_id) . ")[[:>:]]'";
		} else {
			$where[] = "id NOT IN ('" . implode("','", $not_id) . "')";
		}
	}

	$where[] = PREFIX . "_comments.approve=1";

	if ($newsid) {
		$where[] = PREFIX . "_post.id = '" . $newsid . "'";
	}
	if ($userid) {
		$where[] = PREFIX . "_comments.user_id = '" . $userid . "'";
	}

	if (count($where)) {
		$where = implode(" AND ", $where);
		$where = "WHERE " . $where;
	}
	else {
		$where = "";
	}
	if (!$stop) {
		$comments = $db->super_query(
			"SELECT " . PREFIX . "_comments.id, 
			post_id, 
			" . PREFIX . "_comments.user_id, 
			" . PREFIX . "_comments.date, 
			" . PREFIX . "_comments.autor, 
			" . PREFIX . "_comments.email as gast_email, 
			text, 
			ip, 
			is_register, 
			title, 
			" . PREFIX . "_post.date as newsdate, 
			alt_name, 
			category 
			FROM " . PREFIX . "_comments 
			LEFT JOIN " . PREFIX . "_post 
			ON " . PREFIX . "_comments.post_id=" . PREFIX . "_post.id 
			" . $where . " 
			ORDER BY id DESC
			LIMIT 0, " . $config['rss_comm_number']
			, true
		);
		
	}

	$i = 1;
	if (count($comments) > 0) {

		if ($newsid) {
			$config['home_title'] = htmlspecialchars(strip_tags(stripslashes($news['title'])), ENT_QUOTES, $config['charset']) . ' - ' . $config['home_title'];
		}
		elseif ($userid) {
			$config['home_title'] = htmlspecialchars(strip_tags(stripslashes($news['title'])), ENT_QUOTES, $config['charset']) . ' - ' . $config['home_title'];
		}
		else {
			$config['home_title'] = 'Все ' . $config['home_title'];
		}

		foreach ($comments as $commItem) {
			if ($newsid) {
				$news = array(
					'date'     => $commItem['newsdate'],
					'category' => $commItem['category'],
					'id'       => $commItem['post_id'],
					'alt_name' => $commItem['alt_name'],
					'i'        => $i
				);
				$commLink = $config['http_home_url'] . '?' . $cstart . 'do=lastcomments' . $_uId. '#comment-id-' . $commItem['id'];
				$commLink = getUrl($news) . $_uId . '#comment-id-' . $commItem['id'];
				$commItem['text'] = parseText($commItem['text']);
				$news['category'] = intval($news['category']);

				$rssItems .= getItem(
					array(
						'title'       => 'Комментаий №' . $commItem['id'] . ' [' . $commItem['title'] . ']',
						'link'        => $commLink,
						'description' => $commItem['text'],
						'category'    => get_url($news['category']),
						'creator'     => $commItem['autor'],
						'date'        => $commItem['date']
					)
				);

			} elseif ($userid) {
				$cstart = ($i > $config['comm_nummers']) ? 'cstart=' . ceil($i / $config['comm_nummers']) . '&amp;' : '';

				$commLink = $config['http_home_url'] . '?' . $cstart . 'do=lastcomments&amp;userid-' . $commItem['user_id'] . '#comment-id-' . $commItem['id'];

				$commItem['text'] = parseText($commItem['text']);
				$commItem['category'] = intval($commItem['category']);

				$rssItems .= getItem(
					array(
						'title'       => 'Комментаий №' . $commItem['id'] . ' [' . $commItem['title'] . ']',
						'link'        => $commLink,
						'description' => $commItem['text'],
						'category'    => get_url($commItem['category']),
						'creator'     => $commItem['autor'],
						'date'        => $commItem['date']
					)
				);
			}
			else {

				$cstart = ($i > $config['comm_nummers']) ? 'cstart=' . ceil($i / $config['comm_nummers']) . '&amp;' : '';

				$commLink = $config['http_home_url'] . '?' . $cstart . 'do=lastcomments#comment-id-' . $commItem['id'];

				$commItem['text'] = parseText($commItem['text']);
				$commItem['category'] = intval($commItem['category']);

				$rssItems .= getItem(
					array(
						'title'       => 'Комментаий №' . $commItem['id'] . ' [' . $commItem['title'] . ']',
						'link'        => $commLink,
						'description' => $commItem['text'],
						'category'    => get_url($commItem['category']),
						'creator'     => $commItem['autor'],
						'date'        => $commItem['date']
					)
				);

			}

			$i++;
		}
	}
	else {
		$rssItems .= getItem(
			array(
				'title'       => "Комментариев пока нет",
				'link'        => "",
				'description' => "Комментариев пока нет или у вас нет достаточных прав доступа",
				'category'    => "undefined",
				'creator'     => "DataLife Engine",
				'date'        => ""
			)
		);
	}


	$rssContent = <<<XML
<?xml version="1.0" encoding="{$config['charset']}"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
	<channel>
		<title>{$config['home_title']}</title>
		<link>{$config['http_home_url']}</link>
		<language>ru</language>
		<description>{$config['home_title']}</description>
		<generator>DataLife Engine</generator>
XML;

	if ($config['site_offline'] == "yes" or $config['allow_rss_comm'] == 'no') {

		$rssItems .= getItem(
			array(
				'title'       => "RSS in offline mode",
				'link'        => "",
				'description' => "RSS in offline mode",
				'category'    => "undefined",
				'creator'     => "DataLife Engine",
				'date'        => ""
			)
		);


	}
	else {

		$rssContent .= $rssItems;

	}


	$rssContent .= '
	</channel>
</rss>';

	create_cache('rss', $rssContent, 'comments_' . $newsid . $userid);
}
header('Content-type: application/xml');
echo $rssContent;
?>