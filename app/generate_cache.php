<?php
die;

if (isset($_POST['store'])) {
	$data = substr($_POST['data'], strlen('data:image/png;base64,'));
	$data = base64_decode($data);
	file_put_contents('cache/' . $_POST['store'], $data);
	exit;
}

require_once 'vendor/autoload.php';
require_once 'z.log.php';
require_once 'library.php';

$sources_cache = new SourcesCache();
$character_cache = new CharacterCache();
$ids_cache = new IDSCache();
?>
<script src="jquery.js"></script>
<?

$sq_number = '00000';

for ($i = 1; $i <= 5457; $i++) {
	$next = str_pad(intval(ltrim($sq_number, '0')) + $i, 5, '0', STR_PAD_LEFT);
	$char = $character_cache->get($next);
	$char->renderCodeChartCutting();
}
?>