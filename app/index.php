<?php

if (isset($_GET['null'])) {
	header('HTTP/1.1 204 No Content');
	return '';
}

require_once 'z.log.php';
require_once 'vendor/autoload.php';

if (isset($_GET['right'])) {
	$next = ltrim($_GET['right'], '0');
	$file = file_get_contents('../data/processed.txt');
	while (strpos($file, str_pad(intval(ltrim($next, '0')), 5, '0', STR_PAD_LEFT)) !== false) {
		$next++;
	}
	header('Location: ?id=' . str_pad(intval(ltrim($next, '0')), 5, '0', STR_PAD_LEFT));
	exit;
}

if (isset($_GET['find'])) {
	$_GET['find'] = trim(strtr($_GET['find'], [' ' => '']));
	if (!empty($_GET['find'])) {
		$objReader = PHPExcel_IOFactory::createReader('Excel5');
		$objReader->setReadDataOnly(true);
		$excel = $objReader->load("../data/IRGN2179CJK_Working_Set2015v3.0_Attributes.xls");

		$found = false;
		for ($s = 0; $s < 3; $s++) {
			$sheet = $excel->getSheet($s);
			$highestRow = $sheet->getHighestRow(); 
			$highestColumn = $sheet->getHighestColumn();

			$firstRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, false, false)[0];

			for ($row = 2; $row <= $highestRow; $row++){ 
				$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, false, false)[0];
				if (trim($rowData[2]) == $_GET['find']) {
					$found = true;
					break 2;
				}
				if (trim($rowData[4]) == $_GET['find']) {
					$found = true;
					break 2;
				}
				if (trim($rowData[6]) == $_GET['find']) {
					$found = true;
					break 2;
				}
				if (trim($rowData[8]) == $_GET['find']) {
					$found = true;
					break 2;
				}
				if (trim($rowData[10]) == $_GET['find']) {
					$found = true;
					break 2;
				}
			}
		}
		if (!$found) {
			die('Not Found');
		}
		$_GET['id'] = $rowData[0];
	}
}

if (isset($_GET['mark']) && isset($_GET['id'])) {
	if ($_GET['mark'] == 3) {
		$fp = fopen('../data/processed.txt', 'a');
		fwrite($fp, $_GET['id'] . "\r\n");
		fclose($fp);

		$fp = fopen('../data/processed-ids.txt', 'a');
		fwrite($fp, $_GET['id'] . "\r\n");
		fclose($fp);

		$next = ltrim($_GET['id'], '0');
		$file = file_get_contents('../data/processed.txt');
		while (strpos($file, str_pad(intval(ltrim($next, '0')), 5, '0', STR_PAD_LEFT)) !== false) {
			$next++;
		}
		header('Location: ?id=' . str_pad(intval(ltrim($next, '0')), 5, '0', STR_PAD_LEFT));
		exit;
	}
	if ($_GET['mark'] == 1) {
		$fp = fopen('../data/processed.txt', 'a');
		fwrite($fp, $_GET['id'] . "\r\n");
		fclose($fp);
	}
	if ($_GET['mark'] == 2) {
		$fp = fopen('../data/processed-ids.txt', 'a');
		fwrite($fp, $_GET['id'] . "\r\n");
		fclose($fp);
	}
	header('Location: ?id=' . $_GET['id']);
	exit;
}
if (isset($_GET['add_strokes'])) {
	if (preg_match('@^U\+[0-9A-F]?[0-9A-F][0-9A-F][0-9A-F][0-9A-F] [0-9]+\|[0-9]+$@', $_GET['add_strokes'])) {
		$fp = fopen('../totalstrokes.txt', 'a');
		fwrite($fp, $_GET['add_strokes'] . "\r\n");
		fclose($fp);
	} else {
		die('Format Mismatch');
	}
	header('Location: ?id=' . $_GET['id']);
	exit;
}

if (!isset($_GET['id'])) {
	$sq_number = '00143';
} else {
	$sq_number = trim($_GET['id']);
}


