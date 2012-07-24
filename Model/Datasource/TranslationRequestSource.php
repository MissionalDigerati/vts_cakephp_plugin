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
	public $config = array('vts_url' => '');
	
	/**
	 * Define the schema of the DataSource
	 *
	 * @var array
	 * @access protected
	 */
	protected $_schema = array(	'id' => 				array(
																											'type' => 'integer', 
																											'null' => false, 
																											'key' => 'primary'
																							),
															'token' => 			array(
																											'type' => 'string', 
																											'null' => false, 
																											'length' => 255
																							),
															'created' => 		array(
																											'type' => 'datetime', 
																											'null' => true
																							),
															'modified' => 	array(
																											'type' => 'datetime', 
																											'null' => true
																							),
															'expires_at' => array(
																											'type' => 'datetime', 
																											'null' => true
																							),
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
	 * describe the schema of the DataSource
	 *
	 * @param Model $Model The Model Object
	 * @return array
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function describe(Model $Model) {
		return $this->_schema;
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
		if(isset($data['conditions'][$Model->alias . ".id"])) {
			$id = $data['conditions'][$Model->alias . ".id"];
		}else if(isset($data['conditions']["id"])) {
			$id = $data['conditions']["id"];
		}else {
			throw new CakeException("API requires a Translation Request.id.");
		}
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
}