<?php
/**
 * iPHP - i PHP Framework
 * Copyright (c) 2012 iiiphp.com. All rights reserved.
 *
 * @author coolmoo <iiiphp@qq.com>
 * @site http://www.iiiphp.com
 * @licence http://www.iiiphp.com/license
 * @version 1.0.1
 * @package common
 * @$Id: iPHP.php 2330 2014-01-03 05:19:07Z coolmoo $
 */
defined('iPHP') OR exit('What are you doing?');
defined('iPHP_LIB') OR exit('iPHP vendor need define iPHP_LIB');
iPHP::import(iPHP_LIB . '/htmlpurifier-4.6.0/HTMLPurifier.auto.php');

function CleanHtml($content) {
	$content = stripslashes($content);
	//echo $content,"\n\n\n\n\n\n\n\n";
	$config = HTMLPurifier_Config::createDefault();
	//$config->set('Cache.SerializerPath',iPHP_APP_CACHE);
	$config->set('Core.Encoding', 'UTF-8'); //字符编码

	//允许属性 div table tr td br元素
	$config->set('HTML.AllowedElements', array(
		'ul' => true, 'ol' => true, 'li' => true,
		'br' => true, 'hr' => true, 'div' => true, 'p' => true,
		'strong' => true, 'b' => true, 'em' => true, 'span' => true,
		'blockquote' => true, 'sub' => true, 'sup' => true,
		'img' => true, 'a' => true, 'embed' => true,
	));
	// $config->set('HTML.AllowedAttributes', array(
	//     'img.src',
	//     'a.href','a.target',
	//     'embed.play','embed.loop', 'embed.menu',
	// ));
	$config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
	$config->set('HTML.TidyLevel', 'medium');
	$config->set('AutoFormat.AutoParagraph', true);
	$config->set('Cache.DefinitionImpl', null);
	$config->set('AutoFormat.RemoveEmpty', true);
	//配置 允许flash
	$config->set('HTML.SafeEmbed', true);
	$config->set('HTML.SafeObject', true);
	$config->set('Output.FlashCompat', true);
	//允许<a>的target属性
	$def = $config->getHTMLDefinition(true);
	$def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');
	$def->addAttribute('embed', 'play,', 'Enum#true,false');
	$def->addAttribute('embed', 'loop', 'Enum#true,false');
	$def->addAttribute('embed', 'menu', 'Enum#true,false');
	$def->addAttribute('embed', 'allowfullscreen', 'Enum#true,false');

	$htmlPurifier = new HTMLPurifier($config);
	$content = $htmlPurifier->purify($content);
	return addslashes($content);
}
