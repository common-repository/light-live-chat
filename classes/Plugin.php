<?php

namespace LightLiveChat;

defined( 'ABSPATH' ) ||
die( 'Constant missing' );

use Exception;
use DateTime;

/**
 * Class Plugin
 * @package LightLiveChat
 */
final class Plugin {


	//////// constants


	const FOLDER_ASSETS = 'assets';
	const FOLDER_LANG = 'lang';


	//////// static fields


	/**
	 * @var self|null
	 */
	private static $_Instance = null;


	////////  fields

	/**
	 * @var array
	 */
	private $_adminHooks;
	/**
	 * @var string
	 */
	private $_name;
	/**
	 * @var string
	 */
	private $_rootUrl;
	/**
	 * @var string
	 */
	private $_relativeRootPath;


	//////// constructor


	/**
	 * Plugin constructor.
	 */
	private function __construct() {

		$this->_adminHooks       = [];
		$this->_name             = '';
		$this->_rootUrl          = '';
		$this->_relativeRootPath = '';

	}

	/**
	 * @return self
	 */
	public static function Instance() {
		if ( is_null( self::$_Instance ) ) {
			self::$_Instance = new self();
		}

		return self::$_Instance;
	}


	//////// static methods


	/**
	 * Return url base on relative $sourcePath
	 *
	 * @param string $sourcePath
	 * @param bool $isPreventCache
	 * @param bool $isAbs If false return url without domain
	 *
	 * @return string
	 */
	public function url( $sourcePath, $isPreventCache, $isAbs = true ) {

		$urlParts = [];

		array_push( $urlParts, $this->_rootUrl, self::FOLDER_ASSETS );

		if ( $isPreventCache ) {

			$timestamp = 0;

			try {
				$dateTime  = new DateTime();
				$timestamp = $dateTime->getTimestamp();
			} catch ( Exception $ex ) {

			}

			$sourcePath .= '?r=' . $timestamp;

		}

		$urlParts[] = $sourcePath;

		$absUrl = implode( DIRECTORY_SEPARATOR, $urlParts );

		return $isAbs ?
			$absUrl :
			wp_make_link_relative( $absUrl );
	}



	//////// setters


	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public function setName( $name ) {

		if ( $this->_name ) {
			return;
		}

		$this->_name = $name;

	}

	/**
	 * @param string $rootUrl
	 *
	 * @return void
	 */
	public function setRootUrl( $rootUrl ) {

		if ( $this->_rootUrl ) {
			return;
		}

		$this->_rootUrl = untrailingslashit( $rootUrl );

	}

	/**
	 * @param string $relativeRootPath
	 *
	 * @return void
	 */
	public function setRelativeRootPath( $relativeRootPath ) {

		if ( $this->_relativeRootPath ) {
			return;
		}

		$this->_relativeRootPath = $relativeRootPath;

	}

	//////// getters


	/**
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}


	//////// methods

	//// hooks

	/**
	 * @param string $hookSuffix
	 *
	 * @return void
	 */
	public function stylesAndScripts( $hookSuffix ) {


		if ( ! in_array( $hookSuffix, $this->_adminHooks, true ) ) {
			return;
		}

		wp_enqueue_style( __NAMESPACE__, self::Url( 'pages/settings/settings.min.css', false ), [], null, 'all' );
		wp_enqueue_script( __NAMESPACE__, self::Url( 'pages/settings/settings.min.js', false ), [], null, true );

	}

	/**
	 * @return void
	 */
	public function loadLanguages() {

		load_plugin_textdomain( 'light-live-chat', false, implode( DIRECTORY_SEPARATOR, [
			$this->_relativeRootPath,
			self::FOLDER_LANG,
		] ) );

	}

	/**
	 * @return void
	 */
	public function wpHead() {

		$settings = Settings::Instance();

		if ( ! $settings->getOption( Settings::FIELD_GENERAL_IS_ENABLED ) ) {
			return;
		}

		$postId = get_queried_object_id();

		$post = get_post( $postId );

		if ( is_null( $post ) ||
		     'page' !== $post->post_type ||
		     'publish' !== $post->post_status ) {
			return;
		}


		$relatedPages = trim( $settings->getOption( Settings::FIELD_GENERAL_RELATED_PAGES ) );
		$relatedPages = $relatedPages ?
			explode( ',', $relatedPages ) :
			[];

		$ignorePages = trim( $settings->getOption( Settings::FIELD_GENERAL_IGNORE_PAGES ) );
		$ignorePages = $ignorePages ?
			explode( ',', $ignorePages ) :
			[];

		// compare without strict

		$isTargetPage = ( ! $relatedPages ||
		                  in_array( $postId, $relatedPages ) );
		$isIgnorePage = in_array( $postId, $ignorePages );

		if ( ! $isTargetPage ||
		     $isIgnorePage ) {
			return;
		}


		echo html_entity_decode( $settings->getOption( Settings::FIELD_GENERAL_CODE ), ENT_QUOTES );

	}

	////

	/**
	 * @param string $adminHook
	 *
	 * @return void
	 */
	public function addAdminHook( $adminHook ) {
		$this->_adminHooks[] = $adminHook;
	}

}
