<?php
/**
 * BitLeaderFirewallStatistics
 *
 * PHP Version 5.3
 *
 * @package BFS
 * @author tlex <tlex@e-tel.eu>
 * @version 1.0
 * @copyright 2014 Alexandru Thomae / BitLeader (http://www.bitleader.com)
 * @license http://www.gnu.org/licenses/ GPLv3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * class BitleaderFirewallStatistics definition
 */
class BitleaderFirewallStatistics {

	/**
	 * @var string The config file
	 */
	public $coreConf = 'bfs.conf';

	/**
	 * @var array The actual config
	 *
	 * Setting the values to "false" because with "null" isset returns false.
	 */
	public $config = array(
		'pubif' => null,
		'stats_comment_prefix' => null,
		'csv_suffix' => null,
		'rrd_suffix' => null,
		'db' => 'rrd',
		'db_folder' => null,
		'modal' => null,
		'enable_collectd' => null,
		'collectd_rrd_path' => null,
		'collectd_cpu_stats' => null,
	);

	/**
	 * These are changable at runtime
	 *
	 * @var array the settings
	 */
	public $settings = array(
		'start' => null,
		'end' => null,
		'type' => 'throughput',
		'pubif' => false,
		'stats_comment_prefix' => false,
		'csv_suffix' => false,
		'rrd_suffix' => false,
		'db' => false,
		'db_folder' => false,
		'tpl_folder' => false,
		'modal' => false,
		'db_file' => null,
	);

	/**
	 * @var string The base absolute path to BFS
	 */
	public $basePath = null;

	/**
	 * @var array The type of metric requested
	 */
	public $types = array(
		'bytes',
		'throughput',
		'packets',
		'collectd',
	);

	/**
	 * Class constructor
	 *
	 * @param string $confFile The configuration file for BFS - normally ${BASEFOLDER}/conf/bfs.conf
	 * @throws Exception If the config file doesn't exist or is unreadable
	 */
	public function __construct($confFile = null) {
		$this->basePath = realpath(__DIR__ . '/..');

		//Set defaults for start and end
		$this->settings['start'] = (time() - (60 * 60 * 24));
		$this->settings['end'] = time();

		if ($confFile) {
			$fullPath = $this->basePath . '/conf/' . $confFile;
			if (file_exists($fullPath) && (is_readable($fullPath))) {
				$this->coreConf = $fullPath;
			}
		}

		//Rewrites the relative path to the core config file to an absolute path
		$this->coreConf = $this->basePath . '/conf/' . $this->coreConf;

		if ((! file_exists($this->coreConf)) ||(! is_readable($this->coreConf))) {
			throw new Exception('Configuration file ' . $this->coreConf  . ' not found!');
		}

		//Now let's load the settings
		$this->getSettings();
	}

	/**
	 * The setter function for $this->settings['start']
	 *
	 * @param timestamp $timestamp
	 */
	public function setStart($timestamp) {
		if ($this->isTimestamp($timestamp)) {
			$this->settings['start'] = $timestamp;
		}
	}

	/**
	 * The setter function for $this->settings['end']
	 *
	 * @param timestamp $timestamp
	 */
	public function setEnd($timestamp) {
		if ($this->isTimestamp($timestamp)) {
			$this->settings['end'] = $timestamp;
		}
	}

	/**
	 * The setter function for $this->settings['type']
	 *
	 * @param string $type One of $this->types[]
	 */
	public function setMetricType($type) {
		if (in_array($type, $this->types)) {
			$this->settings['type'] = $type;
		}
	}

	/**
	 * The setter function for $this->settings['folder']
	 *
	 * @param string $folder One of the metrics in $this->config['collectd_rrd_path']/
	 */
	public function setMetricFolder($folder) {
		if (!$this->config['collectd_rrd_path']) {
			throw new Exception('The collectd RRD path is not set.');
		}

		if ((!is_dir($this->config['collectd_rrd_path'])) || (!is_readable($this->config['collectd_rrd_path']))) {
			throw new Exception('The collectd RRD path is invalid.');
		}

		if (
			(!is_dir($this->config['collectd_rrd_path'] . DIRECTORY_SEPARATOR . $folder))
			|| (!is_readable($this->config['collectd_rrd_path'] . DIRECTORY_SEPARATOR . $folder))
		) {
			throw new Exception('The specified metric folder is invalid.');
		}
		$this->config['db_folder'] = $this->config['collectd_rrd_path'] . DIRECTORY_SEPARATOR . $folder;
		$this->config['db'] = 'collectd';
	}

