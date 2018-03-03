<?php

class Workbook {

	const VERSION = 5; // WS2015 version
	
	// Indexes
	const DISCUSSION_RECORD = 1;

	// Indexes - Sources
	const SOURCE       = [2,5,7,9,11,4]; // Put SAT before UK since U column can only contain one char
	const UTC_SOURCE   = 2;
	const UK_SOURCE    = 4;
	const T_SOURCE     = 5;
	const K_SOURCE     = 7;
	const SAT_SOURCE   = 9;
	const G_SOURCE     = 11;

	// Indexes - Attributes
	const RADICAL      = 14;
	const STROKE       = 15;
	const FS           = 16;
	const TS_FLAG      = 17;
	const IDS          = 18;
	const TOTAL_STROKE = 21;
	const SIMILAR      = 23;

	const TOTAL_STROKES = [21, 22]; //, 27, 32, 35, 41, 43, 50];
	
	// Indexes - Extras
	const G_EVIDENCE = 40;
	const T_EVIDENCE = 31;
	const K_EVIDENCE = 34;
	const SAT_EVIDENCE = 42;
	const UTC_EVIDENCE = 26;
	const UK_EVIDENCE = 45;

	const UK_TRAD_SIMP = 49;

	static function loadWorkbook() {
		Log::add('Load File Start');
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objReader->setReadDataOnly(true);
		$workbook = $objReader->load("../data/IRGN2269WS2015V5Attributes2017-11-20.xlsx");
		Log::add('Load File End');
		return $workbook;
	}
}