if (!file_exists('../data/attributes-cache/' . $sq_number . '.json')) {
	Log::add('Load File Start');
	$objReader = PHPExcel_IOFactory::createReader('Excel5');
	$objReader->setReadDataOnly(true);
	$excel = $objReader->load("../data/IRGN2179CJK_Working_Set2015v3.0_Attributes.xls");

	Log::add('Load File End');

	$sheet = $excel->getSheet(1); 
	$highestRow = $sheet->getHighestRow(); 
	$highestColumn = $sheet->getHighestColumn();

	$firstRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, false, false)[0];

	Log::add('Loop Data Start');
	$found = false;
	for ($row = 2; $row <= $highestRow; $row++){ 
		$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, false, false)[0];
		if ($rowData[0] == $sq_number) {
			$found = true;
			break;
		}
	}
	Log::add('Loop Data End');
	if ($found) {
		$data = new StdClass();
		$data->sheet = 1;
		$data->data = $rowData;
		file_put_contents('../data/attributes-cache/' . $sq_number . '.json', json_encode($data));
	} else {
		$sheet = $excel->getSheet(2); 
		$highestRow = $sheet->getHighestRow(); 
		$highestColumn = $sheet->getHighestColumn();

		$firstRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, false, false)[0];

		Log::add('Loop Data Start');
		$found = false;
		for ($row = 2; $row <= $highestRow; $row++){ 
			$rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, false, false)[0];
			if ($rowData[0] == $sq_number) {
				$found = true;
				break;
			}
		}
		if ($found) {
			$data = new StdClass();
			$data->sheet = 2;
			$data->data = $rowData;
		}
	}
} else {
	$firstRow = json_decode(file_get_contents('../data/attributes-cache/sheet.0.firstRow.json'));
	$data = json_decode(file_get_contents('../data/attributes-cache/' . $sq_number . '.json'));
	$rowData = $data->data;
	$found = true;
}

if ($rowData[18] === 'No') {
	$rowData[18] = null;
}

// Remove PUA
$rowData[17] = strtr($rowData[17], [
	codepointToChar('U+E832') => codepointToChar('U+9FB8')
]);

$sheetnames = [
	'IRGN2179IRG_Working_Set2015v3.0',
	'IRGN2179Unified&WithdrawnV3.0',
	'IRGN2179PostponedV3.0'
];

