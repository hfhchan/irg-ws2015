<?php

class CharacterCache {

	const SHEETS = [
		'IRGN2223IRG_Working_Set2015v4.0',
		'IRGN2223Unified&WithdrawnV4.0',
		'IRGN2223PostponedV4.0'
	];

	public function generate() {
		$workbook = loadWorkbook();
		$result = $this->generateForWorksheet($workbook, 0);
		$result = $this->generateForWorksheet($workbook, 1);
		$result = $this->generateForWorksheet($workbook, 2);
	}

	public function generateForWorksheet($workbook, int $sheet) {
		Log::add('Generate Cache for Worksheet ' . $sheet . ' Loop Data Start');
		$worksheet     = $workbook->getSheet($sheet);
		$highestRow    = $worksheet->getHighestRow(); 
		$highestColumn = $worksheet->getHighestColumn();

		$firstRow = $worksheet->rangeToArray('A1:' . $highestColumn . '1', null, false, false)[0];
		if (!file_exists('../data/attributes-cache/sheet.'.$sheet.'.firstRow.json')) {
			file_put_contents('../data/attributes-cache/sheet.'.$sheet.'.firstRow.json', json_encode($firstRow));
		}

		for ($row = 2; $row <= $highestRow; $row++) {
			$rowData = $worksheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, false, false)[0];
			$sq_number = trim($rowData[0]);
			$data        = new StdClass();
			$data->sheet = $sheet;
			$data->data  = $rowData;
			if (!file_exists('../data/attributes-cache/' . $sq_number . '.json')) {
				file_put_contents('../data/attributes-cache/' . $sq_number . '.json', json_encode($data));
			}
		}
		Log::add('Loop Data End');
		return null;
	}

	public function get($sq_number) {
		if (file_exists('../data/attributes-cache/' . $sq_number . '.json')) {
			$firstRow = json_decode(file_get_contents('../data/attributes-cache/sheet.0.firstRow.json'));
			$result = json_decode(file_get_contents('../data/attributes-cache/' . $sq_number . '.json'));
			return new WSCharacter($result);
		}
		throw new Exception('Not Found');
	}

	public function getColumns() {
		return json_decode(file_get_contents('../data/attributes-cache/sheet.0.firstRow.json'));
	}
}

