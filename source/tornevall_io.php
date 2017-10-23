<?php

/**
 * Copyright 2017 Tomas Tornevall & Tornevall Networks
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package TorneLIB
 * @version 6.0.2
 */

namespace TorneLIB;

if ( ! class_exists( 'TorneLIB_IO' ) && ! class_exists( 'TorneLIB\TorneLIB_IO' ) ) {
	class TorneLIB_IO {

		public function __construct() {
		}

		/**
		 * Convert object to a data object (used for repairing __PHP_Incomplete_Class objects)
		 *
		 * This function are written to work with WSDL2PHPGenerator, where serialization of some objects sometimes generates, as described, __PHP_Incomplete_Class objects.
		 * The upgraded version are also supposed to work with protected values.
		 *
		 * @param array $objectArray
		 * @param bool $useJsonFunction
		 *
		 * @return object
		 * @since 6.0.0
		 */
		public function arrayObjectToStdClass( $objectArray = array(), $useJsonFunction = false ) {
			/**
			 * If json_decode and json_encode exists as function, do it the simple way.
			 * http://php.net/manual/en/function.json-encode.php
			 */
			if ( ( function_exists( 'json_decode' ) && function_exists( 'json_encode' ) ) || $useJsonFunction ) {
				return json_decode( json_encode( $objectArray ) );
			}
			$newArray = array();
			if ( is_array( $objectArray ) || is_object( $objectArray ) ) {
				foreach ( $objectArray as $itemKey => $itemValue ) {
					if ( is_array( $itemValue ) ) {
						$newArray[ $itemKey ] = (array) $this->arrayObjectToStdClass( $itemValue );
					} elseif ( is_object( $itemValue ) ) {
						$newArray[ $itemKey ] = (object) (array) $this->arrayObjectToStdClass( $itemValue );
					} else {
						$newArray[ $itemKey ] = $itemValue;
					}
				}
			}

			return $newArray;
		}

		/**
		 * Convert objects to arrays
		 *
		 * @param $arrObjData
		 * @param array $arrSkipIndices
		 *
		 * @return array
		 * @since 6.0.0
		 */
		public function objectsIntoArray( $arrObjData, $arrSkipIndices = array() ) {
			$arrData = array();
			// if input is object, convert into array
			if ( is_object( $arrObjData ) ) {
				$arrObjData = get_object_vars( $arrObjData );
			}
			if ( is_array( $arrObjData ) ) {
				foreach ( $arrObjData as $index => $value ) {
					if ( is_object( $value ) || is_array( $value ) ) {
						$value = $this->objectsIntoArray( $value, $arrSkipIndices ); // recursive call
					}
					if ( @in_array( $index, $arrSkipIndices ) ) {
						continue;
					}
					$arrData[ $index ] = $value;
				}
			}

			return $arrData;
		}
	}
}