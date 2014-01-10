<?php
if(!defined('DATALIFEENGINE') OR !defined('LOGGED_IN')) {
	die("Hacking attempt!");
}

// Первым делом подключаем DLE_API как это ни странно, но в данном случаи это упрощает жизнь разработчика.
include('engine/api/api.class.php');

/**
 * Массив с конфигурацией установщика, ведь удобно иметь одинаковый код для разных установщиков разных модулей.
 * @var array
 */
$cfg = array(
	// Идентификатор модуля (для внедения в админпанель и назначение имени иконки с расширением .png)
	'moduleName'    => 'rss_comm',

	// Название модуля - показывается как в установщике, так и в админке.
	'moduleTitle'   => 'RSS Comments Pro',

	// Описание модуля, для установщика и админки.
	'moduleDescr'   => 'Модуль показа RSS ленты комментариев к новостям и всех последних комментариев',

	// Версия модуля, для установщика
	'moduleVersion' => '1.0',

	// Дата выпуска модуля, для установщика
	'moduleDate'    => '11.01.2014',

	// Версии DLE, поддержваемые модулем, для установщика
	'dleVersion'    => '9.x - 10.x',

	// ID групп, для которых доступно управление модулем в админке.
	'allowGroups'   => '1',

	// Массив с запросами, которые будут выполняться при установке
	'queries'       => array(
		// "SELECT * FROM " . PREFIX . "_post WHERE id = '1'",
		// "SELECT * FROM " . PREFIX . "_usergroups"
	),

	// Устанавливать админку (true/false). Включает показ кнопки установки и удаления админки.
	'installAdmin'  => true,

	// Отображать шаги утановки модуля
	'steps'        => true

);

