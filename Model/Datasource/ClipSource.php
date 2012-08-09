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
 * Get the curlUtility in Vendor directory of plugin.  We use this for passing the audio file
 *
 * @author Johnathan Pulos
 */
App::import('Vendor', 'VideoTranslatorService.curlUtility');
/**
 * This is a datasource for interacting with the Video Translator Service Clip Model
 *
 * @package Video Translator Service CakePHP Plugin
 * @author Johnathan Pulos
 */
class ClipSource extends DataSource {
	
	/**
	 * Describe the datasource
	 *
	 * @var string
	 * @access public
	 */
	public $description = "A Video Translator Service datasource for interacting with the Clip Model of the API.";
	
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
		
		public $curlUtility;
	
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
		$this->curlUtility = new curlUtility();
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
	 * Read a specific Clip
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
		$limit = false;
		if((isset($data['conditions'])) && (!empty($data['conditions']))) {
			$translationRequestToken = $this->getToken($data['conditions']);
			if($translationRequestToken == '') {
				throw new CakeException("Please check your condition.  Unable to locate the translation request token.");
			}
			if (empty($data['limit'])) {
				$url = $this->config['vtsUrl'] . "clips.json?translation_request_token=" . $translationRequestToken;
			} else if($data['limit'] == 1){
				$url = $this->config['vtsUrl'] . "clips/" . $Model->id . ".json?translation_request_token=" . $translationRequestToken;
			} else{
				throw new CakeException("Can not retrieve a limit of records.");
			}
	    $json = $this->curlUtility->makeRequest($url, 'GET');
	    $res = json_decode($json, true);
	    if (is_null($res)) {
	        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
	    }
			if($res['vts']['status'] == 'error') {
				return false;
			}else {
				$results = array();
				if(isset($res['vts']['clips'])) {
			    $results[$Model->alias . "s"] = $res['vts']['clips'];
					$results['Translation']['ready_for_processing'] = $res['vts']['ready_for_processing'];
				} else if(isset($res['vts']['clip'])){
					$results[$Model->alias] = $res['vts']['clip'];
				}
				return $results;
			}
		}else {
			throw new CakeException("A Translation Request Token is required to get all the clips related to it.");
		}
		return array();
	}
	
	/**
	 * Create a new Clip
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
		$data = array(	'translation_request_token' 	=> $formData['translation_request_token'],
										'audio_file' 									=> '@'.$formData['audio_file'].";type=".$formData['mime_type'],
										'video_file_location' 				=> $formData['video_file_location']
							);
		$url = $this->config['vtsUrl'] . "clips.json";
		if(isset($formData['vts_clip_id'])) {
			$data['_method'] = 'PUT';
			$data['id'] = $formData['vts_clip_id'];
			$url = $this->config['vtsUrl'] . "clips/" . $data['id'] . ".json";
		}
    $json = $this->curlUtility->makeRequest($url, 'POST', $data);
    $res = json_decode($json, true);
    if (is_null($res)) {
        throw new CakeException("The result came back empty.  Make sure you set the vtsUrl in your app/Config/database.php, and your video translator service is running.");
    }
		if((isset($res['vts']['clip'])) && (!empty($res['vts']['clip']))) {
			/**
			 * We are getting a single translation request
			 *
			 * @author Johnathan Pulos
			 */
	    $Model->id = $res['vts']['clip']['id'];
		}
		if($res['vts']['status'] == 'error') {
			return false;
		}else {
			return true;
		}
	}
	
	/**
	 * Update the Clip.  You need to set the vts_clip_id to the clip id to update.
	 *
	 * @param Model $Model The Model object
	 * @param array $fields an array of fields to save
	 * @param array $values an array of the values to save
	 * @return boolean
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function update(Model $Model, $fields = array(), $values = array()) {
		$formData = array_combine($fields, $values);
		if((!in_array('vts_clip_id', $fields))  || (empty($formData['vts_clip_id']))) {
			throw new CakeException("API requires the vts_clip_id to be set to update a Clip.");
		}
		return $this->create($Model, $fields, $values);
  }
	
	/**
	 * Delete the Clip
	 * example:
	 * 
	 * 	$this->Clip->id = 14;
	 * $this->Clip->translation_request_token = $translation['Translation']['token'];
	 * $this->Clip->delete();
	 *
	 * @param Model $Model The Model Object
	 * @param array $conditions array of conditions
	 * @return boolean
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function delete(Model $Model, $conditions = null) {
		$id = $this->getModelId($Model, $conditions);
		if(!isset($Model->translation_request_token)) {
			throw new CakeException("API requires a Clip.translation_request_token.");
		}
		$url = $this->config['vtsUrl'] . "clips/" . $id . ".json";
		$json = $this->Http->post($url, array('id' => $id, '_method' => 'DELETE', 'translation_request_token' => $Model->translation_request_token));
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
	 * @todo Can we pull this out, since it is used in another datasource
	 */
	private function getModelId(Model $Model, $conditions) {
		if(isset($conditions[$Model->alias . ".id"])) {
			return $conditions[$Model->alias . ".id"];
		}else if(isset($conditions["id"])) {
			return $conditions["id"];
		}else {
			throw new CakeException("API requires a Clip.id.");
		}
	}
	
	/**
	 * Get the translation_request_token based on the supplied conditions
	 *
	 * @param mixed $conditions the conditions
	 * @return string
	 * @access private
	 * @author Johnathan Pulos
	 * @todo Can we pull this out, since it is used in another datasource
	 */
	private function getToken($conditions) {
		$translationRequestToken = '';
		/**
		 * determine the translation_request_token based on the conditions
		 *
		 * @author Johnathan Pulos
		 */
		if((is_string($conditions)) && (strpos($conditions, 'translation_request_token') !== false)) {
			preg_match('/translation_request_token\s*=\s*\'?"?(\w+)\'?"?/', $conditions, $matches);
			if((!empty($matches)) && (isset($matches[1]))) {
				$translationRequestToken = $matches[1];
			}
		}else if((is_array($conditions)) && (array_key_exists('translation_request_token', $conditions))) {
			$translationRequestToken = $conditions['translation_request_token'];
		}
		return $translationRequestToken;
	}
	
}