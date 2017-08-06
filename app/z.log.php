<?
error_reporting(-1);

class Log {
	static $start = 0;
	static $log = [];
	static $disabled = false;
	public static function disable() {
		self::$disabled = true;
	}
	public static function add($name, $info = '') {
		self::$log[] = [microtime(true), $name, $info];
	}
	public static function start() {
		self::$start = microtime(true);
	}
	public static function end() {
		$start = self::$start;
		$last = self::$start;
		echo '<table style="font-size:13px;margin:10px"><col width=120><col width=120><col width=200><col>';
		foreach (self::$log as $entry) {
			echo '<tr><td style="text-align:right;padding-right:10px">';
			echo number_format(($entry[0] - $start) * 1000, 2);
			echo ' ms</td><td style="text-align:right;padding-right:10px">+';
			echo number_format(($entry[0] - $last) * 1000, 2);
			echo ' ms</td><td>';
			echo $entry[1];
			echo '</td><td>';
			echo html_safe($entry[2]);
			echo '</td></tr>';
			$last = $entry[0];
		}
		echo '</table>';
	}
}
Log::start();
register_shutdown_function(function() {
	if (Log::$disabled) {
		return;
	}
	Log::add('Shutdown');
	Log::end();
});

set_exception_handler(function (Throwable $e){
	echo '<div class=center-wrap>';
	echo '<p><b>Uncaught Exception</b><br>';
	echo html_safe($e->getMessage()).'<br>';
	echo '<span style="color:#333;font:13px monospace">@ &nbsp;' . html_safe($e->getFile()) . '('.$e->getLine().')</span>';
	echo '</p>';
	echo '<div style="margin-top:10px"><b>Stack Trace:</b></div>';
	echo '<pre style="color:#333;margin-top:0;font:13px monospace">';
	echo html_safe($e->getTraceAsString());
	echo '</pre>';
	echo '</div>';
	exit;
});

function html_safe($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


function codepointToChar($codepoint) {
	if (preg_match('@^U\+[0-9A-F]{4,5}$@', $codepoint)) {
		return iconv('UTF-32BE', 'UTF-8', pack("H*", str_pad(substr($codepoint, 2), 8, '0', STR_PAD_LEFT)));
	}
	throw new Exception('Invalid Input');
}

function charToCodepoint($char) {
	if (mb_strlen($char, 'UTF-8') === 1) {
		return 'U+'.strtoupper(ltrim(bin2hex(iconv('UTF-8', 'UTF-32BE', $char)),'0'));
	}
	throw new Exception('Invalid Input');
}

function str_startswith($string, $prefix) {
	if (strpos($string, $prefix) === 0) {
		return true;
	}
	return false;
}

function parseStringIntoCodepointArray($utf8) {
	$result = [];
	for ($i = 0; $i < strlen($utf8); $i++) {
		$char = $utf8[$i];
		$ascii = ord($char);
		if ($ascii < 128) {
			$result[] = $char;
		} else if ($ascii < 192) {
		} else if ($ascii < 224) {
			$ascii1 = ord($utf8[$i+1]);
			if( (192 & $ascii1) === 128 ){
				$result[] = substr($utf8, $i, 2);
				$i++;
			}
		} else if ($ascii < 240) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ){
				$unicode = (15 & $ascii) * 4096 +
						   (63 & $ascii1) * 64 +
						   (63 & $ascii2);
				$result[] = 'U+'.strtoupper(dechex($unicode));
				$i += 2;
			}
		} else if ($ascii < 248) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			$ascii3 = ord($utf8[$i+3]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ||
				(192 & $ascii3) === 128 ){
				$unicode = (15 & $ascii) * 262144 +
						   (63 & $ascii1) * 4096 +
						   (63 & $ascii2) * 64 +
						   (63 & $ascii3);
				$result[] = 'U+'.strtoupper(dechex($unicode));
				$i += 3;
			}
		}
	}
	return $result;
}

