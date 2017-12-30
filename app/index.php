<?php

if (isset($_GET['null'])) {
	header('HTTP/1.1 204 No Content');
	return '';
}

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();

if (!env::$readonly) {
	// Run once to generate the attribute cache!
	if (isset($_GET['generate_cache'])) {
		$character_cache->generate();
		$sources_cache->generate();
		$ids_cache->generate();
	}

	// New Comment
	if (isset($_POST['comment']) && isset($_POST['sq_number']) && isset($_POST['type'])) {
		DBComments::save($_POST['sq_number'], $_POST['type'], $_POST['comment']);
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit;
	}
}

// Get Prev Unprocessed
if (isset($_GET['left'])) {
	if (isset($_GET['find'])) {
		$prev = $_GET['find'];
		while(true) {
			if ($prev) {
				$result = $sources_cache->find($prev);
				foreach ($result as $sq_number) {
					$char = $character_cache->get($sq_number);
					if (!$char->hasReviewedUnification()) {
						break 2;
					}
				}
				$prev = $sources_cache->findPrev($prev);
			} else {
				break;
			}
		}
		header('Location: ?find=' . $prev);
		exit;
	}
	$sq_number = $_GET['id'];
	$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
	while (true) {
		$char = $character_cache->get($prev);
		if (!$char) {
			break;
		}
		if (!$char->hasReviewedUnification()) {
			break;
		}
		$prev = str_pad(intval(ltrim($prev, '0')) - 1, 5, '0', STR_PAD_LEFT);
	}
	header('Location: ?id=' . $prev);
	exit;
}

// Get Next Unprocessed
if (isset($_GET['right'])) {	
	if (isset($_GET['find'])) {
		$next = $_GET['find'];
		while(true) {
			if ($next) {
				$result = $sources_cache->find($next);
				foreach ($result as $sq_number) {
					$char = $character_cache->get($sq_number);
					if (!$char->hasReviewedUnification()) {
						break 2;
					}
				}
				$next = $sources_cache->findNext($next);
			} else {
				break;
			}
		}
		header('Location: ?find=' . $next);
		exit;
	}
	$sq_number = $_GET['id'];
	$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);
	while (true) {
		$char = $character_cache->get($next);
		if (!$char) {
			break;
		}
		if (!$char->hasReviewedUnification()) {
			break;
		}
		$next = str_pad(intval(ltrim($next, '0')) + 1, 5, '0', STR_PAD_LEFT);
	}
	header('Location: ?id=' . $next);
	exit;
}

if (!Env::$readonly && isset($_GET['mark']) && isset($_GET['id'])) {
	if (isset($_GET['keyword'])) {
		$base = '?keyword=' . $_GET['keyword'];
	} else if (isset($_GET['find'])) {
		$base = '?find=' . $_GET['find'];
	} else if (isset($_GET['ids'])) {
		$base = '?ids=' . $_GET['ids'];
	} else {
		$base = '?id=' . $_GET['id'];
	}
	$char = $character_cache->get($_GET['id']);	
	if ($_GET['mark'] == 3) {
		$char->setReviewedUnification();
		$char->setReviewedAttributes();
	}
	if ($_GET['mark'] == 1) {
		$char->setReviewedUnification();
	}
	if ($_GET['mark'] == 2) {
		$char->setReviewedAttributes();
	}
	header('Location: ' . $base);
	exit;
}

if (!Env::$readonly && isset($_GET['add_strokes'])) {
	if (isset($_GET['keyword'])) {
		$base = '?keyword=' . $_GET['keyword'];
	} else if (isset($_GET['find'])) {
		$base = '?find=' . $_GET['find'];
	} else if (isset($_GET['ids'])) {
		$base = '?ids=' . $_GET['ids'];
	} else {
		$base = '?id=' . $_GET['id'];
	}
	if (preg_match('@^U\+[0-9A-F]?[0-9A-F][0-9A-F][0-9A-F][0-9A-F] [0-9]+\|[0-9]+$@', $_GET['add_strokes'])) {
		$fp = fopen('../totalstrokes.txt', 'a');
		fwrite($fp, $_GET['add_strokes'] . "\r\n");
		fclose($fp);
	} else {
		die('Format Mismatch');
	}
	header('Location: ' . $base);
	exit;
}

$firstRow = $character_cache->getColumns();

Log::add('Fetch Char');