	public function setMetricFile($file) {
		if ($this->config['db'] != 'collectd') {
			throw new Exception('Set the collectd folder first!');
		}
		if (file_exists($this->config['db_folder'] . DIRECTORY_SEPARATOR . $file . '.rrd')) {
			$this->config['db_file'] = $file . '.rrd';
		}
	}

	/**
	 * Loads the settings from the config file
	 */
	public function getSettings() {
		$configContents = file_get_contents($this->coreConf);

		$configLines = explode("\n",$configContents);

		foreach ($configLines AS $configLine) {
			if (preg_match('/^([\-_A-Z]+)\=([\'\"])([A-Za-z\-_0-9\.\/]+)([\'\"])(.*)$/',$configLine,$matches)) {
				if (array_key_exists(strtolower($matches[1]),$this->config)) {
					$this->config[strtolower($matches[1])] = $matches[3];
				}
			}
		}

		//maintain backwords compatibility for new configuration variables
		$this->_makeCompatible();

		//Folder paths
		$this->config['tpl_folder'] = $this->basePath . '/' . $this->config['tpl_folder'];

		//Fix file paths to be absolute
		$this->config['modal'] = $this->config['tpl_folder'] . '/' . $this->config['modal'];

		//Folder paths
		$this->config['db_folder'] = $this->basePath . '/' . $this->config['db_folder'];

		if ((! is_dir($this->config['db_folder'])) || (! is_readable($this->config['db_folder']))) {
			throw new Exception('Database folder ' . $this->config['db_folder'] . ' is not accessible.');
		}

		foreach ($this->config AS $key => $config) {
			if ($config === null) {
				throw new Exception('Missing configuration value in ' . $this->coreConf . '. Please add "' . strtoupper($key) . '=your-value"');
			}
		}
	}

	/**
	 * Maintaints backwards compatibility, in case new variables have been added
	 *
	 * Sets $this->config[newVariable] to a default value, if it hasn't been added to the config file.
	 */
	private function _makeCompatible() {
		if (!$this->config['modal']) {
			$this->config['modal'] = 'modal.tpl';
		}
		if (!$this->config['tpl_folder']) {
			$this->config['tpl_folder'] = 'tpl';
		}
	}

	/**
	 * Finds all the database files, based on self::config['db']
	 *
	 * RRD Database will be chosen, if the DB is set to "both"
	 * @return string[] The relative paths of the CSV files
	 */
	public function getFiles() {
		$folderContents = scandir($this->config['db_folder']);
		$validFiles = array();
		foreach ($folderContents AS $file) {
			$suffix = null;
			switch($this->config['db']){
				case 'both':
				case 'rrd':
					$suffix = $this->config['rrd_suffix'];
					break;
				case 'csv':
					$suffix = $this->config['csv_suffix'];
					break;
				case 'collectd':
					$this->config['rrd_suffix'] = '.rrd';
					$suffix = '.rrd';
					if ($this->config['db_file']) {
						return array($this->config['db_file']);
					}
					break;
				default:
					throw new Exception('Invalid database in ' . $this->coreConf);
			}

			if (strpos($file,$suffix)) {
				$validFiles[] = $file;
			}
		}
		return $validFiles;
	}

	/**
	 * Loads the contents of the CSV file and returns an array with the values
	 *
	 * @param string $file The name of the csv file. Will be checked against self::getCsvFiles()
	 * @throws Exception if the file is invalid or isn't readable
	 * @return array The values in form array('target' => $key0,'datapoints'[] => array($value,$timestamp)
	 */
	public function getValues($file) {
		if (!in_array($file,$this->getFiles())) {
			throw new Exception ('Invalid file specified');
		}

		$filePath = $this->config['db_folder'] . '/' . $file;
		if (!is_readable($filePath)) {
		    throw new Exception ('File ' . $filePath . ' is not readable (check permissions)');
		}

		if (!$this->settings['start']) {
			//							  seconds   minutes   hours   days
			$this->settings['start'] = time() - (  60    *   60    *   24  *  10);
		}

		if (!$this->settings['end']) {
			$this->settings['end'] = time();
		}

		$this->settings['file'] = $file;

		switch ($this->config['db']) {
			case 'both':
			case 'rrd':
			case 'collectd':
				return $this->_getValuesRrd($filePath);
				break;
			case 'csv':
				return $this->_getValuesCsv($filePath);
				break;
			default:
				throw new Exception('Invalid database in ' . $this->coreConf);
		}
	}

