<?php

class WSCharacter {

	public $sheet;
	public $data;

	public function __construct(StdClass $result) {
		$this->sheet = $result->sheet;
		$this->data  = $result->data;
		
		// Clear Variants field set to "No"
		if ($this->data[Workbook::SIMILAR] === 'No') {
			$this->data[Workbook::SIMILAR] = null;
		}

		// Remove PUA
		$this->data[Workbook::IDS] = strtr($this->data[Workbook::IDS], [
			codepointToChar('U+E832') => codepointToChar('U+9FB8'),
			'_xD876_' => ''
		]);
	}
	
	public function getSources() {
		foreach (Workbook::SOURCE as $source) {
			if (!empty($this->data[$source])) {
				return $this->data[$source];
			}
		}
		return '';
	}

	public function getRadicalStroke() {
		if (strpos($this->data[Workbook::RADICAL], '.1') !== false) {
			$rad = substr($this->data[Workbook::RADICAL], 0, -2);
			$simpRad = 1;
		} else {
			$rad = $this->data[Workbook::RADICAL];
			$simpRad = 0;
		}

		return getIdeographForRadical($rad)[0] . ($simpRad ? "'" : '') . ' ' . $rad . ($simpRad ? "'" : '') . '.' . $this->data[Workbook::STROKE];
	}

	public function getFirstStroke() {
		if ($this->data[Workbook::FS] == '1') return '橫';
		if ($this->data[Workbook::FS] == '2') return '豎';
		if ($this->data[Workbook::FS] == '3') return '撇';
		if ($this->data[Workbook::FS] == '4') return '點';
		if ($this->data[Workbook::FS] == '5') return '折';
		return 'N/A';
	}
	