$data = [];
if (isset($_GET['ids'])) {
	$_GET['ids'] = trim(strtr($_GET['ids'], [' ' => '']));
	if (!empty($_GET['ids'])) {
		$result = $ids_cache->find($_GET['ids']);
		if (empty($result)) {
			throw new NotFoundException('Not Found');
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->get($sq_number);

			$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
			$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

			$char->prev2 = '?left&id=' . $sq_number;
			$char->prev = [$prev, '?id=' . $prev];
			$char->curr = $sq_number;
			$char->next = [$next, '?id=' . $next];
			$char->next2 = '?right&id=' . $sq_number;
			$char->base_path = '?id=' . urlencode($sq_number) . '&ids=' . urlencode($_GET['ids']);

			$data[] = $char;
		}
	}
} else if (isset($_GET['keyword'])) {
	if (!empty($_GET['keyword'])) {
		$result = DBComments::getByKeyword($_GET['keyword']);
		if (empty($result)) {
			throw new NotFoundException('Not Found');
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->get($sq_number);

			$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
			$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

			$char->prev2 = '?left&id=' . $sq_number;
			$char->prev = [$prev, '?id=' . $prev];
			$char->curr = $sq_number;
			$char->next = [$next, '?id=' . $next];
			$char->next2 = '?right&id=' . $sq_number;
			$char->base_path = '?id=' . urlencode($sq_number) . '&keyword=' . urlencode($_GET['keyword']);

			$data[] = $char;
		}
	} else {
		throw new NotFoundException('No Keyword Specified');
	}
} else if (isset($_GET['find']) || !isset($_GET['id'])) {
	if (empty($_GET['find'])) {
		$_GET['find'] = $sources_cache->getFirst();
	}
	$_GET['find'] = trim(strtr($_GET['find'], [' ' => '']));
	if (!empty($_GET['find'])) {
		if (!preg_match('@^[A-Za-z0-9-_\\.]+$@', $_GET['find'])) {
			throw new Exception('Invalid ID');
		}
		$result = $sources_cache->find($_GET['find']);
		if (empty($result)) {
			throw new NotFoundException('Not Found');
		}
		foreach ($result as $sq_number) {
			$char = $character_cache->get($sq_number);
			$prev = $sources_cache->findPrev($_GET['find']);
			$next = $sources_cache->findNext($_GET['find']);

			$char->prev2 = '?left&find=' . $prev;;
			$char->prev = [$prev, '?find=' . $prev];
			$char->curr = $_GET['find'];
			$char->next = [$next, '?find=' . $next];
			$char->next2 = '?right&find=' . $next;
			$char->base_path = '?id=' . urlencode($sq_number) . '&find=' . urlencode($_GET['find']);
			$data[] = $char;
		}
	}
} else if (isset($_GET['id'])) {
	if (!preg_match('@^[0-9]{5}$@', $_GET['id'])) {
		throw new Exception('Invalid ID');
	}
	$sq_number = trim($_GET['id']);
	$char = $character_cache->get($sq_number);

	$prev = str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT);
	$next = str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT);

	$char->prev2 = '?left&id=' . $sq_number;
	$char->prev = [$prev, '?id=' . $prev];
	$char->curr = $sq_number;
	$char->next = [$next, '?id=' . $next];
	$char->next2 = '?right&id=' . $sq_number;
	$char->base_path = '?id=' . urlencode($sq_number);

	$data[] = $char;
}

Log::add('Fetch Char End');

?>
<!doctype html>
<meta charset=utf-8>
<meta name=viewport content="width=initial-width,initial-scale=1">
<title>
<?
	if (isset($_GET['ids'])) {
		echo 'IDS Lookup ' . htmlspecialchars($_GET['ids']);
	} else if (isset($_GET['find'])) {
		echo htmlspecialchars(trim($_GET['find']));
	} else {
		echo $data[0]->data[0] . ' | ' . $data[0]->data[17];
	}