	/**
	 * Loads the values from the CSV file
	 *
	 * Warning: Can use a lot of memory, if your files are large!
	 *			If possible, switch to RRD instead!
	 *
	 * @param string $file The CSV file (absolute path)
	 * @param array $options see self::getValues()
	 * @return array
	 */
	private function _getValuesCsv($file) {
		$fileContent = file_get_contents($file);
		$lines = explode("\n",$fileContent);
		$values = array();
		foreach($lines AS $line) {
			if ($line) {
				$values[] = str_getcsv($line);
			}
		}
		$return = array();
		$index = 0;
		foreach ($values AS $value) {
			if (!isset($return['target'])) {
				$return = array(
					'target' => $value[0],
					'datapoints' => array(),
					'type' => $this->settings['type'],
				);
			}
			$timestamp = intval($value[3]);
			$previousTimestamp = intval(0);
			if ($index != 0) {
				$previousTimestamp = intval($return['datapoints'][$index-1][1]);
			}
			if (
				(intval($this->settings['start']) <= $timestamp)
				&& (intval($this->settings['end']) >= $timestamp)
				&& ($timestamp > $previousTimestamp)         //catching possible errors in the CSV file
			) {
				$throughput = null;
				$bytes = intval($value[2]);
				$packets = intval($value[1]);
				if ($index !== 0) {
					$interval = ($timestamp - $previousTimestamp);
					if ($interval > 0) {
						$throughput = number_format(($bytes / $interval) * 60, 2, '.', '');
					}
				}
				$return['datapoints'][$index] = array($$this->settings['type'],$timestamp);
				$index++;
			}
		}
		return $return;
	}

	/**
	 * Loads the values from the RRD file
	 *
	 * @param string $file The RRD file (absolute path)
	 * @param array $options see self::getValues()
	 * @return array
	 */
	private function _getValuesRrd($file) {
		$return = array();
		$rrdResults = @rrd_fetch($file,array('AVERAGE','--start',$this->settings['start'],'--end',$this->settings['end']));
		if ($rrdResults) {
			$target = str_replace($this->config['rrd_suffix'],'',$this->settings['file']);
			$return = array(
				'target' => $target,
				'datapoints' => array(),
				'type' => $this->settings['type'],
			);

			//for 'throughput' we need to calculate the values based on bytes and interval, as we don't save it in the RRD
			if ($this->settings['type'] == 'throughput') {
				$rrdResults['data']['throughput'] = array();
				unset($rrdResults['data']['packets']);
				$bytes = array();
				foreach ($rrdResults['data']['bytes'] AS $timestamp => $metric) {
					$bytes[] = array($metric,$timestamp);
				}
				//free the memory
				unset($rrdResults['data']['bytes']);
				$counter = 0;

				foreach ($bytes AS $byte) {
					$throughput = 0;
					$timestamp = intval($byte[1]);
					if ($counter !== 0) {
						$previousBytes = $bytes[$counter-1][0];
						if (strtoupper($previousBytes) == 'NAN') {
							$throughput = 'NAN';
						} else {
							$previousTimestamp = intval($bytes[$counter-1][1]);
							$currentBytes = intval($byte[0]);
							$interval = ($timestamp - $previousTimestamp);
							$throughput = number_format(($currentBytes / $interval), 2, '.', '');
						}
					}
					$rrdResults['data']['throughput'][$timestamp] = $throughput; 
					$counter++;

				}
			}

			//free the memory
			foreach ($rrdResults['data'] AS $type => $data) {
				if ($this->settings['type'] != 'collectd') {
					if ($this->settings['type'] != $type) {
						unset($rrdResults['data'][$type]);
					} else {
						$dataType = $type;
					}
				} else {
					$dataType = $type;
				}
			}


			foreach ($rrdResults['data'][$dataType] AS $timestamp => $metric) {
				if (strtoupper($metric) == 'NAN') {
					$metric = null;
				} else {
					$metric = number_format($metric, 0, '.' , '');
					//Something went wrong at insert time and now the RRD has an invalid number
					if ($metric < 0) {
						$metric = null;
					}	
				}
				$return['datapoints'][] = array($metric, $timestamp);
			}
		}
		return $return;
	}

	/**
	 * Checks if a variable is a valid unix timestamp
	 *
	 * @link http://stackoverflow.com/a/2524761
	 * @param string $value The value to be testet
	 * @return bool true if valid timestamp
	 */
	public function isTimestamp($value) {
		return ((string) (int) $value === $value)
			&& ($value <= PHP_INT_MAX)
			&& ($value >= ~PHP_INT_MAX);
	}
}
