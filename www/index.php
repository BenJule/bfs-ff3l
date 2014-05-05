<?php

$coreFile = '../inc/bfs.class.php';
if ((!file_exists($coreFile))||(!is_readable($coreFile))) {
	die($coreFile . ' doesn\'t exist.');
}

try {
	require_once($coreFile);
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
		<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	</head>
	<body>
		<!-- Modal -->
		<div class="modal fade" id="about" tabindex="-1" role="dialog" aria-labelledby="aboutLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="aboutLabel">About bfs</h4>
					</div>
					<div class="modal-body">
						BitLeader Firewall Statistics (short: bfs)
						<p>&copy;2014 Alexandru Thomae <a href="mailto:tlex@bitleader.com">tlex@bitleader.com</a></p>
						<h4>Source Code</h4>
						<p>The source code is available and can be downloaded from GitHub:
						<a href="https://github.com/tlex/bfs" target="_blank">https://github.com/tlex/bfs</a>.</p>
						<h4>Frontend Technologies</h4>
						<ul>
							<li>Bootstrap - <a href="http://getbootstrap.com" target="_blank">http://getbootstrap.com</a></li>
							<li>jQuery - <a href="http://jquery.com" target="_blank">http://jquery.com</a></li>
							<li>Highcharts - <a href="http://www.highcharts.com/" target="_blank">http://www.highcharts.com/</a></li>
						</ul>
						<hr>
						<h5>License - GPLv3</h5>
						<p><small>This program is free software: you can redistribute it and/or modify
							it under the terms of the GNU General Public License as published by
							the Free Software Foundation, either version 3 of the License, or
							(at your option) any later version.</small></p>
						<p><small>This program is distributed in the hope that it will be useful,
							but WITHOUT ANY WARRANTY; without even the implied warranty of
							MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
							GNU General Public License for more details.</small></p>
						<p><small>You should have received a copy of the GNU General Public License
							along with this program.  If not, see <a href="http://www.gnu.org/licenses/"
							target="_blank">http://www.gnu.org/licenses/</a>.</small></p>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		<!-- /Modal -->

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
					class="panel panel-success load-highchart"
					id="highchart-throughput"
					data-source="json"
					data-ytitle=""
					data-title=""
					data-colors="#000000|#F28F43|#FE123A|#910000|#0f667a|#8bbc21"
					data-type="area"
					data-stacking="percentage"
					data-height="200"
					data-tooltip-convert="B"
					data-url="type=throughput"
				>
					<div class="panel-heading">
						<h4 class="panel-title">Total Throughput
							<small>(DB <?php echo $bfs->config['db'];?>: stacked statistics for <?php echo $bfs->config['pubif'];?>)</small>
						</h4>
					</div>
					<div class="panel-body"></div>
				</div>
			</div>
			<div class="col-lg-6">
				<div
					class="panel panel-success load-highchart"
					id="highchart-bytes"
					data-source="json"
					data-ytitle=""
					data-alias="total"
					data-colors="#000000|#F28F43|#FE123A|#910000|#0f667a|#8bbc21"
					data-title=""
					data-type="area"
					data-stacking="percentage"
					data-height="140"
					data-tooltip-convert="B"
					data-url="type=bytes"
					data-legend="disabled"
				>
					<div class="panel-heading">
						<h4 class="panel-title">Total Bytes
							<small>(stacked statistics for <?php echo $bfs->config['pubif'];?>)</small>
						</h4>
					</div>
					<div class="panel-body"></div>
				</div>
			</div>
			<div class="col-lg-6">
				<div
					class="panel panel-success load-highchart"
					id="highchart-packets"
					data-source="json"
					data-ytitle=""
					data-colors="#000000|#F28F43|#FE123A|#910000|#0f667a|#8bbc21"
					data-title=""
					data-type="line"
					data-height="140"
					data-url="type=packets"
					data-legend="disabled"
				>
					<div class="panel-heading">
						<h4 class="panel-title">Total Packets
							<small>(statistics for <?php echo $bfs->config['pubif'];?>)</small>
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