function setModuleConfig() {
	global $config, $dle_api, $cfg;

	if (!empty($_POST['setconfig'])) {
		$allow_rss_comm = ($_POST['allow_rss_comm']) ? $_POST['allow_rss_comm'] : 'no';
		$rss_comm_number = ($_POST['rss_comm_number']) ? $_POST['rss_comm_number'] : '10';
		$rss_not_category = ($_POST['rss_not_category']) ? $_POST['rss_not_category'] : 'no';
		$rss_not_news_id = ($_POST['rss_not_news_id']) ? $_POST['rss_not_news_id'] : 'no';

		$dle_api->edit_config(
			array(
				'allow_rss_comm'   => $allow_rss_comm,
				'rss_comm_number'  => $rss_comm_number,
				'rss_not_category' => $rss_not_category,
				'rss_not_news_id'  => $rss_not_news_id
			)
		);

		$output = <<<HTML
		<div class="descr">
			<div class="form-field clearfix">
				<div class="lebel">&nbsp;</div>
				<div class="control">
					<p class="green">Настройки модуля успешно сохранены</p>
					<a class="btn" href="{$config['admin_path']}?mod={$cfg['moduleName']}">Вернуться назад</a>
				</div>
			</div>
		</div>
HTML;
	} elseif(!$config['allow_rss_comm']) {
		$output = <<<HTML
		<div class="descr">
			Вы забыли открыть файл <b class="red">engine/data/config.php</b>, найти код:
			<textarea readonly>'version_id' => "{$config['version_id']}",</textarea>
			Ниже добавить:
			<textarea readonly>'rss_comm_number' => '1',

'allow_rss_comm' => 'yes',

'rss_not_category' => 'no',

'rss_not_news_id' => 'no',</textarea>
		</div>

HTML;
	} else {		
		$checked_allow_rss_comm = ($config['allow_rss_comm'] == 'yes') ? 'checked' : '' ;
		$output = <<<HTML
<form method="POST" action="{$_SERVER["PHP_SELF"]}?mod={$cfg['moduleName']}">  
	<input type="hidden" name="setconfig" value="1">          
	<div class="descr">
		<div class="form-field clearfix">
			<div class="lebel">Включить модуль:</div>
			<div class="control">
				<input type="checkbox" value="yes" name="allow_rss_comm" id="allow_rss_comm" {$checked_allow_rss_comm} class="checkbox"><label for="allow_rss_comm"><span></span> Да</label>
			</div>
		</div>
		<div class="form-field clearfix">
			<div class="lebel">Количество комментариев в ленте:</div>
			<div class="control">
				<input type="text" value="{$config['rss_comm_number']}" name="rss_comm_number" style="width: 50px;"> <span class="ttp mini" title="Максимальное количество комментариев в каждой RSS-ленте.">?</span>
			</div>
		</div>
		<div class="form-field clearfix">
			<div class="lebel">Исключаемые категории:</div>
			<div class="control">
				<input type="text" value="{$config['rss_not_category']}" name="rss_not_category" style="width: 150px;"> <span class="ttp mini" title="ID категорий через запятую, которые не будут попадать в rss ленту. Если указать no - будут выводиться все категории.">?</span>
			</div>
		</div>
		<div class="form-field clearfix">
			<div class="lebel">Исключаемые новости:</div>
			<div class="control">
				<input type="text" value="{$config['rss_not_news_id']}" name="rss_not_news_id" style="width: 150px;"> <span class="ttp mini" title="ID новостей через запятую, которые не будут попадать в rss ленту. Если указать no - будут выводиться все категории.">?</span>
			</div>
		</div>
		<div class="form-field clearfix">
			<div class="lebel">&nbsp;</div>
			<div class="control">
					<input class="btn" type="submit" value="Сохранить настройки">
			</div>
		</div>
	</div> <!-- .descr -->
</form>
HTML;
	}

	// Функция возвращает то, что должно быть выведено
	return $output;
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="<?=$config['charset']?>">
	<title><?=$cfg['moduleTitle']?> - Управление модулем</title>
	<meta name="viewport" content="width=device-width">
	<link href="http://fonts.googleapis.com/css?family=Ubuntu+Condensed&subset=latin,cyrillic" rel="stylesheet">
	<style>
		/*Общие стили*/
		html{background: #bdc3c7 url('data:image/png;base64,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') repeat;}
		body{min-width: 800px; max-width: 1200px; padding: 20px;margin: 20px auto;font:normal 14px/18px Arial, Helvetica, sans-serif;background: #f1f1f1;box-shadow: 0 0 15px 0 rgba(0, 0, 0, 0.1);color: #34495e;}
		::-moz-selection {background: #34495e;color: #f1f1f1;text-shadow: 0 1px 1px rgba(0, 0, 0, 0.9);}
		::selection {background: #34495e;color: #f1f1f1;text-shadow: 0 1px 1px rgba(0, 0, 0, 0.9);}
		hr{margin: 18px 0;border: 0;border-top: 1px solid #f5f5f5;border-bottom: 1px solid #bdc3c7;}
		.preview  {display: block;margin: 20px auto 40px;max-width: 100%;}
		.descr  {font: normal 18px/24px "Trebuchet MS", Arial, Helvetica, sans-serif;color: #34495e;margin: 20px -20px;padding: 20px;background: #ecf0f1;-webkit-box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, 0.1), inset 0 -10px 10px -10px rgba(0, 0, 0, 0.1);box-shadow: inset 0 10px 10px -10px rgba(0, 0, 0, 0.1), inset 0 -10px 10px -10px rgba(0, 0, 0, 0.1);text-shadow: 0 1px 0 #fff;}
		b{color: #2980b9;}
		.descr hr  {margin: 18px -20px;}
		.ta-center  {text-align: center;}
		.ta-left {text-align: left;}
		.ta-right {text-align: right;}
		.logo{margin: 0 auto;display: block;}
		a{color: #2980b9;}
		a:hover{text-decoration: none;color: #c0392b;}
		.btn{line-height: 32px;font-size: 100%;margin: 0;vertical-align: baseline;*vertical-align: middle;cursor: pointer;*overflow: visible;background: #3498db;color: #ecf0f1;text-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);border: 0;border-radius: 3px;padding: 0 15px;display: inline-block; text-decoration: none; border-bottom: solid 3px #2980b9; transition: all ease .6s;}
		.btn:hover, .btn.active{background: #e74c3c; border-bottom-color: #c0392b; color: #ecf0f1;}
		.btn-small {line-height: 26px;}
		article,
		.gray{color: #95a5a6;}
		.green{color: #16a085;}
		.red{color: #c0392b;}
		.blue{color: #3498db;}
		h1, h2, h3, h4, h1 b, h2 b, h3 b, h4 b{font-family: 'Ubuntu Condensed', sans-serif;font-weight: normal;}
		h3{margin: 0;}
		h1{line-height: 20px;line-height: 28px;}
		.clr{clear: both;height: 0;overflow: hidden;}
		li{margin-bottom: 20px;color: #2980b9;}
		li li{margin-bottom: 4px;margin-top: 4px;}
		li.div, li li, li h3{color: #34495e;}
		textarea{width: 100%;margin-bottom: 10px;vertical-align: top;-webkit-transition: height 0.2s;-moz-transition: height 0.2s;transition: height 0.2s;outline: none;display: block;color:#f39c12;padding: 5px 10px;font: normal 14px/20px Consolas,'Courier New',monospace;background-color: #2c3e50;white-space: pre;white-space: pre-wrap;word-break: break-all;word-wrap: break-word;text-shadow: none;border: none; border-left: solid 3px #f39c12; box-sizing: border-box; }
		textarea:focus{background: #bdc3c7;border-color: #2980b9; color:#2c3e50;}
		input[type="text"], select {padding: 4px 10px;width: 250px;vertical-align: middle;height: 24px;line-height: 24px;border: solid 1px #95a5a6;display: inline-block;border-radius: 3px;}
		input[type="text"]:focus, select:focus {border-color: #3498db;color:#2c3e50;outline: none;-webkit-box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);-moz-box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);box-shadow: 0 0 0 3px rgba(41, 128, 185, .5);}
		select {height: 32px; width: auto;}
		form {margin-bottom: 10px;}
		.checkbox { display:none; }
		.checkbox + label { cursor: pointer; margin-top: 4px; display: inline-block; }
		.checkbox + label span { display:inline-block; width:18px; height:18px; margin:-1px 4px 0 0; vertical-align:middle; background: #fff; cursor:pointer; border-radius: 4px; border: solid 2px #3498db; }
		.checkbox:checked + label span { background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAICAYAAADN5B7xAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAIJJREFUeNpi+f//PwMhIL6wjQVITQDi10xEKBYEUtuAOBuIGVmgAnkgyZfxVY1oilWB1BYgVgPiRqB8A8iGfCBuAGGggnokxS5A6iSyYpA4I8gPQEkQB6YYxH4FxJOAmAVZMVwD1ERkTTCAohgE4J6GSjTiU4xiA5LbG5AMwAAAAQYAgOM4GiRnHpIAAAAASUVORK5CYII=') no-repeat 50% 50%; border-color: #16a085; }
		.checkbox:disabled + label span, input[type="text"]:disabled {background: #e9e9e9; border-color: #ccc;}
		label + .checkbox + label, input[type="text"] + .checkbox + label {margin-left: 10px;}
		.form-field {margin-bottom: 20px; margin-left: 20px;}
		.lebel {float: left;width: 300px;padding-right: 10px;line-height: 32px; text-align: right;}
		.control {margin-left: 320px;}
		.control input[type="text"] { width: 300px; margin-bottom: 2px; }
		.queries {padding: 10px 0;}
		.form-field-large .lebel {width: 100px;}
		.form-field-large .control {width: 622px;}
		.form-field-large .control input[type="text"] { width: 600px; margin-bottom: 2px; }
		.alert {background: #ebada7; color: #c0392b; text-shadow: none; padding: 20px; margin: 0 -20px; font-weight: bold; text-align: center;}
		.alert+.descr{margin-top: 0;}
		.clearfix:before, .clearfix:after {content: ""; display: table;}
		.clearfix:after {clear: both;}
		.clearfix {*zoom: 1;} 
		.hide {display: none;}
		.fleft {float: left;}
		.fright {float: right;}
		.w33p {width: 33.333%;}
		.d-inline {display: inline;}
		/*Стили для подсказок*/
		.ttp { position: relative; cursor: help; border-bottom: 1px dotted; }
		.ttp.mini { display: inline-block; font-size: 14px; width: 20px; text-align: center; margin-left: 10px; color: #2980b9; border: 1px solid #2980b9; background: #cddee9; border-radius: 4px; }
		.ttp div { display: none; position: absolute; bottom: -1px; left: -1px; z-index: 1000; width: 320px; padding: 10px 20px; text-align: left; box-shadow: 0 3px 0 rgba(41, 128, 185, 0.54); border: 1px solid #2980b9; background: #cddee9; border-radius: 4px; text-shadow: none; color: #34495e; }
	</style>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	<script src="http://cdnjs.cloudflare.com/ajax/libs/autosize.js/1.18.1/jquery.autosize.min.js"></script>
	<script>
		jQuery(document).ready(function ($) {
			/*! http://dimox.name/beautiful-tooltips-with-jquery/ */
			$('span.ttp').each(function(){var el=$(this);var title=el.attr('title');if(title&&title!=''){el.attr('title','').append('<div>'+title+'</div>');var width=el.find('div').width();var height=el.find('div').height();el.hover(function(){el.find('div').clearQueue().delay(200).animate({width:width+20,height:height+20},200).show(200).animate({width:width,height:height},200);},function(){el.find('div').animate({width:width+20,height:height+20},150).animate({width:'hide',height:'hide'},150);}).mouseleave(function(){if(el.children().is(':hidden'))el.find('div').clearQueue();});}});


			jQuery(document).ready(function ($) {
				$('textarea').autosize();
				$('textarea').click(function () {
					$(this).select();
				});
			});
		});
	</script>
</head>
<body>
	<header>
		<div class="clearfix">
			<div class="fleft">
				<a href="<?=$PHP_SELF?>?mod=main" class="btn btn-small"><?=$lang['skin_main']?></a>
				<a class="btn btn-small" href="<?=$PHP_SELF?>?mod=options&amp;action=options" title="Список всех разделов">Список всех разделов</a>
				<a href="<?=$config['http_home_url']?>" target="_blank" class="btn btn-small"><?=$lang['skin_view']?></a>
			</div>
			<div class="fright">
				<?=$lang['skin_name'] . ' ' . $member_id['name'] . ' <small>(' . $user_group[$member_id['user_group']]['group_name'] .')</small> '?>
				<a href="<?=$PHP_SELF?>?action=logout" class="btn btn-small"><?=$lang['skin_logout']?></a>
			</div>
		</div>
		<hr>
		<h1 class="ta-center"><big class="red"><?=$cfg['moduleTitle']?></big> v.<?=$cfg['moduleVersion']?> от <?=$cfg['moduleDate']?></h1>
	</header>
	<section>  

		<h2 class="gray ta-center">Управление параметрами модуля <?=$cfg['moduleTitle']?> для DLE <?=$cfg['dleVersion']?></h2>
		
		<?php 
			$output = setModuleConfig();
			echo $output;
		?>

	<div>Автор модуля: <a href="http://pafnuty.name/" target="_blank">ПафНутиЙ</a> <br> ICQ: 817233 <br> <a href="mailto:pafnuty10@gmail.com">pafnuty10@gmail.com</a></div>
	</section> 	
</body>
</html>
