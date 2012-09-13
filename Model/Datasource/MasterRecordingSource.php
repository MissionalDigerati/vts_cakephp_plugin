<?php
/**
 * This file is part of Video Translator Service CakePHP Plugin.
 * 
 * Video Translator Service CakePHP Plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Video Translator Service CakePHP Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see 
 * <http://www.gnu.org/licenses/>.
 *
 * @author Johnathan Pulos <johnathan@missionaldigerati.org>
 * @copyright Copyright 2012 Missional Digerati
 * 
 */
/**
 * Import CakePHP's HttpSocket Library
 *
 * @author Johnathan Pulos
 */
App::uses('HttpSocket', 'Network/Http');
/**
 * Get the sharedDSMethods in Vendor directory of plugin.
 *
 * @author Johnathan Pulos
 */
App::import('Vendor', 'VideoTranslatorService.sharedDSMethods');
/**
 * This is a datasource for interacting with the Video Translator Service Master Recording Model
 *
 * @package Video Translator Service CakePHP Plugin
 * @author Johnathan Pulos
 */
class MasterRecordingSource extends DataSource {
	
	/**
	 * Describe the datasource
	 *
	 * @var string
	 * @access public
	 */
	public $description = "A Video Translator Service datasource for interacting with the Master Recording Model of the API.";
	
	/**
	 * The configuration for this datasource
	 *
	 * @var array
	 * @access public
	 */
	public $config = array('vtsUrl' => '', 'vtsApiKey' => '');
	
	/**
	 * Define the schema of the DataSource
	 *
	 * @var array
	 * @access protected
	 */
	protected $_schema = array();
	
	/**
	 * column definition.  Required for cakePHP or it will barf errors.
	 *
	 * @var array
	 */
		public $columns = array(
			'primary_key' => array('name' => 'NOT NULL AUTO_INCREMENT'),
			'string' => array('name' => 'varchar', 'limit' => '255'),
			'text' => array('name' => 'text'),
			'integer' => array('name' => 'int', 'limit' => '11', 'formatter' => 'intval'),
			'datetime' => array('name' => 'datetime', 'format' => 'Y-m-d H:i:s', 'formatter' => 'date'),
			'boolean' => array('name' => 'tinyint', 'limit' => '1')
		);
		/**
		 * The sharedDSMethods object
		 *
		 * @var object
		 */
		public $sharedMethods;
	
	/**
	 * Initialize the DataSource
	 *
	 * @param array $config the configuration settings
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function __construct($config) {        
		parent::__construct($config);
		$this->Http = new HttpSocket();
		$this->sharedMethods = new sharedDSMethods();
	}
	
	/**
	 * Required for caching
	 *
	 * @return null
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function listSources() {
      return null;
  }
	
	/**
	 * describe the schema of the DataSource
	 *
	 * @param Model $Model The Model Object
	 * @return array
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function describe(Model $Model) {
		return $Model->schema();
	}
	
	/**
	 * retrieve the count of the given object
	 * We don't count the records here but return a string to be passed to
	 * read() which will do the actual counting. The easiest way is to just
	 * return the string 'COUNT' and check for it in read() where
	 * $data['fields'] == 'COUNT'.
	 *
	 * @param Model $Model The Model Object
	 * @param string $func 
	 * @param string $params options for the count
	 * @return string
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function calculate(Model $Model, $func, $params = array()) {
		return 'COUNT';
	}
	
	/**
	 * Read a specific Master Recording
	 *
	 * @param Model $Model The Model object
	 * @param array $data settings for the query
	 * @return array
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function read(Model $Model, $data) {
		/**
		 * Here we do the actual count as instructed by our calculate()
     * method above. We could either check the remote source or some
     * other way to get the record count. Here we'll simply return 1 so
     * update() and delete() will assume the record exists.
     * 
		 * @author Johnathan Pulos
		 */
    if ($data['fields'] == 'COUNT') {
        return array(array(array('count' => 1)));
    }
		if(!isset($data['conditions'])) {
			throw new CakeException("API requires a "  . $Model->alias . ".id.");
		}
		$id = $this->sharedMethods->getModelId($Model, $data['conditions']);
		$translationRequestToken = $this->sharedMethods->getToken($data['conditions']);
		if($translationRequestToken == '') {
			throw new CakeException("Please check your condition.  Unable to locate the translation request token.");
		}
		$url = $this->config['vtsUrl'] . "master_recordings/" . $id . ".json";
    $res = json_decode($this->Http->get($url, array('translation_request_token' => $translationRequestToken)), true);
    if (is_null($res) || empty($res)) {
        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
    }
		$results = array();
		if($res['vts']['status'] == 'error') {
			$error = "VTS ERROR (Reading a Master Recording) - Message: " . $res['vts']['message'];
			if(isset($res['vts']['details'])) {
				$error .= " Details: " . $res['vts']['details'];
			}
			$this->log($error);
			return array();
		}
		if(isset($res['vts']['master_recording'])) {
			/**
			 * We are getting a single translation request
			 *
			 * @author Johnathan Pulos
			 */
	    $results[] = array($Model->alias => $res['vts']['master_recording']);
		}
		return $results;
	}
	
	/**
	 * Create a new Master Recording
	 *
	 * @param Model $Model The Model object
	 * @param array $fields an array of fields to save
	 * @param array $values an array of the values to save
	 * @return boolean
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function create(Model $Model, $fields = array(), $values = array()) {
		$formData = array_combine($fields, $values);
		$url = $this->config['vtsUrl'] . "master_recordings.json";
    $json = $this->Http->post($url, $formData);
    $res = json_decode($json, true);
    if (is_null($res)) {
        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
    }
		if($res['vts']['status'] == 'error') {
			$error = "VTS ERROR (Creating/Updating a Master Recording) - Message: " . $res['vts']['message'];
			if(isset($res['vts']['details'])) {
				$error .= " Details: " . $res['vts']['details'];
			}
			$this->log($error);
			return false;
		}
		if((isset($res['vts']['master_recording'])) && (!empty($res['vts']['master_recording']))) {
			/**
			 * We are getting a single translation request
			 *
			 * @author Johnathan Pulos
			 */
	    $Model->id = $res['vts']['master_recording']['id'];
			return true;
		}
		return false;
	}
	
	/**
	 * Delete the Master Recording
	 *
	 * @param Model $Model The Model Object
	 * @param array $conditions array of conditions
	 * @return boolean
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function delete(Model $Model, $conditions = null) {
		$id = $this->sharedMethods->getModelId($Model, $conditions);
		if(!isset($Model->translation_request_token)) {
			throw new CakeException("API requires a MasterRecording.translation_request_token.");
		}
		$url = $this->config['vtsUrl'] . "master_recordings/" . $id . ".json";
		$json = $this->Http->post($url, array('id' => $id, '_method' => 'DELETE', 'translation_request_token' => $Model->translation_request_token));
		$res = json_decode($json, true);
    if (is_null($res)) {
        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
    }
		if($res['vts']['status'] == 'error') {
			$error = "VTS ERROR (Deleting a Master Recording) - Message: " . $res['vts']['message'];
			if(isset($res['vts']['details'])) {
				$error .= " Details: " . $res['vts']['details'];
			}
			$this->log($error);
			return false;
		}else {
			return true;
		}
	}
	
}