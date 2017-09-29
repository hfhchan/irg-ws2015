<?php

if (isset($_GET['null'])) {
	header('HTTP/1.1 204 No Content');
	return '';
}

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';

if (!env::$readonly) {
	if (isset($_POST['store'])) {
		if (!preg_match('@^[a-z0-9-_.]+$@', $_POST['store'])) {
			throw new Exception('Invalid filename');
		}
		$data = substr($_POST['data'], strlen('data:image/png;base64,'));
		$data = base64_decode($data);
		file_put_contents('cache/' . $_POST['store'], $data);
	}
}

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>Henry Chan Comments | WS2015v4.0</title>
<style>
[hidden]{display:none}
body{font-family:Arial, "Microsoft Jhenghei",sans-serif;margin:0;-webkit-text-size-adjust:none;-moz-text-size-adjust: none;}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}

.footer{width:1200px;margin:20px auto}

.ws2015_cutting>img,.ws2015_cutting>canvas{width:auto!important;height:auto!important;max-width:100%}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.ws2015_comments{width:960px;margin:0 auto}
.ws2015_comments table{border-collapse:collapse;width:100%}
.ws2015_comments td,.ws2015_comments th{border:1px solid #000;padding:10px}
.ws2015_comments td{vertical-align:top}
.ws2015_comments th{text-align:left}

.comment_block{font-size:24px}
.comment_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.comment_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}
</style>
<script src="jquery.js"></script>
<body>
<section class=ws2015_comments>
	<h2>IRGN2223 IRG Working Set 2015 Version 4.0 (Part 1 &amp; 2)</h2>
	<p>
		Source: Henry Chan and Gienwen CHAU (趙瑾昀).<br>
		Author: Henry Chan<br>
		Type: Individual Contribution to IRG<br>
		Date: Generated on <?=date("Y-m-d")?>
	</p>
<?
$list = DBComments::getList();
$type = array_map(function($cm) {
	if ($cm->type === 'UNIFICATION_LOOSE') {
		return 1000;
	}
	return $cm->getTypeIndex();
}, $list);
$source1 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[10] ? $char->data[10] : 'ZZZ';
}, $list);
$source2 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[4] ? $char->data[4] : 'ZZZ';
}, $list);
$source3 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[6] ? $char->data[6] : 'ZZZ';
}, $list);
$source4 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[8] ? $char->data[8] : 'ZZZ';
}, $list);
$source5 = array_map(function($cm) use ($character_cache) {
	$char = $character_cache->get($cm->getSN());
	return $char->data[2] ? $char->data[2] : 'ZZZ';
}, $list);
array_multisort($type, $source1, $source2, $source3, $source4, $source5, $list);


$chunks = [];
foreach ($list as $item) {
	if (!isset($chunks[$item->type])) {
		$chunks[$item->type] = [];
	}
	$chunks[$item->type][] = $item;
}

