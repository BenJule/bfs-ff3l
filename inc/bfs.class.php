<?php
/**
 * BitLeaderFirewallStatistics
 *
 * PHP Version 5.3
 *
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
 **/

/**
 * class BitleaderFirewallStatistics definition
 **/
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
		'pubif' => false,
		'stats_comment_prefix' => false,
		'csv_suffix' => false,
		'rrd_suffix' => false,
		'db' => false,
		'db_folder' => false,
		'modal' => false,
	);

	/**
	 * @var array The type of metric requested
	 */
	public $types = array(
		'bytes',
		'throughput',
		'packets',
	);

	/**
	 * Class constructor
	 *
	 * @param string $confFile The configuration file for BFS - normally ${BASEFOLDER}/conf/bfs.conf
	 * @throws Exception If the config file doesn't exist or is unreadable
	 */
	public function __construct($confFile = null) {
		if ($confFile) {
			$fullPath = realpath(__DIR__ . '/../conf/' . $confFile);
			if (file_exists($fullPath) && (is_readable($fullPath))) {
				$this->coreConf = $fullPath;
			}
		}

		//Rewrites the relative path to the core config file to an absolute path
		$this->coreConf = realpath(__DIR__ . '/../conf/' . $this->coreConf);
		if ((!file_exists($this->coreConf)) || (!is_readable($this->coreConf))) {
			throw new Exception('Configuration file ' . $this->coreConf  . ' not found!');
		}

		//Now let's load the settings
		$this->getSettings();
	}

	/**
	 * Loads the settings from the config file
	 */
	public function getSettings() {
		$configContents = file_get_contents($this->coreConf);
		$configLines = explode("\n",$configContents);
		foreach ($configLines AS $configLine) {
			if (preg_match('/^([\-_A-Z]+)\=([\'\"])([A-Za-z\-_0-9\.]+)([\'\"])(.*)$/',$configLine,$matches)) {
				if (isset($this->config[strtolower($matches[1])])) {
					$this->config[strtolower($matches[1])] = $matches[3];
				}
			}
		}

		//maintain backwords compatibility for new configuration variables
		$this->_makeCompatible();

		//Fix file paths to be absolute
		$this->config['modal'] = realpath(__DIR__ . '/../inc/' . $this->config['modal']);

		foreach ($this->config AS $key => $config) {
			if ($config === false) {
				throw new Exception('Missing configuration value in ' . $this->coreConf . '. Please add "' . strtoupper($key) . '=your-value"');
			}
		}
		//For tests only
		//$this->config['db'] = 'csv';
	}

	/**
	 * Maintaints backwards compatibility, in case new variables have been added
	 *
	 * Sets $this->config[newVariable] to a default value, if it hasn't been added to the config file.
	 */
	private function _makeCompatible() {
		if (!$this->config['modal']) {
			$this->config['modal'] = 'bfs.modal.php';
		}
	}

	/**
	 * Finds all the database files, based on self::config['db']
	 *
	 * RRD Database will be chosen, if the DB is set to "both"
	 * @return string[] The relative paths of the CSV files
	 */
	public function getFiles() {
		if (!is_readable(__DIR__ . '/../' . $this->config['db_folder']  . '/')) {
		    throw new Exception (__DIR__ . '/../' . $this->config['db_folder'] . '/ is not readable.');
		}
		$folderContents = scandir(__DIR__ . '/../' . $this->config['db_folder'] . '/');
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
	 * @param array $options containing:
	 *				['type'] - One of self::types
	 *				['start'](optional) - start timestamp - defalt now-48h
	 *				['end'](optional) end timestamp - default now
	 * @throws Exception if the file is invalid or isn't readable
	 * @return array The values in form array('target' => $key0,'datapoints'[] => array($value,$timestamp)
	 */
	public function getValues($file,array $options = array()) {
		if (!in_array($file,$this->getFiles())) {
			throw new Exception ('Invalid file specified');
		}

		$absolutePath = __DIR__ . '/../' . $this->config['db_folder'] . '/' . $file;
		if (!is_readable($absolutePath)) {
		    throw new Exception ('File $absolutePath is not readable (check permissions)');
		}

		if ((!$options) || (!isset($options['type'])) || (!in_array($options['type'],$this->types))) {
			$options['type'] = 'throughput';
		}

		if (!isset($options['start'])) {
			//							  seconds   minutes   hours   days
			$options['start'] = time() - (  60    *   60    *   24  *  10);
			$options['start'] = 1399100271;
		}

		if (!isset($options['end'])) {
			$options['end'] = time();
		}

		$options['file'] = $file;

		$return = array();

		switch ($this->config['db']) {
			case 'both':
			case 'rrd':
				$return = $this->_getValuesRrd($absolutePath, $options);
				break;
			case 'csv':
				$return = $this->_getValuesCsv($absolutePath, $options);
				break;
			default:
				throw new Exception('Invalid database in ' . $this->coreConf);
		}
		return $return;
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
	private function _getValuesCsv($file, array $options = array()) {
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
					'type' => $options['type'],
				);
			}
			$timestamp = intval($value[3]);
			$previousTimestamp = intval(0);
			if ($index != 0) {
				$previousTimestamp = intval($return['datapoints'][$index-1][1]);
			}
			if (
				(intval($options['start']) <= $timestamp)
				&& (intval($options['end']) >= $timestamp)
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
				$return['datapoints'][$index] = array($$options['type'],$timestamp);
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
	private function _getValuesRrd($file, array $options = array()) {
		$return = array();
		$rrdResults = @rrd_fetch($file,array('AVERAGE','--start',$options['start'],'--end',$options['end']));
		if ($rrdResults) {
			$target = str_replace($this->config['rrd_suffix'],'',$options['file']);
			$return = array(
				'target' => $target,
				'datapoints' => array(),
				'type' => $options['type'],
			);

			//for 'throughput' we need to calculate the values based on bytes and interval, as we don't save it in the RRD
			if ($options['type'] == 'throughput') {
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
							$throughput = number_format(($currentBytes / $interval) * 60, 2, '.', '');
						}
					}
					$rrdResults['data']['throughput'][$timestamp] = $throughput; 
					$counter++;

				}
			}

			//free the memory
			foreach ($rrdResults['data'] AS $type => $data) {
				if ($options['type'] != $type) unset($rrdResults['data'][$type]);
			}

			foreach ($rrdResults['data'][$options['type']] AS $timestamp => $metric) {
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
}
