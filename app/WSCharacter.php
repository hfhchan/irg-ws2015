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
			return [$page, $row];
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

	public function renderCodeChartCutting($class = 'ws2015_cutting', $start=400, $end = 1400, $width=577) {
		$filename = 'cache/' . 'canvas' . $this->data[0] . $class . '.png';
		if (file_exists($filename)) {
?>
<div class="<?=htmlspecialchars($class)?>"><img src="<?=html_safe($filename)?>"></div>
<?
			return;
		}
		$suffix = rand(10000,99999);

		Log::add('Render Char Cutting Start ' . $this->data[0]);

		list($pg_page, $pg_row) = $this->getCodeChartCutting();
		$pg_src = 'IRGN2223IRG_Working_Set2015v4.0-' . sprintf('%03d', $pg_page) . '.png';
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

	window.delay += 300;
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
			var x = 112;
			for (var y = 30; y < 1620; y++) {
				var rgb = imagecolorat(pix, x, y, width);
				if (rgb[0] < 16 && rgb[1] < 16 && rgb[2] < 16) {
					offsets.push(y);
					y += 10;
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

			var top = offsets[<?=$pg_row?>];
			var btm = offsets[<?=($pg_row + 1)?>] + 2;
			
			var new_width = <?=$width?>;
			var new_height = (btm - top) * new_width / (right - left);

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

			image.src = null;
			canvas = null;
		}
	}, delay);
})(window.delay);
</script>
<?
		Log::add('Render Char Cutting End ' . $this->data[0]);
	}

	public function getMatchedCharacter() {
		$ids = parseStringIntoCodepointArray(str_replace(' ', '', $this->data[17]));
		$ids = array_values(array_map(function($d) {
			if ($d[0] === 'U') {
				return codepointToChar($d);
			}
			return $d;
		}, $ids));
		
		if (!env::$readonly && !empty($this->data[17])) {
			$matched = \IDS\getCharByIDS($ids);
		} else {
			$matched = false;
		}
		return $matched;
	}
}