function getIdeographForSimpRadical($rad) {
	if ($rad === 120) return ['纟'];
	if ($rad === 147) return ['见'];
	if ($rad === 149) return ['讠'];
	if ($rad === 154) return ['贝'];
	if ($rad === 159) return ['车'];
	if ($rad === 167) return ['钅'];
	if ($rad === 168) return ['长'];
	if ($rad === 169) return ['门'];
	if ($rad === 178) return ['韦'];
	if ($rad === 181) return ['页'];
	if ($rad === 182) return ['风'];
	if ($rad === 184) return ['饣'];
	if ($rad === 187) return ['马'];
	if ($rad === 195) return ['鱼'];
	if ($rad === 196) return ['鸟'];
	if ($rad === 197) return ['卤'];
	if ($rad === 199) return ['麦'];
	if ($rad === 211) return ['齿'];
	if ($rad === 212) return ['龙'];
	if ($rad === 213) return ['龟'];
	return getIdeographForRadical($rad);
}

function getIdeographForRadical($rad) {
	$radicals = [
		['一'],
		['丨'],
		['丶'],
		['丿'],
		['乙','⺄'],
		['亅'],
		['二'],
		['亠'],
		['人','亻','𠆢'],
		['儿'], // RAD 10
		['入'],
		['八'],
		['冂'],
		['冖'],
		['冫'],
		['几','𠘨'],
		['凵'],
		['刀','刂'],
		['力'],
		['勹'], // RAD 20
		['匕'],
		['匚'],
		['匸'],
		['十'],
		['卜'],
		['卩'],
		['厂'],
		['厶'],
		['又'],
		['口'], // RAD 30
		['囗'],
		['土'],
		['士'],
		['夂'],
		['夊'],
		['夕'],
		['大'],
		['女'],
		['子'],
		['宀'], // RAD 40
		['寸'],
		['小'],
		['尢','兀'],
		['尸'],
		['屮'],
		['山'],
		['巛'],
		['工'],
		['己'],
		['巾'], // RAD 50
		['干'],
		['幺'],
		['广'],
		['廴'],
		['廾'],
		['弋'],
		['弓'],
		['彐'],
		['彡'],
		['彳'], // RAD 60
		['心','忄'],
		['戈'],
		['戶'],
		['手','扌'],
		['支'],
		['攴','攵'],
		['文'],
		['斗'],
		['斤'],
		['方'], // Rad 70
		['无'],
		['日'],
		['曰'],
		['月'],
		['木'],
		['欠'],
		['止'],
		['歹'],
		['殳'],
		['毋'], // Rad 80
		['比'],
		['毛'],
		['氏'],
		['气'],
		['水','氵'],
		['火','灬'],
		['爪','爫'],
		['父'],
		['爻'],
		['爿'], // Rad 90
		['片'],
		['牙'],
		['牛','牜'],
		['犬','犭'],
		['玄'],
		['玉','𤣩','王'],
		['瓜'],
		['瓦'],
		['甘'],
		['生'], // Rad 100
		['用'],
		['田'],
		['疋'],
		['疒'],
		['癶'],
		['白'],
		['皮'],
		['皿'],
		['目'],
		['矛'], // Rad 110
		['矢'],
		['石'],
		['示'],
		['禸'],
		['禾'],
		['穴'],
		['立'],
		['竹', '𥫗'],
		['米'],
		['糸','糹'], // Rad 120
		['缶'],
		['网','罒'],
		['羊'],
		['羽'],
		['老'],
		['而'],
		['耒'],
		['耳'],
		['聿'],
		['肉','月'], // Rad 130
		['臣'],
		['自'],
		['至'],
		['臼'],
		['舌'],
		['舛'],
		['舟'],
		['艮'],
		['色'],
		['艸','艹'], // Rad 140
		['虍'],
		['虫'],
		['血'],
		['行'],
		['衣','衤'],
		['襾'],
		['見'],
		['角'],
		['言','訁'],
		['谷'], // Rad 150
		['豆'],
		['豕'],
		['豸'],
		['貝'],
		['赤'],
		['走'],
		['足','𧾷','⻊'],
		['身'],
		['車'],
		['辛'], // Rad 160
		['辰'],
		['辵','辶'],
		['邑','阝'],
		['酉'],
		['釆'],
		['里'],
		['金','釒'],
		['長','镸'],
		['門'],
		['阜','阝'], // Rad 170
		['隶'],
		['隹'],
		['雨','⻗'],
		['靑','青'],
		['非'],
		['面'],
		['革'],
		['韋'],
		['韭'],
		['音'], // Rad 180
		['頁'],
		['風'],
		['飛'],
		['食','飠'],
		['首'],
		['香'],
		['馬'],
		['骨'],
		['高','髙'],
		['髟'], // Rad 190
		['鬥'],
		['鬯'],
		['鬲'],
		['鬼'],
		['魚'],
		['鳥'],
		['鹵'],
		['鹿'],
		['麥','麦'],
		['麻'], // Rad 200
		['黃','黄'],
		['黍'],
		['黑'],
		['黹'],
		['黽','黾'],
		['鼎'],
		['鼓'],
		['鼠'],
		['鼻'],
		['齊'], // Rad 210
		['齒'],
		['龍'],
		['龜'],
		['龠']
	];

	return $radicals[($rad - 1)];
}