foreach ($chunks as $chunk) {
	$type = $chunk[0]->type;
	$type = strtr($type, '_', ' ');
	if (strpos($type, 'ATTRIBUTES') === 0) {
		$shorttype = substr($type, 11);
		if ($shorttype === 'RADICAL') {
			$shorttype = 'Radical';
		}
		if ($shorttype === 'TRAD SIMP') {
			$shorttype = 'T/S Flag';
		}
		$type = 'Attributes (' . $shorttype . ')';
	} else if ($type === 'UNIFICATION LOOSE') {
		$type = 'Unification (Additional References)';
		$shorttype = 'Unification (Reference only)';
	} else {
		$type = ucfirst(strtolower($type));
		$shorttype = $type;
	}

	if ($chunk[0]->type === 'CODEPOINT_CHANGED') {
		continue;
	}

	if ($chunk[0]->type === 'SEMANTIC_VARIANT') {
		continue;
	}

	if ($chunk[0]->type === 'TRAD_VARIANT') {
		continue;
	}

	if ($chunk[0]->type === 'SIMP_VARIANT') {
		continue;
	}

	echo '<h3>' . $type . '</h3>';
	if ($type === 'Unification (Additional References)') {
		echo '<p>These suggested unifications are unifications which may have been carried had looser unification rules for WS2015 been adopted.</p>';
		echo '<p>These unifications are mostly single-case proposed unifications of forms which are typically localized to one or a few sources. It is of my opinion that it is not necessary to encode these forms as separate characters. Submittors may still choose to unify/withdraw these characters if they deem these comments reasonable.</p>';
		echo '<p>The comments here are provided here for reference only.  For certain characters, this table provides etymological justification of why these characters should be unified with their more common forms, or comparison with other variants.</p>';
		echo '<p>If these characters are encoded into Extension G, the proposed unifications may be treated as kSemanticVariant data for Unihan.</p>';
	}
	echo '<table style="table-layout:fixed" border=1>';
	echo '<col width=100>';
	echo '<col width=280>';
	echo '<col width=160>';
	echo '<col width=420>';
	echo '<thead><tr><th>Sn</th><th>Image/Source</th><th>Comment Type</th><th>Description</th></tr></thead>';
	foreach ($chunk as $cm) {

		if ($cm->type === 'OTHER') {
			if (strpos(strtolower($cm->comment), '** note') !== false) {
				continue;
			}
			if (strpos(strtolower($cm->comment), 'private note') !== false) {
				continue;
			}
		}

		echo '<tr>';
		echo '<td><b>'.htmlspecialchars($cm->getSN()).'</b></td>';
		echo '<td>';
		$char = $character_cache->get($cm->getSN());
		$char->renderCodeChartCutting();
		echo '</td>';
		echo '<td><b>'.htmlspecialchars($shorttype).'</b></td>';
		echo '<td>';

		if ($cm->type === 'UNIFICATION' || $cm->type === 'UNIFICATION_LOOSE') {
			$pos1 = strpos($cm->comment, "\n");
			if ($pos1 === false) {
				$str = $cm->comment;
			} else {
				$str = substr($cm->comment, 0, $pos1);
			}
			$pos2 = strpos($str, ';');
			if ($pos2) {
				$str = substr($str, 0, $pos2);
			}
			$str = ' ' . trim($str);
			preg_match_all("/ ([\x{3000}-\x{9FFF}\x{20000}-\x{2FFFF}])/u", $str, $matches);
			foreach ($matches[1] as $match) {
				$codepoint = charToCodepoint($match);
				echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
			}
		}

		$text = nl2br(htmlspecialchars($cm->comment));
		$text = preg_replace('@{?{(([0-9]){5}-([0-9]){3}\\.png)}}?@', '<img src="../comments/\\1" style="max-width:320px">', $text);
		$text = preg_replace_callback('@{{(U\\+[A-F0-9a-f]{4,5})}}@', function ($m) {
			$codepoint = $m[1];
			if (!env::$readonly) {
				if ($codepoint[2] === '2' && $codepoint[3] === 'F') {
					return '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%">';
				}
				return '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%">';
			} else {
				return '';
			}
		}, $text);
		$text = preg_replace_callback('@{{(([0-9]){5})}}@', function ($m) use ($character_cache) {
			$char = $character_cache->get($m[1]);
			ob_start();
			echo '<a href="?id=' . $m[1] . '" target=_blank>';
			$char->renderCodeChartCutting('comment_cutting1', 20, 1400, 2000);
			if ($char->data[1]) {
				$char->renderCodeChartCutting('comment_cutting2', 1260, 2300, 2000);
			}
			echo '</a>';
			return ob_get_clean();
		}, $text);
		echo $text;
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '<br>';
	echo '<br>';
}
?>
</section>

<div class=footer>
	<p>Source Code released at <a href="https://github.com/hfhchan/irg-ws2015">https://github.com/hfhchan/irg-ws2015</a>.</p>
</div>

<script>
var finalize = (function() {
	$('img').each(function() {
		$(this).attr('width', $(this).width());
	});
});
</script>