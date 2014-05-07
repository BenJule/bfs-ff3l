<?php

$classFile= realpath(__DIR__ . '/../inc/bfs.class.php');
if ((!file_exists($classFile))||(!is_readable($classFile))) {
	die($classFile . ' doesn\'t exist.');
}

try {
	require_once($classFile);
	$bfs = new BitleaderFirewallStatistics();
} catch (Exception $e) {
	die('Error loading the core class file. The error was: ' . $e->getMessage());
}

?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>BFS</title>
		<link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" />
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel="shortcut icon" type="image.png" href="favicon.png" />
		<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	</head>
	<body>
		<?php require_once($bfs->config['modal']);?>
		<div class="navbar navbar-default navbar-fixed-top-disabled" role="navigation">
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="<?php echo htmlentities($_SERVER['REQUEST_URI']);?>">BFS</a>
				</div>
				<ul class="nav navbar-nav pull-right">
					<li><a href="#about" data-toggle="modal" data-target="#about">About</a></li>
				</ul>
			</div>
	    </div>
		<div class="container-fluid">
			<div class="col-lg-12">
				<div
					class="panel panel-danger load-highchart"
					id="highchart-throughput"
					data-source="json"
					data-ytitle=""
					data-type="area"
					data-stacking="percentage"
					data-tooltip-convert="B"
					data-url="type=throughput"
				>
					<div class="panel-heading">
						<h4 class="panel-title">Total Throughput
							<small>(area type / stacked / statistics for <?php echo $bfs->config['pubif'];?>)</small>
						</h4>
					</div>
					<div class="panel-body"></div>
				</div>
			</div>
			<div class="col-lg-12">
				<div
					class="panel panel-success load-highchart"
					id="highchart-bytes"
					data-source="json"
					data-ytitle=""
					data-alias="total"
					data-type="areaspline"
					data-stacking="percentage"
					data-tooltip-convert="B"
					data-url="type=bytes"
				>
					<div class="panel-heading">
						<h4 class="panel-title">Total Bytes
							<small>(type areaspline / stacked / statistics for <?php echo $bfs->config['pubif'];?>)</small>
						</h4>
					</div>
					<div class="panel-body"></div>
				</div>
			</div>
			<div class="col-lg-6">
				<div
					class="panel panel-default load-highchart"
					id="highchart-packets"
					data-source="json"
					data-ytitle=""
					data-colors="#000000|#F28F43|#FE123A|#910000|#0f667a|#8bbc21"
					data-type="spline"
					data-url="type=packets"
					data-legend="disabled"
				>
					<div class="panel-heading">
						<h4 class="panel-title">Total Packets
							<small>(type spline / custom colors / no legend / statistics for <?php echo $bfs->config['pubif'];?>)</small>
						</h4>
					</div>
					<div class="panel-body"></div>
				</div>
			</div>
			<div class="col-lg-6">
				<div
					class="panel panel-warning load-highchart"
					id="highchart-packets"
					data-source="json"
					data-ytitle=""
					data-colors="#000000|#F28F43|#FE123A|#910000|#0f667a|#8bbc21"
					data-type="spline"
					data-url="type=packets"
					data-legend="disabled"
					data-stacking="percentage"
				>
					<div class="panel-heading">
						<h4 class="panel-title">Total Packets
							<small>(type spline / custom colors / stacked / no legend / statistics for <?php echo $bfs->config['pubif'];?>)</small>
						</h4>
					</div>
					<div class="panel-body"></div>
				</div>
			</div>
		</div>
		<script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
		<script type="text/javascript" src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
		<script type="text/javascript" src="//code.highcharts.com/highcharts.js"></script>
		<script type="text/javascript" src="bfs.js"></script>
	</body>
</html>

