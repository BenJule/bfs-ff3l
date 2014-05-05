<?php

$coreFile = __DIR__ . '/../inc/bfs.class.php';
if ((!file_exists($coreFile))||(!is_readable($coreFile))) {
	die ($coreFile . ' doesn\'t exist.');
}

try {
	require_once($coreFile);
	$bfs = new BitleaderFirewallStatistics();
} catch (Exception $e) {
	die('Error loading the core class file. The error was: ' . $e->getMessage());
}


$files = $bfs->getFiles();
$types = $bfs->types;
$values = array();
$options = array('type'=>null);

if (isset($_REQUEST['type']) && in_array($_REQUEST['type'],$types)) {
	$options['type'] = $_REQUEST['type'];
}

foreach ($files AS $file) {
	$values[] = $bfs->getValues($file,$options);
}

header('Content-Type: application/json');

echo json_encode($values);

