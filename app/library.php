<?php

Log::add('DB Start');

$db = new PDO('sqlite:../data/review/current-database.sqlite3');
$db->exec('PRAGMA foreign_keys = ON');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Env::$db = $db;

class Env {
	static $db = null;
}

require_once 'SourcesCache.php';
require_once 'CharacterCache.php';
require_once 'WSCharacter.php';
require_once 'DBProcessedInstance.php';

Log::add('DB End');

function loadWorkbook() {
	Log::add('Load File Start');
	$objReader = PHPExcel_IOFactory::createReader('Excel2007');
	$objReader->setReadDataOnly(true);
	$workbook = $objReader->load("../data/IRGN2223IRG_Working_Set2015v4.0_Attributes.xlsx");
	Log::add('Load File End');
	return $workbook;
}
