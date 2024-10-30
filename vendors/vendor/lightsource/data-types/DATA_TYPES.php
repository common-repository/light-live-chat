<?php

namespace LightSource\DataTypes;

use LightSource\StdResponse\STD_RESPONSE;

/**
 * Class DATA_TYPES
 * @package LightSource\DataTypes
 */
abstract class DATA_TYPES {


	//////// constants


	const INT = 'int';
	const FLOAT = 'float';
	const BOOL = 'bool';
	const STRING = 'string';

	const _WHITE__LIST = '_white_list'; // allowed values (i.e. compare wholly)
	const _MIN = '_min'; // (compare <) min value for NUMBERS || min length for STRING
	const _MAX = '_max'; // (compare >) max value for NUMBERS || max length for STRING

	// only for strings
	const _CHARSET = '_charset';
	const _STRIP_TAGS = '_strip_tags';
	const _HTML_ENTITIES = '_html_entities';
	const _RM__MULTIPLE_SPACE = '_rm_multiple_space';
	const _WHITE__SYMBOLS = '_white_symbols'; // allowed symbols (i.e. compare parts)
	const _PCRE = '_pcre';

	const _ARG__VALUE = 'value';


	//////// static methods


	/**
	 * @param string $typeConst
	 * @param mixed $value
	 * @param array $args
	 * @param bool $isSoftMode Default FALSE. TRUE disable some default restrictions - but remember for default un-security! (Can be used in special cases, ex. for verified source or custom settings)
	 *
	 * @return array StdResponse : args = [self::_ARG__VALUE => x] || errorMsgs = [x, x...]
	 */
	final public static function Clear( $typeConst, $value, $args = [], $isSoftMode = false ) {


		//// 1. defaults


		$defaults = [
			self::_WHITE__LIST        => [],
			self::_MIN                => ( self::STRING !== $typeConst ) ?
				null :
				1,
			self::_MAX                => ( self::STRING !== $typeConst ) ?
				null :
				5000,
			// only for strings
			self::_STRIP_TAGS         => ! $isSoftMode,
			self::_HTML_ENTITIES      => ! $isSoftMode,
			self::_RM__MULTIPLE_SPACE => ! $isSoftMode,
			self::_WHITE__SYMBOLS     => [],
			self::_PCRE               => '',
			self::_CHARSET            => 'UTF-8',
		];


		$args = array_merge( $defaults, $args );

		$isString   = self::STRING === $typeConst;
		$violations = [];


		//// 2. try cast to type


		switch ( $typeConst ) {
			case self::INT:

				if ( ! is_numeric( $value ) ) {
					$violations[] = self::_ARG__VALUE;
					break;
				}

				$value = intval( $value );

				break;
			case self::FLOAT:

				if ( ! is_numeric( $value ) ) {
					$violations[] = self::_ARG__VALUE;
					break;
				}

				// replace comma to dot (if exists), for correct work with floatval function

				$value = strval( $value );
				$value = trim( $value );
				$value = str_replace( ',', '.', $value );
				$value = floatval( $value );

				break;
			case  self::BOOL:

				if ( ! is_bool( $value ) &&
				     ! is_numeric( $value ) &&
				     ! is_string( $value ) ) {
					$violations[] = self::_ARG__VALUE;
					break;
				}

				// 'on' to support php forms

				$value = in_array( $value, [ true, 1, 'true', '1', 'on', ], true );

				break;
			case  self::STRING:

				if ( ! is_string( $value ) &&
				     ! is_numeric( $value ) ) {
					$violations[] = self::_ARG__VALUE;
					break;
				}

				$value = strval( $value );
				$value = trim( $value );

				break;
			default:
				$violations[] = self::_ARG__VALUE;
				break;
		}

		// stop clear if cast to type is failed

		if ( $violations ) {
			return STD_RESPONSE::Create( [], false, $violations );
		}


		//// 3. string restrictions


		if ( $isString ) {

			// convert to utf-8, all not converted symbols replaced with '?'
			$value = mb_convert_encoding( $value, $args[ self::_CHARSET ] );

			// decode string to correct next work (strip_tags, etc...)
			// BUT ONLY if enabled htmlentities in next step, else it maybe un-secure
			$value = $args[ self::_HTML_ENTITIES ] ?
				html_entity_decode( $value, ENT_QUOTES, $args[ self::_CHARSET ] ) :
				$value;

			$value = $args[ self::_STRIP_TAGS ] ?
				strip_tags( $value ) :
				$value;

			if ( $args[ self::_RM__MULTIPLE_SPACE ] ) {

				// after preg_replace can be null (if string contains bad symbols)

				// remove all \r

				$value = preg_replace( '/\r/', '', $value );
				$value = is_null( $value ) ?
					'' :
					$value;

				// replace all \t to single space

				$value = preg_replace( '/\t/', ' ', $value );
				$value = is_null( $value ) ?
					'' :
					$value;

				// replace multiple \n to one

				$value = preg_replace( '/\n+/', "\n", $value );
				$value = is_null( $value ) ?
					'' :
					$value;

				// replace multiple space to one

				$value = preg_replace( '/ +/', ' ', $value );
				$value = is_null( $value ) ?
					'' :
					$value;

			}

			$whiteSymbols = $args[ self::_WHITE__SYMBOLS ];
			if ( $whiteSymbols ) {

				$mbStrlen = mb_strlen( $value );
				for ( $i = 0; $i < $mbStrlen; $i ++ ) {

					$symbol = mb_substr( $value, $i, 1, $args[ self::_CHARSET ] );
					if ( in_array( $symbol, $whiteSymbols, true ) ) {
						continue;
					}

					$violations[] = self::_WHITE__SYMBOLS;
					break;

				}

			}

			$pcre = $args[ self::_PCRE ];
			if ( $pcre &&
			     1 !== preg_match( $pcre, $value ) ) {
				$violations[] = self::_PCRE;
			}

			if ( $args[ self::_HTML_ENTITIES ] ) {
				// convert all possible characters to exist HTML entities (without double encoding to prevent corrupt data if exist multiple calling this function)
				// also return empty string if have bad utf characters
				$value = htmlentities( $value, ENT_QUOTES, $args[ self::_CHARSET ], false );
			}

		}


		//// 4. common restrictions


		$min = $args[ self::_MIN ];
		if ( ! is_null( $min ) &&
		     ( ( $isString && mb_strlen( $value, $args[ self::_CHARSET ] ) < $min ) ||
		       ( ! $isString && $value < $min ) ) ) {
			$violations[] = self::_MIN;
		}
		$max = $args[ self::_MAX ];
		if ( ! is_null( $max ) &&
		     ( ( $isString && mb_strlen( $value, $args[ self::_CHARSET ] ) > $max ) ||
		       ( ! $isString && $value > $max ) ) ) {
			$violations[] = self::_MAX;
		}

		$whiteList = $args[ self::_WHITE__LIST ];
		if ( $whiteList &&
		     ! in_array( $value, $whiteList, true ) ) {
			$violations[] = self::_WHITE__LIST;
		}


		//// 5. response


		return ( ! $violations ?
			STD_RESPONSE::Create( [ self::_ARG__VALUE => $value ], true ) :
			STD_RESPONSE::Create( [], false, $violations ) );
	}

	/**
	 * Default value for typeConst
	 *
	 * @param string $typeConst
	 *
	 * @return mixed
	 */
	final public static function GetDefaultValue( $typeConst ) {

		$defaultValue = null;

		switch ( $typeConst ) {
			case self::INT:
			case self::FLOAT:
				$defaultValue = 0;
				break;
			case  self::BOOL:
				$defaultValue = false;
				break;
			case  self::STRING:
				$defaultValue = '';
				break;
		}

		return $defaultValue;
	}

}