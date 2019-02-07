<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


define('ZBX_PAGE_NO_AUTHERIZATION', 1);

require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/maps.inc.php';

$page['file'] = 'imgstore.php';
$page['type'] = detect_page_type(PAGE_TYPE_IMAGE);

require_once dirname(__FILE__).'/include/page_header.php';

//	VAR		TYPE	OPTIONAL 	FLAGS	VALIDATION	EXCEPTION
$fields = [
	'css' =>			[T_ZBX_INT, O_OPT, P_SYS, null,				null],
	'imageid' =>		[T_ZBX_STR, O_OPT, P_SYS, null,				null],
	'iconid' =>			[T_ZBX_INT, O_OPT, P_SYS, DB_ID,				null],
	'width' =>			[T_ZBX_INT, O_OPT, P_SYS, BETWEEN(1, 2000),	null],
	'height' =>			[T_ZBX_INT, O_OPT, P_SYS, BETWEEN(1, 2000),	null],
	'max_width' =>			[T_ZBX_INT, O_OPT, P_SYS, BETWEEN(1, 2000),	null],
	'max_height' =>			[T_ZBX_INT, O_OPT, P_SYS, BETWEEN(1, 2000),	null],
	'unavailable' =>	[T_ZBX_INT, O_OPT, null, IN([0, 1]),		null],
];
check_fields($fields);

$resize = false;
if (isset($_REQUEST['width']) || isset($_REQUEST['height'])) {
	$resize = true;
	$width = getRequest('width', 0);
	$height = getRequest('height', 0);
}

$limit = false;
if (isset($_REQUEST['max_width']) || isset($_REQUEST['max_height'])) {
	$limit = true;
	$max_width = getRequest('max_width', 0);
	$max_height = getRequest('max_height', 0);
}

if (isset($_REQUEST['css'])) {
	$css = 'div.sysmap_iconid_0 {'.
			' height: 50px;'.
			' width: 50px;'.
			' background-image: url("images/general/no_icon.png"); }'."\n";

	$images = API::Image()->get([
		'output' => ['imageid'],
		'filter' => ['imagetype' => IMAGE_TYPE_ICON],
		'select_image' => true
	]);
	foreach ($images as $image) {
		$image['image'] = base64_decode($image['image']);
		$ico = imagecreatefromstring($image['image']);

		if ($resize) {
			$ico = imageThumb($ico, $width, $height);
		}
		$w = imagesx($ico);
		$h = imagesy($ico);

		$css .= 'div.sysmap_iconid_'.$image['imageid'].'{'.
					' height: '.$h.'px;'.
					' width: '.$w.'px;}';
	}
	echo $css;
}
elseif (isset($_REQUEST['iconid'])) {
	$iconid = getRequest('iconid', 0);
	$unavailable = getRequest('unavailable', 0);

	if ($iconid > 0) {
		$image = get_image_by_imageid($iconid);

		$source = $image['image'] ? imageFromString($image['image']) : get_default_image();
	}
	else {
		$source = get_default_image();
	}

	if ($resize) {
		$source = imageThumb($source, $width, $height);
	}

	if ($unavailable == 1) {
		imagefilter($source, IMG_FILTER_GRAYSCALE);
		imagefilter($source, IMG_FILTER_BRIGHTNESS, 75);
	}

	if ($resize || $unavailable || $iconid <= 0 || !$image['image']) {
		imageOut($source);
	}
	elseif ($limit) {
		$img_info = getimagesizefromstring($image['image']);
		if ($img_info[0] > $max_width || $img_info[1] > $max_height) {
			$source = imageFromString($image['image']);
			$source = imageThumb($source, min($img_info[0], $max_width), min($img_info[1], $max_heigh));
			imageOut($source);
		}
		else {
			echo $image['image'];
		}
	}
	else {
		echo $image['image'];
	}
}
elseif (isset($_REQUEST['imageid'])) {
	$imageid = getRequest('imageid', 0);

	if (CSession::keyExists('image_id')) {
		$image_data = CSession::getValue('image_id');
		if (array_key_exists($imageid, $image_data)) {
			echo $image_data[$imageid];
			unset($image_data[$imageid]);
			CSession::setValue('image_id', $image_data);
		}
	}
}

require_once dirname(__FILE__).'/include/page_footer.php';