?> | WS2015v4.0</title>
<style>
[hidden]{display:none}
body{font-family:Arial, "Microsoft Jhenghei",sans-serif;background:#eee;margin:0;-webkit-text-size-adjust:none;-moz-text-size-adjust: none;}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}

.ws2015_char{width:1160px;padding:20px;margin:10px auto;background:#fff;border:1px solid #ccc}
.ws2015_char_nav{font-size:24px;display:grid;grid-template-columns:auto 1fr 1fr 1fr auto;background:#def;margin:-20px -20px 20px;align-items:center;border-bottom:1px solid #ccc}
.ws2015_char_nav a{display:block;padding:10px 20px;color:#009;text-decoration:none}
.ws2015_char_nav a:hover{background:#cce3ff}

.ws2015_chart_table{border:1px solid #333;display:grid;grid-template-columns:84px 140px 1fr 358px}
.ws2015_chart_table>div:first-child{border-left:none}
.ws2015_chart_table>div{border-left:1px solid #333;text-align:center;font-size:16px}
.ws2015_chart_sn{padding:10px;display:grid;align-items:center}
.ws2015_chart_attributes{display:grid;grid-template-rows:1fr 1fr 1fr}
.ws2015_chart_attributes{padding:0!important}
.ws2015_chart_attributes>div:first-child{border-top:none}
.ws2015_chart_attributes>div{border-top:1px solid #333}
.ws2015_chart_table_sources{width:100%;table-layout:fixed;border-collapse:collapse;border:hidden}
.ws2015_chart_table_sources td{border:1px solid #333;padding:10px 5px;text-align:center}
.ws2016_chart_table_discussion{display:grid;align-content:center;text-align:left!important;padding:10px;overflow:auto}
.ids_component{margin:0 2px}
.sheet-1{background:#999;opacity:.6}
.sheet-2{background:#ff0}

.ws2015_cutting{margin-left:225px}

.ws2015_content{display:grid;grid-template-columns:802px 1fr;grid-column-gap:20px;margin-top:10px}

.ws2015_similar_char{border:1px solid #ccc;padding:10px;margin-bottom:10px}
.ws2015_evidence img{display:block;max-width:800px;max-height:400px;object-fit:contain;border:1px solid #ccc;margin:10px auto}
.ws2015_evidence img.full{max-height:none}

.ws2015_right{}
.ws2015_right h2{margin:10px 0}

a.review{font-size:16px;border:1px solid #ccc;padding:4px 20px;display:block;margin:10px auto;text-align:center;max-width:200px;background:#eee;text-decoration:none;color:#03c;font-weight:bold}
a.review_all{font-size:16px;border:1px solid #ccc;padding:4px 12px;display:block;margin:10px auto;text-align:center;max-width:200px;background:#eee;text-decoration:none;color:#03c;font-weight:bold}

#findbar{padding:10px;background:#fff;border-bottom:1px solid #ccc}
#findbar>div{width:1160px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;justify-items:center}
#findbar form{display:flex}
#findbar form>div{margin-right:5px;flex-shrink:none;flex-grow:none}
#search-1,#search-2,#search-3,#search-4{border:1px solid #999;padding:2px 4px}
#find-1,#find-2,#find-3,#find-4{background:#eee;color:#000;border:1px solid #ccc;padding:2px 12px;line-height:1.2}

.footer{width:1200px;margin:20px auto}

.comment_cutting1>img,.comment_cutting1>canvas{width:auto!important;height:auto!important;max-width:100%}
.comment_cutting2>img,.comment_cutting2>canvas{width:auto!important;height:auto!important;max-width:100%}

.ws2015_comments{max-width:720px;margin:0 auto}
.ws2015_comments table{border-collapse:collapse;width:100%}
.ws2015_comments td,.ws2015_comments th{border-top:1px solid #ccc;padding:10px}
.ws2015_comments td{vertical-align:top}
.ws2015_comments th{text-align:left}

.comment_block{font-size:24px}
.comment_block select {font-size:20px;display:block;border:1px solid #999;padding:4px;margin:10px 0;font-family:inherit}
.comment_block textarea{display:block;width:-webkit-fill-available;width:-moz-available;min-height:200px;border:1px solid #999;padding:4px;font-family:inherit}
.comment_submit{font-size:20px;border:none;padding:4px 20px;min-width:200px;text-align:center;background:#9cf;display:block;margin:10px 0;font-family:inherit}

@media (min-width:810px) {
	.ws2015_chart_table_sources{border-left:1px solid #333}
}
@media (max-width:800px) {
	#findbar > div{display:block;width:auto}
	.ws2015_char_nav{font-size:24px}
	.ws2015_char_nav a{padding:5px}
	.ws2015_char{width:auto;padding:10px}
	.ws2015_char_nav{margin:-10px -10px 10px}
	.ws2015_chart_table{grid-template-columns:1fr 2fr;grid-template-rows:minmax(60pt, auto) auto auto}
	.ws2016_chart_table_discussion{grid-row:2;grid-column:1 / 3;border-top:1px solid #333;border-left:none!important}
	.ws2015_chart_table_sources{grid-row:3;grid-column:1 / 3;border-top:1px solid #333;table-layout:auto}
	.ws2015_chart_table_sources td{white-space:nowrap;width:20%}
	.ws2015_cutting{margin-left:0}
	.ws2015_cutting canvas{width:100%;height:auto}
	.ws2015_cutting img{width:100%;height:auto}
	.ws2015_content{display:block}
	.ws2015_evidence img{max-width:calc(100% - 2px)}
	.footer{width:auto;padding:0 20px}
	.ws2015_similar_char>img{width:100%;display:block}
}
@media (max-width:620px) {
	.ws2015_char_nav{font-size:12px;line-height:24px}
	.ws2015_char_nav a{padding:2px 5px}
	#nav_next{text-align:right}
}
</style>
<script src="jquery.js"></script>
<body>
<div id=findbar>
	<div>
		<div>
			<form method=get autocomplete=off style="display:flex" id=search-char-1 role=search>
				<div style="width:160px">Find by Source (f):</div>
				<div><input id=search-1 type=text name=find value="<?=html_safe(isset($_GET['find']) ? $_GET['find'] : '')?>" accesskey=f></div>
				<div><input id=find-1 type=submit value=Find></div>
			</form>
			<form method=get autocomplete=off style="display:flex" id=search-char-2 role=search>
				<div style="width:160px">Find by Serial No (s):</div>
				<div><input id=search-2 name=id value="<?=html_safe(isset($_GET['id']) ? $_GET['id'] : '')?>" accesskey=s></div>
				<div><input id=find-2 type=submit value=Find></div>
			</form>
		</div>
		<div>
			<form method=get autocomplete=off style="display:flex" id=search-char-3 role=search>
				<div style="width:160px">Find by IDS (i):</div>
				<div><input id=search-3 name=ids value="<?=html_safe(isset($_GET['ids']) ? $_GET['ids'] : '')?>" accesskey=i></div>
				<div><input id=find-3 type=submit value=Find></div>
			</form>
			<form method=get autocomplete=off style="display:flex" id=search-char-4 role=search>
				<div style="width:160px">Find by Keyword (k):</div>
				<div><input id=search-4 name=keyword value="<?=html_safe(isset($_GET['keyword']) ? $_GET['keyword'] : '')?>" accesskey=k></div>
				<div><input id=find-4 type=submit value=Find></div>
			</form>
		</div>
	</div>
</div>
<?
if (Env::$readonly) {
	define('EVIDENCE_PATH', 'https://raw.githubusercontent.com/hfhchan/irg-ws2015/5d22fba4/data');
} else {
	define('EVIDENCE_PATH', '../data');
}

foreach ($data as $char) {
	Log::add('Render Char Start ' . $char->data[0]);
	$rowData  = $char->data;
	$sq_number = $char->data[0];
	

?>

<div class=ws2015_char>
	<div class=ws2015_char_nav>
<? if ($char->prev2) { ?>
		<div><a href="<?=$char->prev2?>" id=nav_prev>&laquo;</a></div>
<? } ?>
		<div align=left><a href="<?=$char->prev[1]?>"><?=$char->prev[0]?></a></div>
		<div align=center><b><?=$char->curr?></b></div>
		<div align=right><a href="<?=$char->next[1]?>"><?=$char->next[0]?></a></div>
<? if ($char->next2) { ?>
		<div><a href="<?=$char->next2?>" id=nav_next>&raquo;</a></div>
<? } ?>
	</div>

	<h2 hidden>Character Info</h2>
	<div class="ws2015_chart_table sheet-<?=$char->sheet?>">
		<div class=ws2015_chart_sn style="padding:10px;display:grid;align-items:center"><?=$rowData[0]?><br><?=$rowData[16] ? '簡' : '繁';?></div>
		<div class=ws2015_chart_attributes style="display:grid;grid-template-rows:1fr 1fr 1fr">
			<div style="display:grid;align-items:center"><?=$char->getRadicalStroke()?></div>
			<div style="display:grid;align-items:center">
				<div>
<?
		$ids = parseStringIntoCodepointArray($rowData[17]);
		foreach ($ids as $component) {
			if (!empty(trim($component))) {
				if ($component[0] === 'U') {
					if (!env::$readonly) echo '<a href="../../../fonts/gen-m.php?name=u'.substr($component, 2).'" target=_blank class=ids_component>';
					else echo '<span>';
					echo codepointToChar($component);
					if (!env::$readonly) echo '</a>';
					else echo '</span>';
				} else {
					echo html_safe($component);
				}
			}
		}
		if (empty($rowData[17])) {
			echo '<span style="color:#999;font-family:sans-serif">(Empty)</span>';
		}
?>
				</div>
			</div>
			<div style="display:grid;grid-template-columns:1fr 2fr">
				<div style="border-right:1px solid #333;display:grid;align-items:center"><?=$char->getFirstStroke()?></div>
				<div style="display:grid;align-items:center"><?=$char->getTotalStrokes()?></div>
			</div>
		</div>
		<table class=ws2015_chart_table_sources>
			<tr>
			<td rowspan="3">
				<?php if (isset($rowData[10]) || isset($rowData[11])) {?>
					<img src="<?=EVIDENCE_PATH?>/g-bitmap/<?=substr($rowData[11], 0, -4)?>.png" width="32" height="32"><br>
					<?=$rowData[10]?>
				<?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($rowData[4]) || isset($rowData[5])) {?>
					<img src="<?=EVIDENCE_PATH?>/t-bitmap/<?=substr($rowData[5], 0, -4)?>.bmp" width="32" height="32"><br>
					<?=$rowData[4]?>
				<?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($rowData[6]) || isset($rowData[7])) {?>
					<img src="<?=EVIDENCE_PATH?>/k-bitmap/<?=substr($rowData[7], 0, -4)?>.png" width="32" height="32"><br>
					<?=$rowData[6]?><?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($rowData[8]) || isset($rowData[9])) {?>
					<img src="https://glyphwiki.org/glyph/sat_g9<?=substr($rowData[9], 4, -4)?>.svg" width="32" height="32"><br>
					<?=$rowData[8]?>
				<?php } ?>
			</td>
			<td rowspan="3">
				<?php if (isset($rowData[2]) || isset($rowData[3])) {?>
					<?php if (empty($rowData[39])) {?>
						<img src="<?=EVIDENCE_PATH?>/utc-bitmap/<?=substr($rowData[3], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[2]?>
					<?php } else { ?>
						<img src="<?=EVIDENCE_PATH?>/uk-bitmap/<?=substr($rowData[3], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[2]?> (UK)
					<?php } ?>
				<?php } ?>
			</td>
			</tr>
		</table>
		<div class=ws2016_chart_table_discussion>
			<div>
				<? if ($char->sheet) echo '<b>'.CharacterCache::SHEETS[$char->sheet] . '</b><br>'; ?>
				<?=$rowData[1]?>
<?
		if ((isset($rowData[6]) || isset($rowData[7])) && file_exists('../data/k-bitmap/' . substr($rowData[7], 0, -4) . '-updated.png')) {
			echo '<br>Glyph Updated: <img src="' . EVIDENCE_PATH . '/k-bitmap/' . substr($rowData[7], 0, -4) . '-updated.png" width="32" height="32">';
		}
?>
			</div>
		</div>
	</div>
	
	<? $char->renderCodeChartCutting(); ?>
	
	<div class=ws2015_content>
		<section class=ws2015_left>
<?
	$matched = $char->getMatchedCharacter();
	if ($matched && substr($matched, 0, 1) !== '&') {
		echo '<p style="background:red;font-size:24px;margin:10px 0;padding:10px;color:#fff">Exact Match: <a href="/unicode/fonts/gen-m.php?name=' . ($matched) . '" target=_blank style="color:#fff">' . $matched . ' (' . charToCodepoint($matched) . ')</a></p>';
	}
?>

<?
	$codepoints = [];
	preg_replace_callback('@U\+0?([0-9A-Fa-f]{4,5})@', function($m) use (&$codepoints) {
		$codepoint = 'U+' . $m[1];
		$codepoints[] = $codepoint;
	}, $rowData[1]);

	$similar = html_safe($rowData[18]);
	if (!empty($rowData[44])) {
		if ($rowData[16]) {
			$similar .= ' // Simplified Form of '.$rowData[44];
		} else {
			$similar .= ' // Traditional Form of '.$rowData[44];
		}
	}

	// Convert Codepoint + Char to Char only
	$replace = [];
	$similar = preg_replace_callback('@([\xE0-\xEF][\x80-\xbf][\x80-\xbf])|([\xF0-\xF7][\x80-\xbf][\x80-\xbf][\x80-\xbf])@', function($m) use (&$replace) {
		list($codepoint) = parseStringIntoCodepointArray($m[0]);
		$replace[$codepoint] = '';
		$replace['('.$codepoint.')'] = '';
		if (strlen($codepoint) === 6) {
			$replace['U+0' . substr($codepoint, 2)] = '';
			$replace['(U+0' . substr($codepoint, 2).')'] = '';
		}
		return $m[0];
	}, $similar);
	$similar = strtr($similar, $replace);

	// Convert Codepoint to Char only
	$similar = preg_replace_callback('@U\+0?([0-9A-Fa-f]{4,5})@', function($m) use (&$codepoints) {
		$codepoint = 'U+' . $m[1];
		$codepoints[] = $codepoint;
		return codepointToChar($codepoint);
	}, $similar);

	// Convert Char to link
	$similar = preg_replace_callback('@([\xE0-\xEF][\x80-\xbf][\x80-\xbf])|([\xF0-\xF7][\x80-\xbf][\x80-\xbf][\x80-\xbf])@', function($m) use (&$codepoints) {
		$m = parseStringIntoCodepointArray($m[0]);
		$m[1] = substr($m[0], 2);
		$codepoint = $m[0];
		$codepoints[] = $codepoint;
		return '<a href="../../../fonts/gen-m.php?name=u'.$m[1].'" target=_blank>'.codepointToChar($codepoint).' ('.$codepoint.')</a>';
	}, $similar);


	if (!empty($similar)) {
		echo '<div class=ws2015_similar_char>';
		echo 'Similar To: ';	
		echo $similar;
		echo '</div>';
	}
	if (!empty($codepoints)) {
		$codepoints = array_values(array_unique($codepoints));
		foreach ($codepoints as $codepoint) {
			echo '<div class=ws2015_similar_char>';
			if (!env::$readonly) echo '<a href="../../../fonts/gen-m.php?name=u'.strtolower(substr($codepoint, 2)).'" target=_blank style="margin-right:10px">';
			else echo '<span>';
			echo '<img src="https://glyphwiki.org/glyph/hkcs_m'.strtolower(substr($codepoint, 2)).'.svg" alt="'.$codepoint.'" height=72 width=72 style="vertical-align:top">';
			if (!env::$readonly) echo '</a>';
			else echo '</span>';
			echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
			echo '</div>';
		}
	}
?>
<div class=ws2015_evidence>
<?php if (str_startswith($rowData[10], 'GHZR')) { ?>
<?php if($rowData[35]) {?>
	<img src="<?=EVIDENCE_PATH?>/g-evidence/<?=html_safe($rowData[35])?>">
<?php } else { ?>
	<img src="<?=EVIDENCE_PATH?>/g-evidence/<?=html_safe(substr($rowData[11], 0, -4))?>.jpg">
	<!--img src="<?=('http://pic.guoxuedashi.com/hydzd/' . ltrim(substr($rowData[10], 5, -3), '0') . '.gif')?>"-->
<?php } ?>
<?php } ?>
<?php if (str_startswith($rowData[10], 'G_')) { ?>
	<? if (!empty($rowData[35])) { ?>
	<a href="<?=EVIDENCE_PATH?>/g-evidence/IRGN2115_Appendix7_1268_Zhuang_Evidences_page1268_image<?=substr($rowData[35], 23)?>.jpg" target=_blank><img src="<?=EVIDENCE_PATH?>/g-evidence/IRGN2115_Appendix7_1268_Zhuang_Evidences_page1268_image<?=substr($rowData[35], 23)?>.jpg"></a>
	<? } ?>
<?php } ?>
<?php if (!empty($rowData[4])) { ?>
	<div><a href="http://cns11643.gov.tw/AIDB/query_general_view.do?page=<?=substr($rowData[4],1,-5)?>&amp;code=<?=substr($rowData[4],-4)?>" target=_blank>Info on CNS11643.gov.tw</a></div>
<?php } ?>
<?php if (str_startswith($rowData[26], 'TCA_CJK_2015_Evidences.pdf page')) { ?>
	<a href="<?=EVIDENCE_PATH?>/t-evidence/IRGN2128A4Evidences-<?=html_safe(str_pad(trim(substr($rowData[26],31)), 3, '0', STR_PAD_LEFT))?>.png" target=_blank><img src="<?=EVIDENCE_PATH?>/t-evidence/IRGN2128A4Evidences-<?=html_safe(str_pad(trim(substr($rowData[26],31)), 3, '0', STR_PAD_LEFT))?>.png" width=800></a>
<?php } ?>
<?php if (!empty($rowData[29])) { ?>
	<a target=_blank href="<?=EVIDENCE_PATH?>/k-evidence/<?=substr($rowData[29], 0, -4)?>.JPG"><img src="<?=EVIDENCE_PATH?>/k-evidence/<?=substr($rowData[29], 0, -4)?>.min.jpg" width=800></a>
<?php } ?>
<?php if (!empty($rowData[37])) {

	$sat = file_get_contents('../data/sat-evidence/part1-mapping.txt');
	foreach (explode("\n", $sat) as $line) {
		if (str_startswith($line, $rowData[8])) {
			list($a, $page) = explode("\t", $line);
			$page = trim($page);
			echo '<a href="'.EVIDENCE_PATH.'/sat-evidence/IRGN2127_E_part1-'.str_pad($page + 1, 3, '0', STR_PAD_LEFT).'.png" target=_blank><img src="'.EVIDENCE_PATH.'/sat-evidence/IRGN2127_E_part1-'.str_pad($page + 1, 3, '0', STR_PAD_LEFT).'.png" width=800></a>';
		}
	}
	$sat = file_get_contents('../data/sat-evidence/part2-mapping.txt');
	foreach (explode("\n", $sat) as $line) {
		if (str_startswith($line, $rowData[8])) {
			list($a, $page) = explode("\t", $line);
			$page = trim($page);
			echo '<a href="'.EVIDENCE_PATH.'/sat-evidence/IRGN2127_E_part2-'.str_pad($page, 2, '0', STR_PAD_LEFT).'.png" target=_blank><img src="'.EVIDENCE_PATH.'/sat-evidence/IRGN2127_E_part2-'.str_pad($page, 2, '0', STR_PAD_LEFT).'.png" width=800></a>';
		}
	}
} ?>
<?php if (!empty($rowData[21])) {
	
	$utc = file_get_contents('../data/utc-evidence/page-mapping.txt');
	foreach (explode("\n", $utc) as $line) {
		if (str_startswith($line, $rowData[2])) {
			list($a, $page) = explode("\t", $line);
			$page = trim($page) + 0;
			if ($page > 67 && $page < 92) {
				$page = $page + 1;
			}
			
			if ($page >= 11 && $page <= 14) {
				echo '<img src="'.EVIDENCE_PATH.'/utc-evidence/IRGN2091_Evidence_edit-011.png" width=800>';
				echo '<img src="'.EVIDENCE_PATH.'/utc-evidence/IRGN2091_Evidence_edit-012.png" width=800>';
				echo '<img src="'.EVIDENCE_PATH.'/utc-evidence/IRGN2091_Evidence_edit-013.png" width=800>';
				echo '<img src="'.EVIDENCE_PATH.'/utc-evidence/IRGN2091_Evidence_edit-014.png" width=800>';
			} else {
				echo '<img src="'.EVIDENCE_PATH.'/utc-evidence/IRGN2091_Evidence_edit-'.str_pad($page, 3, '0', STR_PAD_LEFT).'.png" width=800 class=full>';
			}
		}
	}
	$utc = file_get_contents('../data/utc-evidence/page-mapping-additional.txt');
	foreach (explode("\n", $utc) as $line) {
		if (str_startswith($line, $rowData[0] .' (' . $rowData[2] . ')')) {
			list($a, $page) = explode(")", $line);
			$page = trim($page);
			echo '<img src="'.EVIDENCE_PATH.'/utc-evidence/Additional Evidences-'.str_pad($page, 2, '0', STR_PAD_LEFT).'.png" width=800>';
		}
	}
} ?>
<?php
if (str_startswith($rowData[40], 'Fig. ')) {
	foreach (explode(';',$rowData[40]) as $fig) {
		$page = trim(str_replace('Fig.', '', $fig));
?>
	<img src="<?=EVIDENCE_PATH?>/uk-evidence/IRGN2107_evidence-<?=html_safe(str_pad($page, 4, '0', STR_PAD_LEFT))?>.png" width=800 class=full style="max-height:400px;object-fit:cover;object-position:top">
<?php
	}
}
?>
</div>
</section>
<section class=ws2015_right>
<? if (!env::$readonly) { ?>
	<div style="display:grid;grid-template-columns:auto auto">
		<h2>Review</h2>
<?
if (!$char->hasReviewedUnification() || !$char->hasReviewedAttributes()) {
?>
		<div><a href="<?=html_safe($char->base_path . '&mark=3')?>" class=review_all>Review All</a></div>
<?
}
?>
	</div>
<?
/*
$review_path = ['IRGN2179_UTC-Review', 'IRGN2179_KR-Review', 'IRGN2155_UK_Review', 'IRGN2155_China_Review'];
foreach ($review_path as $path) {
	$review = json_decode(file_get_contents('..\/data/\' . $path . '.json'), true);
	if (isset($review[$sq_number])) {
		if (str_startswith($path, 'IRGN2179')) {
			$name = 'WS2015v3 - ' . strtr($path, ['_' => ' ']);
		} else if (str_startswith($path, 'IRGN2155')) {
			$name = '<span style="color:red"><u>WS2015v2 - ' . strtr($path, ['_' => ' ']) . '</u></span>';
		} else {
			$name = strtr($path, ['_' => ' ', '-' => ' ']).' Review';
		}
		echo '<b>'.$name.'</b><br>' . nl2br(html_safe($review[$sq_number]));
		echo '<br>';
		echo '<br>';
	}
}
*/
?>
	<div>
		<b>Evidence &amp; Unification</b>:<br>
<?php
	if ($char->hasReviewedUnification()) {
		echo '<div>Reviewed.</div>';
	} else {
?>
		<a href="<?=html_safe($char->base_path . '&mark=1')?>" class=review>Review</a>
<?
	}
?>
	</div>
	<hr>
	<div>
		<b>Attributes</b>: <br>
<?php

$ids = parseStringIntoCodepointArray($rowData[17]);
if ($rowData[16]) {
	$rad = getIdeographForSimpRadical(intval($rowData[13]));
} else {
	$rad = getIdeographForRadical($rowData[13]);
}

if (count($ids) === 3) {
	if (in_array(codepointToChar($ids[1]), $rad)) {
		list($totalstrokes, $fs) = getTotalStrokes($ids[2]);
		if ($totalstrokes) {
			if (!$fs) {
				echo '<div style="color:red">First Stroke not found. Suggested: '.$rowData[15].'</div>';
			}
			echo codepointToChar($ids[2]) . ' ('.$ids[2] . '): SC - ' . $totalstrokes . ' FS - ' . $fs;
			if ($totalstrokes != $rowData[14]) {
				echo '<div style="color:red">Stroke Count doesn\'t match.</div>';
			}
			if ($fs != $rowData[15]) {
				echo '<div style="color:red">First Stroke doesn\'t match.</div>';
			}
			if ($totalstrokes == $rowData[14] && $fs == $rowData[15]) {
				echo '<div style="color:green">Matched.</div>';
			}
		} else {
			$ids_row = \IDS\getIDSforCodepoint($ids[2]);
			if ($ids_row) {
				foreach ($ids_row->ids_list as $ids_list) {
					$fs2 = 0;
					$ts2 = 0;
					$poisoned = false;
					foreach ($ids_list->getCJKComponents() as $component) {
						list($ts1, $fs1) = getTotalStrokes(charToCodepoint($component));
						if ($ts1) {
							echo $component . ' SC - ' . $ts1 . ' FS - ' . $fs1;
							if (!$fs2 && $component !== codepointToChar('U+8FB6')) {
								$fs2 = $fs1;
							}
							$ts2 += $ts1;
						} else {
							echo $component . ' SC - N/A FS - N/A - (' . charToCodepoint($component) . ')';
							$poisoned = true;
						}
						echo '<br>';
					}
					if (!$poisoned) {
						echo '= SC ' . $ts2 . ' FS ' . $fs2;
					}
					echo '<br>';
				}
			}
			echo '<br>';
			echo codepointToChar($ids[2]) . " SC " . $rowData[14] . ' FS ' . $rowData[15];
			
			if (!$poisoned && $ts2 == $rowData[14] && $fs2 == $rowData[15]) {
				echo ' <span style="color:green">OK</span>';
			} else {
				echo ' <div style="color:red">Stroke Count Not Found</div>';
				echo ' - <a href="'.$char->base_path.'&id='.$sq_number.'&amp;add_strokes='.urlencode($ids[2] . " " . $rowData[14] . '|' . $rowData[15]).'">Confirm</a>';
			}
			echo '<br>';
			echo '<br>';
		}
		echo '<br>';
		echo '<br>';
	}
	if (in_array(codepointToChar($ids[2]), $rad)) {
		list($totalstrokes, $fs) = getTotalStrokes($ids[1]);
		if ($totalstrokes) {
			if (!$fs) {
				echo '<div style="color:red">First Stroke not found. Suggested: '.$rowData[15].'</div>';
			}
			echo codepointToChar($ids[1]) . ' ('.$ids[1] . '): SC - ' . $totalstrokes . ' FS - ' . $fs;
			if ($totalstrokes != $rowData[14]) {
				echo '<div style="color:red">Stroke Count doesn\'t match.</div>';
			}
			if ($fs != $rowData[15]) {
				echo '<div style="color:red">First Stroke doesn\'t match.</div>';
			}
			if ($totalstrokes == $rowData[14] && $fs == $rowData[15]) {
				echo '<div style="color:green">Matched.</div>';
			}
		} else {
			echo ' <div style="color:red">Stroke Count Not Found</div>';
			$ids_row = \IDS\getIDSforCodepoint($ids[1]);
			if ($ids_row) {
				foreach ($ids_row->ids_list as $ids_list) {
					$fs2 = 0;
					$ts2 = 0;
					$poisoned = false;
					foreach ($ids_list->getCJKComponents() as $component) {
						list($ts1, $fs1) = getTotalStrokes(charToCodepoint($component));
						if ($ts1) {
							echo $component . ' SC - ' . $ts1 . ' FS - ' . $fs1;
							if (!$fs2 && $component !== codepointToChar('U+8FB6')) {
								$fs2 = $fs1;
							}
							$ts2 += $ts1;
						} else {
							echo $component . ' SC - N/A FS - N/A - (' . charToCodepoint($component) . ')';
							$poisoned = true;
						}
						echo '<br>';
						}
					if (!$poisoned) {
						echo '= SC ' . $ts2 . ' FS ' . $fs2;
					}
					echo '<br>';
				}
			}
			echo '<br>';
			echo codepointToChar($ids[1]) . " SC " . $rowData[14] . ' FS ' . $rowData[15];
			echo ' - <a href="'.$char->base_path.'&id='.$sq_number.'&amp;add_strokes='.urlencode($ids[1] . " " . $rowData[14] . '|' . $rowData[15]).'">Confirm</a>';
			echo '<br>';
			echo '<br>';
		}
		echo '<br>';
		echo '<br>';
	}
}

$totalstrokes = 0;
foreach ($ids as $ids_char) {
	if (empty(trim($ids_char))) {
		continue;
	}
	if (codepointToChar($ids_char) >= codepointToChar('U+2FF0') && codepointToChar($ids_char) <= codepointToChar('U+2FFF')) {
		continue;
	}
	list($totalstrokes1, $fs) = getTotalStrokes($ids_char);
	$totalstrokes += $totalstrokes1;
	if ($totalstrokes1) {
		echo codepointToChar($ids_char) . ' ('.$ids_char . '): SC - ' . $totalstrokes1;
		echo '<br>';
	} else {
		echo '<br>';
		$ids_row = \IDS\getIDSforCodepoint($ids_char);
		if ($ids_row) {
			foreach ($ids_row->ids_list as $ids_list) {
				$fs2 = 0;
				$ts2 = 0;
				$poisoned = false;
				foreach ($ids_list->getCJKComponents() as $component) {
					list($ts1, $fs1) = getTotalStrokes(charToCodepoint($component));
					if ($ts1) {
						echo $component . ' SC - ' . $ts1 . ' FS - ' . $fs1;
						if (!$fs2 && $component !== codepointToChar('U+8FB6')) {
							$fs2 = $fs1;
						}
						$ts2 += $ts1;
					} else {
						echo $component . ' SC - N/A FS - N/A - (' . charToCodepoint($component) . ')';
						$poisoned = true;
					}
					echo '<br>';
				}
				if (!$poisoned) {
					echo '= SC ' . $ts2 . ' FS ' . $fs2;
				}
				echo '<br>';
			}
			echo codepointToChar($ids_char) . ' ('.$ids_char . '): <div style="color:red">Stroke Count Not Found</div>';
			echo 'SC ' . $ts2 . ' FS ' . $fs2 . ' - <a href="'.$char->base_path.'&id='.$sq_number.'&amp;add_strokes='.urlencode($ids_char . " " . $ts2 . '|' . $fs2).'">Confirm</a>';
			echo '<br>';
			echo '<br>';
		} else {
			echo codepointToChar($ids_char) . ' ('.$ids_char . '): <div style="color:red">Stroke Count Not Found</div>';
			echo '<a href="'.$char->base_path.'&id='.$sq_number.'&amp;add_strokes='.urlencode($ids_char . " " . '0|0').'">Confirm</a>';
			echo '<br>';
			echo '<br>';
		}
	}
}

if ($totalstrokes != $char->getTotalStrokes()) {
	echo " TC " . $totalstrokes;
	echo '<div style="color:red">Total Stroke doesn\'t match.</div>';
} else {
	echo '<div style="color:green">Total Stroke Matched.</div>';
}



?>
<?php
	if ($char->hasReviewedAttributes()) {
		echo '<div>Reviewed.</div>';
	} else {
?>
		<a href="<?=html_safe($char->base_path . '&mark=2')?>" class=review>Review</a>
<?
	}
?>
	</div>

	<hr>
<? } ?>
	<div>
<?php
Log::add('Comments Start');
$has_cm = false;
foreach ([/*'GHZR', 'K', 'SAT', 'TW', 'UK', 'UTC',*/ 'IRGN2179 Henry Review', 'IRGN2179 Henry Review - Part 2'] as $cm_n) {
	$cm = file('../' . $cm_n . '.txt');
	foreach ($cm as $c) {
		if (str_startswith($c, $rowData[0])) {
			$has_cm = true;
			echo '<h2>Comments</h2>';
			echo '<div><b>'.$cm_n.'.txt</b></div>';
			echo trim($c);
		}
		if (str_startswith($c, $rowData[10]) || str_startswith($c, $rowData[4]) || str_startswith($c, $rowData[6]) || str_startswith($c, $rowData[8]) || str_startswith($c, $rowData[2])) {
			$has_cm = true;
			echo '<h2>Comments</h2>';
			echo '<div><b>'.$cm_n.'.txt</b></div>';
			echo trim($c);
		}
	}
}
Log::add('Comments End');

?>
	</div>
<? if (!env::$readonly) { ?>
	<p><? $instance = new DBProcessedInstance(); $total = $instance->getTotal(); echo $total .' out of 5547 processed, ' . (5547-$total) . ' remaining.'; ?></p>
<? } ?>
</section>
</div>
<hr>
<section class=ws2015_comments>
	<h2>Comments</h2>
<?
		echo '<table>';
		echo '<col width=200>';
		echo '<col width=auto>';
		echo '<thead><tr><th>Type</th><th>Description</th></tr></thead>';
		foreach (DBComments::getAll($char->data[0]) as $cm) {
			echo '<tr>';
			echo '<td><b>'.htmlspecialchars($cm->type).'</b></td>';
			echo '<td>';

			if ($cm->type === 'SEMANTIC_VARIANT') {
				$arr = parseStringIntoCodepointArray($cm->comment);
				if (count($arr) === 1) {
					try {
						$arr2 = codepointToChar($arr[0]) . ' ('.$arr[0].')';
						$cm->comment = $arr2;
					} catch (Exception $e) {}
				}
			}

			if ($cm->type !== 'KEYWORD') {
				if ($cm->type === 'UNIFICATION' || $cm->type === 'UNIFICATION_LOOSE' || $cm->type === 'CODEPOINT_CHANGED') {
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
					if ($cm->type === 'CODEPOINT_CHANGED' && preg_match('@^U\\+[0-9A-F]{4,5}$@', $cm->comment)) {
						$matches = [null, [codepointToChar($cm->comment)]];
					}
					foreach ($matches[1] as $match) {
						$codepoint = charToCodepoint($match);
						if ($codepoint[2] === 'F' || ($codepoint[2] === '2' && $codepoint[3] === 'F')) {
							echo '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
						} else {
							echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%"><br>';
						}
					}
				}

				$text = nl2br(htmlspecialchars($cm->comment));
				$text = preg_replace('@{?{(([0-9]){5}-([0-9]){3}\\.png)}}?@', '<img src="../comments/\\1" style="max-width:100%">', $text);
				$text = preg_replace_callback('@{{(U\\+[A-F0-9a-f]{4,5})}}@', function ($m) {
					$codepoint = $m[1];
					if (!env::$readonly) {
						if ($codepoint[2] === 'F' || ($codepoint[2] === '2' && $codepoint[3] === 'F')) {
							return '<img src="../../../Code Charts/UCSv9/Compat/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'" style="max-width:100%">';
						}
						return '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
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

			} else {
				$keyword = ($cm->comment);
				$text = '<span style="font-size:32px"><a href="?keyword='.urlencode($keyword).'" target=_blank>'.htmlspecialchars($keyword).'</a></span>';
			}
			echo $text;
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
if (!env::$readonly) { ?>
	<hr>
	<form method=post class=comment_block id=comment_block_<?=$sq_number;?>>
		<div><input type=hidden name=sq_number value="<?=$sq_number;?>"></div>
		<div>
			<?=$sq_number;?>
			<?=implode('|',array_filter(array_map("trim",[$rowData[10], $rowData[4], $rowData[6], $rowData[8], $rowData[2]]), function($e) { return $e !== ''; }));?>
		</div>
		<div>
			<select name=type class=comment_type>
<?
		foreach (DBComments::COMMENT_TYPES as $type) {
			echo '<option value="' . $type . '">' . $type . '</option>'."\r\n";
		}
?>
			</select>
		</div>
		<div>
			<textarea name=comment class=comment_content></textarea>
		</div>
		<div class=comment_keywords>
<?
		foreach (DBComments::getAllKeywords() as $keyword) {
			echo '<span>' . $keyword . '</span>';
		}
?>
		</div>
		<div>
			<input type=submit value=Add class=comment_submit>
		</div>
	</form>
	<script>
	(function() {
		var parent = $('#comment_block_<?=$sq_number;?>');
		var toggleCommentKeywords = function() {
			var val = parent.find('.comment_type').val();
			if (val === 'KEYWORD') {
				parent.find('.comment_keywords').show();
			} else {
				parent.find('.comment_keywords').hide();
			}
		}
		parent.find('.comment_type').on('change', toggleCommentKeywords);
		toggleCommentKeywords();
		$(toggleCommentKeywords);
		parent.find('.comment_keywords').css({
			'display': 'grid',
			gridTemplateColumns: 'repeat(auto-fill, minmax(64px, 1fr))',
			gridGap: '10px',
			'margin': '10px 0'
		});
		parent.find('.comment_keywords span').on('click', function() {
			parent.find('.comment_content').val($(this).text());
		}).css({
			'border': '1px solid #999',
			'padding': '8px 4px',
			textAlign: 'center',
			cursor: 'pointer'
		});
	})();
	</script>
<?
}
?>
</section>

<hr>
	<details>
		<summary>Raw Info</summary>
<?php
		echo '<table>';
		foreach ($rowData as $cell => $value) {
			echo '<tr><td>' . $cell . ' - ' . htmlspecialchars($firstRow[$cell]) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
		}
		echo '</table>';
?>
	</details>
</div>
<?
}
?>

<script>
(function() {
	$(window).on('popstate', function() { window.location.reload(); })
	function attach() {
		var n = $('#nav_next').each(function() {		
			var h = this.href;
			console.log(h);
			$.get(h, function(html) {
				$(html).find('img').each(function(){
					var i = new Image;
					i.src = $(this).attr('src');
				});
				$(this).on('click', function(e) {
					if (e.ctrlKey) return;
					e.preventDefault();
					history.pushState({}, null, h);
					$('body').html(html);
					var title = $('body').find('title');
					document.title = title.text();
					title.remove();
				});
			});
		});
	}
	attach();
})();
</script>
<div class=footer>
	<p>Source Code released at <a href="https://github.com/hfhchan/irg-ws2015">https://github.com/hfhchan/irg-ws2015</a>.</p>
</div>