	public function getTotalStrokes() {
		$strokes = [];
		foreach (Workbook::TOTAL_STROKES as $col) {
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
		$file0 = file_get_contents(__DIR__ . '/../data/charts/map.sheet0.txt');
		$pos1 = strpos($file0, $find);
		if ($pos1 !== false) {
			$pos2 = strpos($file0, '#####', $pos1);
			$pos2 = strpos($file0, "\n" . '## ', $pos2);
			if ($pos2 === false) {
				$pos2 = strlen($file0) - 1;
			}
			$pos3 = strrpos($file0, '#####', $pos1 - strlen($file0));
			if ($pos3 === false) {
				$pos3 = 0;
			} else {
				$pos3 = strpos($file0, "\n" . '## ', $pos3) + 1;
			}
			$section = explode("\n", substr($file0, $pos3, $pos2 - $pos3));
			$page = trim(array_pop($section), " #\r");
			$section = array_map("trim", $section);
			$row = array_search($find, $section) + 1;
			return [0, $page, $row];
		}
		/*
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
					var_dump(ltrim($row, '# '), $found);
					return [ltrim($row, '# '), $found];
				}
				$index = 0;
			}
			$index++;
		}
		*/

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
					return [1, ltrim($row, '# '), $found];
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
					return [2, ltrim($row, '# '), $found];
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

	public function renderPDAM2_2() {
		$class = 'pdam2_2';
		$pdam22 = file_get_contents('../data/charts/pdam2.2/map.txt');
		$pdam22_map = explode("\n", $pdam22);
		$source = $this->getSources();
		foreach ($pdam22_map as $row) {
			if (strpos($row, $source) !== false) {
				$codepoint = substr($row, 0, 5);
				break;
			}
		}
		
		if (!isset($codepoint)) {
			if ($this->sheet == 0) {
				echo '<span style="color:red">No pic in PDAM2.2</div>';
			}
			return;
		}

		$filename = 'cache/' . 'canvas' . $this->data[0] . $class . '.png';
		if (file_exists($filename)) {
?>
<div class="<?=htmlspecialchars($class)?>"><img src="<?=html_safe($filename)?>"></div>
<?
			return;
		}
		$suffix = rand(10000,99999);

		Log::add('Render PDAM2.2 Cutting Start ' . $this->data[0]);

		$decimal = hexdec($codepoint) - hexdec('30000');
		$span = 1;
		if ($decimal > hexdec('867')) {
			$decimal++;
			$span = 2;
		}
		$page = 116 + floor($decimal / 80);
		$col = floor($decimal / 20) % 4;
		$row = $decimal % 20;

		$offset_left = 193 + 310 * $col;
		if (($page % 2) === 1) {
			$offset_left += 90;
		}
		$offset_top = 222 + 88.5 * $row;
		$width = 300;
		$height = ($decimal === hexdec('867') ? '180' : '90');
		$pg_src = 'pdam2.2/-000' . $page . '.png';

?>
<div class="<?=htmlspecialchars($class)?>"><canvas id=canvas<?=$this->data[0]?>-<?=$suffix?> width=577 height=93></canvas></div>
<script>
window.delay = window.delay || 0;
(function(delay) {
	var imagecolorat = function(pix, x, y, width) {
		var offset = y * width + x;
		return [pix[offset * 4], pix[offset * 4 + 1], pix[offset * 4 + 2]];
	}
	var canvas2 = document.getElementById('canvas<?=$this->data[0]?>-<?=$suffix?>');
	var ctx2    = canvas2.getContext('2d');

	window.delay += 50;
	window.setTimeout(function() {

		var image   = new Image();
		image.src = '../data/charts/' + <?=json_encode($pg_src)?>;
		image.onload = function() {
			var canvas = document.createElement('canvas'),
				ctx = canvas.getContext('2d');

			var width = image.naturalWidth;
			var height = image.naturalHeight;
			canvas.width = width;
			canvas.height = height;
			ctx.drawImage(image, 0, 0);

			var left = <?=$offset_left?>;
			var top = <?=$offset_top?>;
			var new_width = <?=$width?>;
			var new_height = <?=$height?>;

			canvas2.width = new_width;
			canvas2.height = new_height;
			ctx2.drawImage(image, left, top, new_width, new_height, 0, 0, new_width, new_height);

			window.setTimeout(function() {
				var imgAsDataURL = canvas2.toDataURL("image/png");
				$.post('list.php', {
					'store': "canvas<?=$this->data[0]?><?=$class?>.png",
					"data": imgAsDataURL
				});
			}, 300);

			image.src = 'about:blank';
			canvas = null;
		}
	}, delay);
})(window.delay);
</script>
<?
		Log::add('Render PDAM2.2 Cutting End ' . $this->data[0]);
	}

	public function renderCodeChartCutting($class = 'ws2015_cutting', $start=280, $end = 1540, $width=577) {
		list($pg_sheet, $pg_page, $pg_row) = $this->getCodeChartCutting();
		if ($class == 'ws2015_cutting' && $pg_sheet == 0 && $pg_page == 387) {
			$start = 600;
			$end = 1700;
		}
		if ($class == 'ws2015_cutting' && $pg_sheet == 0 && $pg_page == 24) {
			$start += 80;
			$end += 80;
		}
		$filename = 'cache/' . 'canvas' . $this->data[0] . $class . '.png';
		if (file_exists($filename)) {
?>
<div class="<?=htmlspecialchars($class)?>"><img src="<?=html_safe($filename)?>"></div>
<?
			return;
		}
		$suffix = rand(10000,99999);

		Log::add('Render Char Cutting Start ' . $this->data[0]);

		list($pg_sheet, $pg_page, $pg_row) = $this->getCodeChartCutting();
		if ($pg_sheet === 0) {
			$pg_src = 'sheet0/charts-000' . sprintf('%03d', $pg_page) . '.png';
		}
		if ($pg_sheet === 1) {
			$pg_src = 'sheet1/chart2--000' . sprintf('%03d', $pg_page) . '.png';
		}
		if ($pg_sheet === 2) {
			$pg_src = 'sheet2/chart1--000' . sprintf('%03d', $pg_page) . '.png';
		}
?>
<div class="<?=htmlspecialchars($class)?>"><canvas id=canvas<?=$this->data[0]?>-<?=$suffix?> width=577 height=93></canvas></div>
<script>
window.delay = window.delay || 0;
(function(delay) {
	var imagecolorat = function(pix, x, y, width) {
		var offset = y * width + x;
		return [pix[offset * 4], pix[offset * 4 + 1], pix[offset * 4 + 2]];
	}
	var canvas2 = document.getElementById('canvas<?=$this->data[0]?>-<?=$suffix?>');
	var ctx2    = canvas2.getContext('2d');

	window.delay += 50;
	window.setTimeout(function() {

		var image   = new Image();
		image.src = '../data/charts/' + <?=json_encode($pg_src)?>;
		image.onload = function() {
			var canvas = document.createElement('canvas'),
				ctx = canvas.getContext('2d');

			var width = image.naturalWidth;
			var height = image.naturalHeight;
			canvas.width = width;
			canvas.height = height;
			ctx.drawImage(image, 0, 0);
			var imgd = ctx.getImageData(0, 0, width, height);
			var pix = imgd.data;
			
			var offsets = [];
			var offsets2 = [];
			var x = 112;
			for (var y = 30; y < 1620; y++) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 16 && rgb[1] < 16 && rgb[2] < 16) {
					offsets.push(y);
					y += 10;
				}
			}
			for (var y = 1620; y > 30; y--) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 16 && rgb[1] < 16 && rgb[2] < 16) {
					offsets2.unshift(y);
					y -= 10;
				}
			}
			
			var left = <?=$start?>;
			var y = offsets[0] + 4;
			for (var x = <?=$start?>; x < <?=($start+200)?>; x++) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 75 && rgb[1] < 75 && rgb[2] < 75) {
					left = x;
					break;
				}
			}
			var right = <?=$end?>;
			for (var x = <?=$end?>; x > <?=$end-140?>; x--) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 75 && rgb[1] < 75 && rgb[2] < 75) {
					right = x;
					break;
				}
			}

			right += 2;
			console.log(left, right);

			var top = offsets[<?=$pg_row?>];
			var btm = offsets2[<?=($pg_row + 1)?>];
			console.log(offsets[<?=($pg_row + 1)?>]);
			console.log(offsets2[<?=($pg_row + 1)?>]);

			var new_width = <?=$width?>;
			var new_height = Math.ceil((btm - top) * new_width / (right - left));

			canvas2.width = new_width;
			canvas2.height = new_height;
			ctx2.drawImage(image, left, top, right - left, btm - top, 0, 0, new_width, new_height);

			window.setTimeout(function() {
				var imgAsDataURL = canvas2.toDataURL("image/png");
				$.post('list.php', {
					'store': "canvas<?=$this->data[0]?><?=$class?>.png",
					"data": imgAsDataURL
				});
			}, 300);

			image.src = 'about:blank';
			canvas = null;
		}
	}, delay);
})(window.delay);
</script>
<?
		Log::add('Render Char Cutting End ' . $this->data[0]);
	}

	public function getMatchedCharacter() {
		$ids = parseStringIntoCodepointArray(str_replace(' ', '', $this->data[Workbook::IDS]));
		$ids = array_values(array_map(function($d) {
			if ($d[0] === 'U') {
				return codepointToChar($d);
			}
			return $d;
		}, $ids));
		
		if (!env::$readonly && !empty($this->data[Workbook::SIMILAR])) {
			$matched = \IDS\getCharByIDS($ids);
		} else {
			$matched = false;
		}
		return $matched;
	}
}
