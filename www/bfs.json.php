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

if (isset($_REQUEST['start']) && $bfs->isTimestamp($_REQUEST['start'])) {
	$options['start'] = $_REQUEST['start'];
}

if (isset($_REQUEST['end']) && $bfs->isTimestamp($_REQUEST['end'])) {
	$options['end'] = $_REQUEST['end'];
}


//Request for data from a specific database
if (isset($_REQUEST['file']) && in_array($_REQUEST['file'],$files)) {
	$values[] = $bfs->getValues($_REQUEST['file'], $options);
} elseif (!isset($_REQUEST['file'])) {
	foreach ($files AS $file) {
		try {
			$values[] = $bfs->getValues($file,$options);
		} catch (Exception $e) {
			$values[] = 'Error loading ' . $file . '. The Exception message is: ' . $e->getMessage();
		}
	}
} else {
	$values[] = 'The database filename requested is invalid.';
}

header('Content-Type: application/json');

echo json_encode($values);

