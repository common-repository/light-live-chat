<?php

namespace LightSource\StdResponse;

/**
 * Class STD_RESPONSE
 * @package LightSource\StdResponse
 */
abstract class STD_RESPONSE {


	//////// constants


	const IS_SUCCESS = 'isSuccess';
	const ARGS = 'args';
	const E_MSGS = 'errorMsgs';


	//////// static methods


	/**
	 * @param array $args
	 * @param bool $isSuccess
	 * @param array $errorMsgs
	 *
	 * @return array
	 */
	final public static function Create( $args = [], $isSuccess = false, $errorMsgs = [] ) {

		// remove args for not success response

		if ( ! $isSuccess ) {
			$args = [];
		}

		return [
			self::IS_SUCCESS => $isSuccess,
			self::ARGS       => $args,
			self::E_MSGS     => $errorMsgs,
		];
	}

}