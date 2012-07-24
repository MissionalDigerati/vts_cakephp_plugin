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
 * A model for handling the translation Requests
 *
 * @package Video Translator Service CakePHP Plugin
 * @author Johnathan Pulos
 */
class TranslationRequest extends VideoTranslatorServiceAppModel {
	/**
	 * We want to use the Translation Request DataSource
	 *
	 * @var string
	 * @access public
	 */
	public $useDbConfig = 'vtsTranslationRequest';
	
	/**
	 * This model is using an external API, so set the table to false
	 *
	 * @var boolean
	 * @access public
	 */
	public $useTable = false;
	
	/**
	 * Define the schema of the Model
	 *
	 * @var array
	 * @access protected
	 */
	protected $_schema = array(	'id' => 				array(
																											'type' => 'integer', 
																											'null' => false, 
																											'key' => 'primary',
																											'length' => 12
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

}