?>
<!doctype html>
<meta charset=utf-8>
<title>WS2015v3.0 <?=$sq_number?></title>
<style>
body{font-family:Arial, "Microsoft Jhenghei",sans-serif;background:#eee;margin:0}
h2{margin:16px 0}
hr{border:none;border-top:1px solid #999}
form{margin:0}
#ws2015_table{border:1px solid #333;border-collapse:collapse;table-layout:fixed}
#ws2015_table td{text-align:center;padding:5px 10px;font-size:16px;border:1px solid #333}
#ws2015_table td.discussion{text-align:left}
#evidence h2{padding:0 10px}
#evidence img{display:block;width:800px;height:400px;object-fit:contain;border:1px solid #ccc;margin:10px auto}
#evidence img.full{height:auto}
.ids_component{margin:0 2px}
.sheet-1{background:#999;opacity:.6}
.sheet-2{background:#ff0}

#findbar{padding:10px;background:#fff;border-bottom:1px solid #ccc}
#findbar form{display:flex}
#findbar form>div{margin-right:5px;flex-shrink:none;flex-grow:none}
</style>
<body>
<div style="" id=findbar>
	<div style="width:1160px;margin:0 auto">
		<form method=get autocomplete=no style="display:flex">
			<div style="width:100px">Find Char:</div>
			<div><input type=text name=find value="<?=html_safe(isset($_GET['find']) ? $_GET['find'] : '')?>" accesskey=f></div>
			<div><input type=submit value=Find></div>
		</form>
		<form method=get autocomplete=no style="display:flex">
			<div style="width:100px">Char ID:</div>
			<div><input name=id value="<?=html_safe(isset($_GET['id']) ? $_GET['id'] : '')?>" accesskey=i></div>
			<div><input type=submit value=Find></div>
		</form>
	</div>
</div>

<div style="width:1160px;padding:20px;margin:10px auto;background:#fff;border:1px solid #ccc">
<table width="100%" style="font-size:24px">
	<tr>
		<td align=left><a href="?id=<?=str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT)?>"><?=str_pad(intval(ltrim($sq_number, '0')) - 1, 5, '0', STR_PAD_LEFT)?></a></td>
		<td style="text-align:center;font-weight:bold"><?=str_pad(intval(ltrim($sq_number, '0')), 5, '0', STR_PAD_LEFT)?></td>
		<td align=right><a href="?id=<?=str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT)?>" id=nav_next><?=str_pad(intval(ltrim($sq_number, '0')) + 1, 5, '0', STR_PAD_LEFT)?></a> || <a href="?right=<?=$sq_number?>" id=nav_next>&raquo;</a></td>
	</tr>
</table>
<? if ($found) { ?>
<hr>
<!--h2>Character Info</h2-->
<table id=ws2015_table class="sheet-<?=$data->sheet?>">
	<col width="6%">
	<col width="4%">
	<col width="8%">
	<col width="10%">
	<col width="10%">
	<col width="10%">
	<col width="10%">
	<col width="10%">
	<col width="32%">
	<!--tr>
		<td>CJK_2015<br/>Seq. No.</td>
		<td colspan=2>Kxi Rad.SC<br/>IDS<br/>FS</td>
		<td>G</td>
		<td>T</td>
		<td>K</td>
		<td>SAT</td>
		<td>UTC</td>
		<td>Discussion Record</td>
	</tr-->
	<tr>
		<td rowspan="3" style="height:46pt"><?=$rowData[0]?></td>
		<td colspan="2">
<?

if (strpos($rowData[13], '.1') !== false) {
	$rad = substr($rowData[13], 0, -2);
	$simpRad = 1;
} else {
	$rad = $rowData[13];
	$simpRad = 0;
}

?>
			<?=getIdeographForRadical($rad)[0]?><? if ($simpRad) echo '\'';?>
			<?=$rad?><? if ($simpRad) echo '\'';?>.<?=$rowData[14]?>
		</td>
		<td rowspan="3"><?php if (isset($rowData[10]) || isset($rowData[11])) {?><img src="../data/g-bitmap/<?=substr($rowData[11], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[10]?><?php } ?></td>
		<td rowspan="3"><?php if (isset($rowData[4]) || isset($rowData[5])) {?><img src="../data/t-bitmap/<?=substr($rowData[5], 0, -4)?>.bmp" width="32" height="32"><br><?=$rowData[4]?><?php } ?></td>
		<td rowspan="3"><?php if (isset($rowData[6]) || isset($rowData[7])) {?><img src="../data/k-bitmap/<?=substr($rowData[7], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[6]?><?php } ?></td>
		<td rowspan="3">
			<?php if (isset($rowData[8]) || isset($rowData[9])) {?><img src="http://en.glyphwiki.org/glyph/sat_g9<?=substr($rowData[9], 4, -4)?>.svg" width="32" height="32"><br><?=$rowData[8]?><?php } ?>
		</td>
		<td rowspan="3">
			<?php if (isset($rowData[2]) || isset($rowData[3])) {?>
				<?php if (empty($rowData[39])) {?>
					<img src="../data/utc-bitmap/<?=substr($rowData[3], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[2]?>
				<?php } else { ?>
					<img src="../data/uk-bitmap/<?=substr($rowData[3], 0, -4)?>.png" width="32" height="32"><br><?=$rowData[2]?> (UK)
				<?php } ?>
			<?php } ?>
		</td>
		<td rowspan="3" class=discussion>
			<? if ($data->sheet) echo '<b>'.$sheetnames[$data->sheet] . '</b><br>'; ?>
			<?=$rowData[1]?>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="font-family:SimSun"><div style="width:136px"><?
		$ids = parseStringIntoCodepointArray($rowData[17]);
		foreach ($ids as $component) {
			if (!empty(trim($component))) {
				echo '<a href="../../../fonts/gen-m.php?name=u'.substr($component, 2).'" target=_blank class=ids_component>'.codepointToChar($component).'</a>';
			}
		}
		if (empty($rowData[17])) {
			echo '<span style="color:#999;font-family:sans-serif">(Empty)</span>';
		}
?></div></td>
	</tr>
	<tr>
		<td><?
if ($rowData[15] == '1') echo '橫';
if ($rowData[15] == '2') echo '豎';
if ($rowData[15] == '3') echo '撇';
if ($rowData[15] == '4') echo '點';
if ($rowData[15] == '5') echo '折';
?></td>
		<td>N/A</td>
	</tr>
</table>
<div style="display:flex;margin-top:10px">
<section style="width:820px;border:1px solid #ccc;flex-shrink:0">
<?

	$ids = parseStringIntoCodepointArray(str_replace(' ', '', $rowData[17]));
	$ids = array_values(array_map(function($d) {
		return codepointToChar($d);
	}, $ids));
	if (!empty($rowData[17])) {
		$matched = \IDS\getCharByIDS($ids);
	} else {
		$matched = false;
	}
	if ($matched && substr($matched, 0, 1) !== '&') {
		echo '<p style="background:red;font-size:24px;margin:10px;padding:10px;color:#fff">Exact Match: <a href="/unicode/fonts/gen-m.php?name=' . ($matched) . '" target=_blank style="color:#fff">' . $matched . ' (' . charToCodepoint($matched) . ')</a></p>';
	}

	$codepoints = [];
	preg_replace_callback('@U\+0?([0-9A-Fa-f]{4,5})@', function($m) use (&$codepoints) {
		$codepoint = 'U+' . $m[1];
		$codepoints[] = $codepoint;
	}, $rowData[1]);

	$similar = html_safe($rowData[18]);
	if (!empty($rowData[44])) {
		$similar .= ' // Simplified Form of '.$rowData[44];
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
		echo '<div style="margin:10px">';
		echo 'Similar To: ';	
		echo $similar;
		echo '</div>';
	}
	if (!empty($codepoints)) {
		echo '<div style="overflow:hidden">';
		$codepoints = array_values(array_unique($codepoints));
		foreach ($codepoints as $codepoint) {
			echo '<div style="border:1px solid #ccc;width:800px;padding:10px">';
			echo '<a href="../../../fonts/gen-m.php?name=u'.strtolower(substr($codepoint, 2)).'" target=_blank style="margin-right:10px">';
			echo '<img src="http://en.glyphwiki.org/glyph/hkcs_m'.strtolower(substr($codepoint, 2)).'.svg" alt="'.$codepoint.'" height=72 width=72 style="vertical-align:top">';
			echo '</a>';
			//if (isExtensionF($codepoint)) {
			//	echo renderExtFImage(getExtFImage($codepoint));
			//} else {
				echo '<img src="../../../Code Charts/UCSv9/Excerpt/'.substr($codepoint, 2, -2).'/'.$codepoint.'.png" alt="'.$codepoint.'">';
			//}
			echo '</div>';
		}
		echo '</div>';
	}
?>

<div id=evidence>
<!--h2>Evidence</h2-->
<?php if (str_startswith($rowData[10], 'GHZR')) { ?>
	<!--img rel=noreferrer src="https://4.hidemyass.com/includes/process.php?action=update&sslSwitch=1&ssl=1&server=0&ipidx=0&obfuscation=0&u=<?=urlencode('http://pic.guoxuedashi.com/hydzd/' . ltrim(substr($rowData[10], 5, -3), '0') . '.gif')?>"-->
<?php if($rowData[35]) {?>
	<img src="../data/g-evidence/<?=html_safe($rowData[35])?>">
<?php } else { ?>
	<img src="../data/g-evidence/<?=html_safe(substr($rowData[11], 0, -4))?>.jpg">
<?php } ?>
<?php } ?>
<?php if (str_startswith($rowData[10], 'G_Z') || str_startswith($rowData[10], 'G_P')) { ?>
	<img src="../data/g-evidence/IRGN2115_Appendix7_1268_Zhuang_Evidences_page1268_image<?=substr($rowData[35], 23)?>.jpg">
<?php } ?>
<?php if (!empty($rowData[4])) { ?>
	<div><a href="http://cns11643.gov.tw/AIDB/query_general_view.do?page=<?=substr($rowData[4],1,-5)?>&amp;code=<?=substr($rowData[4],-4)?>" target=_blank>Info on CNS11643.gov.tw</a></div>
<?php } ?>
<?php if (str_startswith($rowData[26], 'TCA_CJK_2015_Evidences.pdf page')) { ?>
	<img src="../data/t-evidence/IRGN2128A4Evidences-<?=html_safe(str_pad(trim(substr($rowData[26],31)), 3, '0', STR_PAD_LEFT))?>.png" width=800>
	<!--img src="<?=('http://pic.guoxuedashi.com/hydzd/' . ltrim(substr($rowData[10], 5, -3), '0') . '.gif')?>"-->
<?php } ?>
<?php if (!empty($rowData[29])) { ?>
	<a target=_blank href="../data/k-evidence/<?=substr($rowData[29], 0, -4)?>.jpg"><img src="../data/k-evidence/<?=substr($rowData[29], 0, -4)?>.min.jpg" width=800></a>
<?php } ?>
<?php if (!empty($rowData[37])) {
	
	$sat = file_get_contents('..\data\sat-evidence\part1-mapping.txt');
	foreach (explode("\n", $sat) as $line) {
		if (str_startswith($line, $rowData[8])) {
			list($a, $page) = explode("\t", $line);
			$page = trim($page);
			echo '<a href="../data/sat-evidence/IRGN2127_E_part1-'.str_pad($page + 1, 3, '0', STR_PAD_LEFT).'.png" target=_blank><img src="../data/sat-evidence/IRGN2127_E_part1-'.str_pad($page + 1, 3, '0', STR_PAD_LEFT).'.png" width=800></a>';
		}
	}
	$sat = file_get_contents('..\data\sat-evidence\part2-mapping.txt');
	foreach (explode("\n", $sat) as $line) {
		if (str_startswith($line, $rowData[8])) {
			list($a, $page) = explode("\t", $line);
			$page = trim($page);
			echo '<a href="../data/sat-evidence/IRGN2127_E_part2-'.str_pad($page, 2, '0', STR_PAD_LEFT).'.png" target=_blank><img src="../data/sat-evidence/IRGN2127_E_part2-'.str_pad($page, 2, '0', STR_PAD_LEFT).'.png" width=800></a>';
		}
	}
} ?>
<?php if (!empty($rowData[21])) {
	
	$utc = file_get_contents('..\data\utc-evidence\page-mapping.txt');
	foreach (explode("\n", $utc) as $line) {
		if (str_startswith($line, $rowData[2])) {
			list($a, $page) = explode("\t", $line);
			$page = trim($page) + 0;
			if ($page > 67 && $page < 92) {
				$page = $page + 1;
			}
			
			if ($page >= 11 && $page <= 14) {
				echo '<img src="../data/utc-evidence/IRGN2091_Evidence_edit-011.png" width=800>';
				echo '<img src="../data/utc-evidence/IRGN2091_Evidence_edit-012.png" width=800>';
				echo '<img src="../data/utc-evidence/IRGN2091_Evidence_edit-013.png" width=800>';
				echo '<img src="../data/utc-evidence/IRGN2091_Evidence_edit-014.png" width=800>';
			} else {
				echo '<img src="../data/utc-evidence/IRGN2091_Evidence_edit-'.str_pad($page, 3, '0', STR_PAD_LEFT).'.png" width=800 class=full>';
			}
		}
	}
	$utc = file_get_contents('..\data\utc-evidence\page-mapping-additional.txt');
	foreach (explode("\n", $utc) as $line) {
		if (str_startswith($line, $rowData[0] .' (' . $rowData[2] . ')')) {
			list($a, $page) = explode(")", $line);
			$page = trim($page);
			echo '<img src="../data/utc-evidence/Additional Evidences-'.str_pad($page, 2, '0', STR_PAD_LEFT).'.png" width=800>';
		}
	}
} ?>
<?php
if (str_startswith($rowData[40], 'Fig. ')) {
	foreach (explode(';',$rowData[40]) as $fig) {
		$page = trim(str_replace('Fig.', '', $fig));
?>
	<img src="../data/uk-evidence/IRGN2107_evidence-<?=html_safe(str_pad($page, 4, '0', STR_PAD_LEFT))?>.png" width=800 class=full>
<?php
	}
}
?>
</div>
</section>
<section id=review style="margin-left:10px;border:1px solid #ccc;padding:10px;flex-grow:1;flex-shrink:1">
	<h2>Review</h2>
	<a href="?mark=3&amp;id=<?=urlencode($rowData[0])?>">Review All</a>
	<hr>
<?
$review_path = ['IRGN2179_UTC-Review', 'IRGN2179_KR-Review', 'IRGN2155_UK_Review', 'IRGN2155_China_Review'];
foreach ($review_path as $path) {
	$review = json_decode(file_get_contents('..\\data\\' . $path . '.json'), true);
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
?>
	<div>
		<b>Evidence &amp; Uniqueness</b>:<br>
<?php
$file = file_get_contents('../data/processed.txt');
if (strpos($file, $rowData[0]) !== false) echo '<div>Reviewed.</div>';
else { echo '<a href="?mark=1&amp;id='.urlencode($rowData[0]).'">Review</a>'; }
?>
	</div>
	<br>
	<div>
		<b>Rad.SC &amp; IDS</b>: <br>
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
				echo ' - <a href="?id='.$sq_number.'&amp;add_strokes='.urlencode($ids[2] . " " . $rowData[14] . '|' . $rowData[15]).'">Confirm</a>';
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
			echo ' - <a href="?id='.$sq_number.'&amp;add_strokes='.urlencode($ids[1] . " " . $rowData[14] . '|' . $rowData[15]).'">Confirm</a>';
			echo '<br>';
			echo '<br>';
		}
		echo '<br>';
		echo '<br>';
	}
}
?>
<?php
$file = file_get_contents('../data/processed-ids.txt');
if (strpos($file, $rowData[0]) !== false) echo '<div>Reviewed.</div>';
else { echo '<a href="?mark=2&amp;id='.urlencode($rowData[0]).'">Review</a>'; }
?>
	</div>
	<hr>
	<div>
<?php
$has_cm = false;
$cm = file('../comments.txt');
foreach ($cm as $c) {
	if (str_startswith($c, $rowData[0])) {
		$has_cm = true;
		echo '<h2>Comments</h2>';
		echo trim($c);
	}
}
?>
	</div>
	<div>
<?php
foreach (['GHZR', 'K', 'SAT', 'TW', 'UK', 'UTC', 'IRGN2179 Henry Review - Part 2'] as $cm_n) {
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
if (!$has_cm) {
	echo '<h2>Comments</h2>';
	echo '<textarea height=200 style="border:1px solid #999;padding:4px;width:280px;font-family:inherit">'.$sq_number;
	echo ' ' . implode('|',array_filter([$rowData[10], $rowData[4], $rowData[6], $rowData[8], $rowData[2]], function($e) {
		if (trim($e) === '') return false;
		return true;
	}));
	echo '</textarea>';
}
?>
	</div>
	<p><? $count = count(file('../data/processed.txt')); echo $count .' out of 5547 processed, ' . (5547-$count) . ' remaining.'; ?></p>
</section>
</div>
<hr>
<h2>Raw Info</h2>
<?php
		echo '<table>';
		foreach ($rowData as $cell => $value) {
			echo '<tr><td>' . $cell . ' - ' . htmlspecialchars($firstRow[$cell]) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
		}
		echo '</table>';
?>
<? } ?>
</div>
<script src="jquery.js"></script>
<script>
(function() {
	$(window).on('popstate', function() { window.location.reload(); })
	function attach() {
		var n = $('#nav_next');
		var h = n[0].href;
		console.log(h);
		$.get(h, function(html) {
			$(html).find('img').each(function(){
				var i = new Image;
				i.src = $(this).attr('src');
			});
			n.on('click', function(e) {
				if (e.ctrlKey) return;
				e.preventDefault();
				history.pushState({}, null, h);
				$('body').html(html);
				var title = $('body').find('title');
				document.title = title.text();
				title.remove();
			});
		});
	}
	attach();
})();
</script>