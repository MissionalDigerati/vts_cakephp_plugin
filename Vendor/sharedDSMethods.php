<?php
/**
 * This file is part of curl Utility.
 * 
 * curl Utility is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * curl Utility is distributed in the hope that it will be useful,
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
 * A class of shared methods for the datasources
 *
 * @author Johnathan Pulos
 */
class sharedDSMethods {
	
	/**
	 * Get the Model.id based on the passed conditions
	 *
	 * @param Model $Model The Model Object
	 * @param array $conditions an array of conditions
	 * @return integer
	 * @access public
	 * @author Johnathan Pulos
	 */
	public function getModelId(Model $Model, $conditions) {
		if(isset($conditions[$Model->alias . ".id"])) {
			return $conditions[$Model->alias . ".id"];
		}else if(isset($conditions["id"])) {
			return $conditions["id"];
		}else if(isset($Model->id)) {
			return $Model->id;
		}else {
			throw new CakeException("API requires a " . $Model->alias . ".id.");
		}
	}
	
}
?>