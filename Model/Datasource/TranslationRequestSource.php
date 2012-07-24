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
 * This is a datasource for interacting with the Video Translator Service Translation Request Model
 *
 * @package Video Translator Service CakePHP Plugin
 * @author Johnathan Pulos
 */
class TranslationRequestSource extends DataSource {
	
	/**
	 * Describe the datasource
	 *
	 * @var string
	 * @access public
	 */
	public $description = "A Video Translator Service datasource for interacting with the Translation Request Model of the API.";
	
	/**
	 * The configuration for this datasource
	 *
	 * @var array
	 * @access public
	 */
	public $config = array('vtsUrl' => '');
	
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
	 * Initialize the DataSource
	 *
	 * @param array $config the configuration settings
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function __construct($config) {        
		parent::__construct($config);
		$this->Http = new HttpSocket();
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
	 * Read a specific Translation Request
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
			throw new CakeException("API requires a Translation Request.id.");
		}
		$id = $this->getModelId($Model, $data['conditions']);
		$url = $this->config['vtsUrl'] . "translation_requests/" . $id . ".json";
    $res = json_decode($this->Http->get($url, array()), true);
    if (is_null($res) || empty($res)) {
        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
    }
		$results = array();
		if(isset($res['vts']['translation_request'])) {
			/**
			 * We are getting a single translation request
			 *
			 * @author Johnathan Pulos
			 */
	    $results[] = array($Model->alias => $res['vts']['translation_request']);
		}
		return $results;
	}
	
	/**
	 * Create a new Translation Request
	 *
	 * @param Model $Model The Model object
	 * @param array $fields an array of fields to save
	 * @param array $values an array of the values to save
	 * @return boolean
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function create(Model $Model, $fields = array(), $values = array()) {
		$data = array();
		$url = $this->config['vtsUrl'] . "translation_requests.json";
    $json = $this->Http->post($url, $data);
    $res = json_decode($json, true);
    if (is_null($res)) {
        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
    }
		if((isset($res['vts']['translation_request'])) && (!empty($res['vts']['translation_request']))) {
			/**
			 * We are getting a single translation request
			 *
			 * @author Johnathan Pulos
			 */
	    $Model->id = $res['vts']['translation_request']['id'];
		}
		if($res['vts']['status'] == 'error') {
			return false;
		}else {
			return true;
		}
	}
	
	/**
	 * Delete the TranslationRequest
	 *
	 * @param Model $Model The Model Object
	 * @param array $conditions array of conditions
	 * @return boolean
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function delete(Model $Model, $conditions = null) {
		$id = $this->getModelId($Model, $conditions);
		$url = $this->config['vtsUrl'] . "translation_requests/" . $id . ".json";
		$json = $this->Http->post($url, array('id' => $id, '_method' => 'DELETE'));
		$res = json_decode($json, true);
    if (is_null($res)) {
        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
    }
		if($res['vts']['status'] == 'error') {
			return false;
		}else {
			return true;
		}
	}
	
	/**
	 * Get the Model.id based on the passed conditions
	 *
	 * @param Model $Model The Model Object
	 * @param array $conditions an array of conditions
	 * @return integer
	 * @access private
	 * @author Johnathan Pulos
	 */
	private function getModelId(Model $Model, $conditions) {
		if(isset($conditions[$Model->alias . ".id"])) {
			return $conditions[$Model->alias . ".id"];
		}else if(isset($conditions["id"])) {
			return $conditions["id"];
		}else {
			throw new CakeException("API requires a Translation Request.id.");
		}
	}
	
}