function getTotalStrokes($codepoint) {
	$totalstrokes = 0;
	$fs = 0;
	foreach (file('../totalstrokes.txt') as $totalstrokesline) {
		if (strpos($totalstrokesline, $codepoint) !== false) {
			$line = trim(substr($totalstrokesline, strlen($codepoint)));
			@list($totalstrokes, $fs) = explode('|', $line);
		}
	}
	return [$totalstrokes, $fs];
}

function isExtensionF($codepoint) {
	$current = hexdec(substr($codepoint, 2));
	if ($current < hexdec('2CEB0') || $current > hexdec('2EBE0')) {
		return false;
	}
	return true;
}

function getExtFImage($codepoint) {
	$double = [
		'2D000',
		'2D480',
		'2DCEF',
		'2E8BF',
	];
	$current = hexdec(substr($codepoint, 2));
	if ($current < hexdec('2CEB0') || $current > hexdec('2EBE0')) {
		throw new Exception('Not Extension F!');
	}
	$offset = $current - hexdec('2CEB0');
	$length = 1;

	foreach ($double as $d) {
		$d = hexdec($d);
		if ($current > $d) {
			$offset += 1;
		}
		if ($current == $d) {
			$length = 2;
		}
	}
	$page = floor($offset / 80);
	$col  = floor($offset / 20) % 4;
	$row  = $offset % 20;
	
	$p  = $page + 1;
	$x1 = 0;
	$y1 = 0;
	$x2 = 0;
	$x2 = 0;
	
	if ($p % 2 === 1) {
		$x1 = 188 + $col * 310;
		$x2 = $x1 + 300;
	} else {
		$x1 = 278 + $col * 310;
		$x2 = $x1 + 300;
	}
	$y1 = 223 + 88.2 * $row;
	$y2 = $y1 + 88 * $length + 3;
	
	return [$p, $x1, floor($y1), $x2, floor($y2)];
}

function renderExtFImage($b) {
	return '<div style="width:'.($b[3] - $b[1]) .'px;height:' . ($b[4] - $b[2]) .'px;overflow:hidden;border:1px solid #ccc">
<img src="../../../Code Charts/Extension F/IRGN2156CodeTable-' . str_pad($b[0], 2, '0', STR_PAD_LEFT) . '.png" style="margin-left:-' . $b[1] .'px;margin-top:-' . $b[2] . 'px">
</div>';
}

require_once '../../../ids/library.php';
