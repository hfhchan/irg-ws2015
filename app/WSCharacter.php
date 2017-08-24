<?php

class WSCharacter {

	public $sheet;
	public $data;

	public function __construct(StdClass $result) {
		$this->sheet = $result->sheet;
		$this->data  = $result->data;
		
		// Clear Variants field set to "No"
		if ($this->data[18] === 'No') {
			$this->data[18] = null;
		}

		// Remove PUA
		$this->data[17] = strtr($this->data[17], [
			codepointToChar('U+E832') => codepointToChar('U+9FB8')
		]);
	}
	
	public function getRadicalStroke() {
		if (strpos($this->data[13], '.1') !== false) {
			$rad = substr($this->data[13], 0, -2);
			$simpRad = 1;
		} else {
			$rad = $this->data[13];
			$simpRad = 0;
		}

		return getIdeographForRadical($rad)[0] . ($simpRad ? "'" : '') . ' ' . $rad . ($simpRad ? "'" : '') . '.' . $this->data[14];
	}

	public function getFirstStroke() {
		if ($this->data[15] == '1') return '橫';
		if ($this->data[15] == '2') return '豎';
		if ($this->data[15] == '3') return '撇';
		if ($this->data[15] == '4') return '點';
		if ($this->data[15] == '5') return '折';
		return 'N/A';
	}
	
	public function getTotalStrokes() {
		$strokes = [];
		foreach ([22,27,30,36,38,45] as $col) {
			if (!empty($this->data[$col])) {
				$s = explode(',', $this->data[$col]);
				$s = array_map("trim", $s);
				$strokes = array_merge($strokes, $s);
			}
		}
		return implode(', ', $strokes);
	}

	public function getCodeChartCutting() {
		$find = '## ' . $this->data[0];
		$file0 = file(__DIR__ . '/../data/charts/map.sheet0.txt');
		$found = false;
		$index = 1;
		foreach ($file0 as $row) {
			$row = trim($row);
			if ($row === $find) {
				$found = $index;
			}
			if ($row[4] === '#') {
				if ($found) {
					return [ltrim($row, '# '), $found];
				}
				$index = 0;
			}
			$index++;
		}

		$file1 = file(__DIR__ . '/../data/charts/map.sheet1.txt');
		$found = false;
		$index = 1;
		foreach ($file1 as $row) {
			$row = trim($row);
			if ($row === $find) {
				$found = $index;
			}
			if ($row[4] === '#') {
				if ($found) {
					return [ltrim($row, '# ') + 507, $found];
				}
				$index = 0;
			}
			$index++;
		}

		$file2 = file(__DIR__ . '/../data/charts/map.sheet2.txt');
		$found = false;
		$index = 1;
		foreach ($file2 as $row) {
			$row = trim($row);
			if ($row === $find) {
				$found = $index;
			}
			if ($row[4] === '#') {
				if ($found) {
					return [ltrim($row, '# ') + 518, $found];
				}
				$index = 0;
			}
			$index++;
		}
	}

	public function hasReviewedUnification() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->hasReviewedUnification($this->data[0]);
	}
	public function hasReviewedAttributes() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->hasReviewedAttributes($this->data[0]);
	}
	public function setReviewedUnification() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->setReviewedUnification($this->data[0]);
	}
	public function setReviewedAttributes() {
		static $instance;
		if (!$instance)
			$instance = new DBProcessedInstance();
		return $instance->setReviewedAttributes($this->data[0]);
	}
}
