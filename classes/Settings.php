<?php

namespace LightLiveChat;

defined( 'ABSPATH' ) ||
die( 'Constant missing' );

use LightSource\DataTypes\DATA_TYPES;
use LightSource\StdResponse\STD_RESPONSE;

use WP_Query;

/**
 * Class Settings
 *
 * Singleton class
 *
 * @package LightLiveChat
 */
final class Settings {


	//////// constants


	const CAPABILITY = 'manage_options';

	// name $_GET param with current tab
	const _GET_TAB = 'tab';
	const _TAB = 'field_info_tab';
	const _TITLE = 'field_info_title';
	const _TYPE = 'field_info_type';
	const _DEFAULT = 'field_info_default';
	const _CSS = 'field_info_css';

	const TAB_GENERAL = 'general';
	const TAB_DEFAULT = self::TAB_GENERAL;

	const FIELD_CURRENT_TAB = '_current_tab';
	const FIELD_GENERAL_IS_ENABLED = 'general_is_enabled';
	const FIELD_GENERAL_CODE = 'general_code';
	const FIELD_GENERAL_RELATED_PAGES = 'general_related_pages';
	const FIELD_GENERAL_IGNORE_PAGES = 'general_ignore_pages';

	const BUTTON_NAME_RESET = 'reset_settings';


	//////// static fields


	/**
	 * @var self|null
	 */
	private static $_Instance = null;


	//////// fields


	/**
	 * @var string
	 */
	private $_optionName;
	/**
	 * @var string
	 */
	private $_optionGroup;
	/**
	 * @var string Menu or page slug
	 */
	private $_slug;

	/**
	 * @var string
	 */
	private $_settingsInfo;
	/**
	 * @var array
	 */
	private $_settings;
	/**
	 * @var string
	 */
	private $_tabCurrent;

	/**
	 * @var bool
	 */
	private $_isExistUpdatedMessage;


	//////// construct


	/**
	 * Settings constructor.
	 */
	private function __construct() {

		$this->_optionName = $this->_optionGroup = $this->_slug = 'light-live-chat';
		$this->_tabCurrent = self::TAB_DEFAULT;

		// [ tabKey => [ fieldKey => [fieldType, fieldDefault, fieldCss] ] ]
		// fieldCss is option

		$this->_settingsInfo = [
			self::TAB_GENERAL => [
				self::FIELD_GENERAL_IS_ENABLED    => [ DATA_TYPES::BOOL, false, ],
				self::FIELD_GENERAL_CODE          => [ DATA_TYPES::STRING, '', 'width:300px;' ],
				self::FIELD_GENERAL_RELATED_PAGES => [ DATA_TYPES::STRING, '', 'width:300px;height:150px;' ],
				self::FIELD_GENERAL_IGNORE_PAGES  => [ DATA_TYPES::STRING, '', 'width:300px;height:150px;' ],
			],
		];

		// get current settings or defaults if option does not exist
		// use explicit defaults because admin_init not fired && defaults in register_setting is not setting

		$this->_settings = get_option( $this->_optionName, $this->_getDefaults() );

		// for prevent twice display message updated in case if option not exist in db (called in update/called in add)

		$this->_isExistUpdatedMessage = false;

		$this->_setCurrentTab();

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


	//////// setters

	/**
	 * @return void
	 */
	private function _setCurrentTab() {

		if ( ! key_exists( self::_GET_TAB, $_GET ) ) {
			return;
		}

		$clearStdResponse = DATA_TYPES::Clear( DATA_TYPES::STRING, $_GET[ self::_GET_TAB ], [
			DATA_TYPES::_WHITE__LIST => $this->_getTabsInfo( true ),
		] );
		if ( $clearStdResponse[ STD_RESPONSE::IS_SUCCESS ] ) {
			$this->_tabCurrent = $clearStdResponse[ STD_RESPONSE::ARGS ][ DATA_TYPES::_ARG__VALUE ];
		}

	}


	//////// getters


	/**
	 * @param string $fieldKey
	 *
	 * @return string
	 */
	private function _getFieldTitle( $fieldKey ) {

		$fieldTitle = '';

		switch ( $fieldKey ) {
			case self::FIELD_GENERAL_IS_ENABLED:
				$fieldTitle = __( 'Is enabled', 'light-live-chat' );
				break;
			case self::FIELD_GENERAL_CODE:
				$fieldTitle = __( 'Widget code', 'light-live-chat' );
				break;
			case self::FIELD_GENERAL_RELATED_PAGES:
				$fieldTitle = __( 'Related pages', 'light-live-chat' );
				break;
			case self::FIELD_GENERAL_IGNORE_PAGES:
				$fieldTitle = __( 'Excluded pages', 'light-live-chat' );
				break;
		}

		return $fieldTitle;
	}

	/**
	 * @return array [ fieldKey => [ self::FIELD_INFO__* => '' ] ]
	 */
	private function _getFieldsInfo() {

		$fieldsInfo = [];

		foreach ( $this->_settingsInfo as $tabKey => $tabFields ) {
			foreach ( $tabFields as $fieldKey => $fieldArgs ) {
				$fieldCss                = 3 === count( $fieldArgs ) ? $fieldArgs[2] : '';
				$fieldsInfo[ $fieldKey ] = [
					self::_TAB     => $tabKey,
					self::_TITLE   => $this->_getFieldTitle( $fieldKey ),
					self::_TYPE    => $fieldArgs[0],
					self::_DEFAULT => $fieldArgs[1],
					self::_CSS     => $fieldCss,
				];
			}
		}

		return $fieldsInfo;
	}

	/**
	 * @return array [ fieldKey => fieldDefault ]
	 */
	private function _getDefaults() {
		$defaults   = [];
		$fieldsInfo = $this->_getFieldsInfo();
		foreach ( $fieldsInfo as $fieldKey => $fieldInfo ) {
			$defaults[ $fieldKey ] = $fieldInfo[ self::_DEFAULT ];
		}

		return $defaults;
	}

	/**
	 * @param bool $onlyKeys
	 *
	 * @return array [ tabKey => tabName ] OR [ tabKey ] depending on $onlyKeys
	 */
	private function _getTabsInfo( $onlyKeys ) {
		$tabsInfo = [];

		$listTabs = array_keys( $this->_settingsInfo );

		if ( $onlyKeys ) {
			$tabsInfo = $listTabs;
		} else {
			foreach ( $listTabs as $tab ) {
				// first symbol to uppercase
				$tabsInfo[ $tab ] = ucfirst( $tab );
			}
		}


		return $tabsInfo;
	}

	/**
	 * @return string
	 */
	private function _getNavigationBlock() {

		$tabsInfo = $this->_getTabsInfo( false );
		$pageUrl  = menu_page_url( $this->_slug, false );

		$items = [];

		foreach ( $tabsInfo as $tabKey => $tabName ) {
			$items[] = [
				'class' => ( $tabKey === $this->_tabCurrent ?
					'current' :
					'' ),
				'href'  => ( self::TAB_DEFAULT !== $tabKey ?
					add_query_arg( array( self::_GET_TAB => $tabKey, ), $pageUrl ) :
					$pageUrl ),
				'name'  => __( $tabName, 'light-live-chat' ),
			];
		}

		return Html::Instance()->render( 'settings-navigation', [
			'items' => $items,
			'class' => 'settings__navigation',
		] );

	}

	/**
	 * @return string
	 */
	private function _getFormBlock() {

		$html = '';

		// hidden input with current tab name
		$name = $this->_optionName . '[' . self::FIELD_CURRENT_TAB . ']';
		$html .= "<input type='hidden' name='{$name}' value='{$this->_tabCurrent}'>";

		ob_start();

		// wp hidden fields (security...)

		settings_fields( $this->_optionGroup );

		// print all fields

		do_settings_sections( $this->_slug );

		// buttons

		$text = __( 'Are you sure you want to restore the default settings?', 'light-live-chat' );

		submit_button( __( 'Save', 'light-live-chat' ), 'primary custom settings-form__button settings-form__button--type--save' );
		submit_button( __( 'Restore All Defaults', 'light-live-chat' ), 'delete custom settings-form__button settings-form__button--type--restore', self::BUTTON_NAME_RESET, true, 'onclick="return confirm(\'' . $text . '\')"' );

		$html .= ob_get_clean();


		return Html::Instance()->render( 'settings-form', [
			'action' => admin_url( 'options.php' ),
			'fields' => $html,
			'class'  => 'settings__form',
		] );

	}

	/**
	 * @return string
	 */
	private function _getHeaderBlock() {

		return Html::Instance()->render( 'settings-header', [
			'class' => 'settings__header updated js-update-details',
			'text'  => [
				'title'       => __( 'Light live chat', 'light-live-chat' ),
				'description' => __( 'This plugin provide easy integration for a live chat widget', 'light-live-chat' ),
				'signUp'      => __( 'sign up', 'light-live-chat' ),
				'notice'      => __( 'For using the plugin you should have an account, if you still don\'t have - ', 'light-live-chat' ),
				'mainUrl'     => __( 'https://lightchat.org/en/', 'light-live-chat' ),
				'signUpUrl'   => __( 'https://lightchat.org/en/sign-up/', 'light-live-chat' ),
			],
		] );
	}

	/**
	 * @return array [ id => title ]
	 */
	private function _getAllPages() {

		$allPages = [];

		$pages = new WP_Query( [
			'post_type'        => 'page',
			'post_status'      => 'publish',
			// unlimited
			'posts_per_page'   => - 1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			// to show pages with all languages
			'suppress_filters' => true,
		] );
		$pages = $pages->get_posts();

		foreach ( $pages as $page ) {
			$allPages[ $page->ID ] = get_the_title( $page );
		}

		return $allPages;
	}

	/**
	 * @param $name
	 *
	 * @return string
	 */
	public function getOption( $name ) {
		return key_exists( $name, $this->_settings ) ? $this->_settings[ $name ] : '';
	}


	//////// methods


	/**
	 * @param string $fieldKey
	 * @param string $id
	 * @param string $name
	 * @param string $style
	 * @param string $value
	 *
	 * @return bool
	 */
	private function _printCustomInput( $fieldKey, $id, $name, $style, $value ) {

		$isPrinted = false;

		switch ( $fieldKey ) {
			case self::FIELD_GENERAL_RELATED_PAGES:

				$isPrinted           = true;
				$allPages            = $this->_getAllPages();
				$currentRelatedPages = explode( ',', $this->getOption( self::FIELD_GENERAL_RELATED_PAGES ) );

				echo "<select id='{$id}' name='{$name}[]'  style='{$style}' multiple>";
				foreach ( $allPages as $pageId => $pageTitle ) {

					$selected = '';

					// compare without strict
					if ( in_array( $pageId, $currentRelatedPages ) ) {
						$selected = 'selected';
					}

					echo "<option value='{$pageId}' {$selected}>{$pageTitle}</option>";

				}
				echo "</select>";
				$text = __( 'If you want to display a widget on all pages including new - just don\'t setup target pages', 'light-live-chat' );
				echo '<p style="width: 300px;">' . $text . '</p>';

				break;
			case self::FIELD_GENERAL_IGNORE_PAGES:

				$isPrinted          = true;
				$allPages           = $this->_getAllPages();
				$currentIgnorePages = explode( ',', $this->getOption( self::FIELD_GENERAL_IGNORE_PAGES ) );

				echo "<select id='{$id}' name='{$name}[]'  style='{$style}' multiple>";
				foreach ( $allPages as $pageId => $pageTitle ) {


					$selected = '';

					// compare without strict
					if ( in_array( $pageId, $currentIgnorePages ) ) {
						$selected = 'selected';
					}

					echo "<option value='{$pageId}' {$selected}>{$pageTitle}</option>";

				}
				echo "</select>";

				break;
		}

		return $isPrinted;
	}

	/**
	 * @param string $fieldKey
	 * @param string $type
	 * @param string $value
	 *
	 * @return array
	 */
	private function _cleanInput( $fieldKey, $type, $value ) {

		$stdResponse = [];

		switch ( $fieldKey ) {
			case self::FIELD_GENERAL_CODE:

				// contain's <script>, so just only htmlEntities

				$stdResponse = DATA_TYPES::Clear( $type, $value, [
					DATA_TYPES::_MAX           => 1000,
					DATA_TYPES::_MIN           => null,
					DATA_TYPES::_HTML_ENTITIES => true,
				], true );

				break;
			default:

				// unset min, because all fields is optional (so, strings can be empty)

				$stdResponse = DATA_TYPES::Clear( $type, $value, [
					DATA_TYPES::_MAX => 1000,
					DATA_TYPES::_MIN => null,
				] );

				break;
		}

		if ( in_array( $fieldKey, [ self::FIELD_GENERAL_RELATED_PAGES, self::FIELD_GENERAL_IGNORE_PAGES, ], true ) &&
		     $stdResponse[ STD_RESPONSE::IS_SUCCESS ] ) {

			$allPages   = $this->_getAllPages();
			$value      = explode( ',', $stdResponse[ STD_RESPONSE::ARGS ][ DATA_TYPES::_ARG__VALUE ] );
			$cleanValue = [];

			foreach ( $value as $pageId ) {

				if ( ! key_exists( $pageId, $allPages ) ) {
					continue;
				}

				$cleanValue[] = $pageId;

			}

			$stdResponse[ STD_RESPONSE::ARGS ][ DATA_TYPES::_ARG__VALUE ] = implode( ',', $cleanValue );

		}

		return $stdResponse;
	}

	//// hooks

	/**
	 * @return void
	 */
	public function adminMenu() {

		$title = __( 'LightChat widget', 'light-live-chat' );
		$slug  = $this->_slug;

		$hookSuffix = add_options_page( $title, $title, self::CAPABILITY, $slug, [
			$this,
			'render',
		] );
		Plugin::Instance()->addAdminHook( $hookSuffix );

	}

	/**
	 * Add a settings link to a plugin page
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	public function actionLinks( $actions ) {

		$slug         = $this->_slug;
		$href         = get_admin_url( null, "options-general.php?page={$slug}" );
		$textSettings = __( 'Settings', 'light-live-chat' );
		$link         = "<a target='_self' href='{$href}'>{$textSettings}</a>";
		array_unshift( $actions, $link );

		return $actions;
	}

	/**
	 * @return void
	 */
	public function registerSettings() {

		$args = [
			'type'              => 'string',
			'description'       => '',
			'sanitize_callback' => [ $this, 'sanitizeCallback' ],
			'show_in_rest'      => false,
			//  set defaults if option does not exist for get_option, but only after admin_init action!
			// so if before doest not have defaults
			'default'           => $this->_getDefaults(),
		];

		register_setting( $this->_optionGroup, $this->_optionName, $args );

		// add sections (tab = section)
		$tabKeys = $this->_getTabsInfo( true );
		foreach ( $tabKeys as $tabKey ) {
			add_settings_section( $tabKey, '', '__return_false', $this->_slug );
		}

		// add fields
		$fieldsInfo = $this->_getFieldsInfo();
		foreach ( $fieldsInfo as $fieldKey => $fieldInfo ) {

			$section = $fieldInfo[ self::_TAB ];
			// hide tr with labels if tab is not active
			$sectionClass = ( $this->_tabCurrent === $section ) ? '' : 'hidden';

			add_settings_field( $fieldKey, $fieldInfo[ self::_TITLE ], [
				$this,
				'printInput'
			], $this->_slug, $section, [
				// 'class' arg passed by wp and add to parent tr
				'class'     => $sectionClass,
				// custom args
				'fieldKey'  => $fieldKey,
				'fieldInfo' => $fieldInfo,
			] );

		}

	}

	/**
	 * Can called twice - in case option update, but not exist in db (called in update/called in add)
	 * Read more for function register_setting()
	 *
	 * @param mixed $inputData
	 *
	 * @return array
	 */
	public function sanitizeCallback( $inputData ) {

		// for prevent twice display message updated in case if option not exist in db (called in update/called in add)

		if ( $this->_isExistUpdatedMessage ) {
			return $this->_settings;
		}

		if ( ! is_array( $inputData ) ||
		     ! key_exists( self::FIELD_CURRENT_TAB, $inputData ) ) {

			add_settings_error( $this->_optionName, 'not_correct_call', __( 'Settings doesn\'t updated - the form data is not correct', 'light-live-chat' ), 'error' );

			return $this->_settings;
		}

		if ( isset( $inputData[ self::FIELD_GENERAL_RELATED_PAGES ] ) &&
		     is_array( $inputData[ self::FIELD_GENERAL_RELATED_PAGES ] ) ) {
			$inputData[ self::FIELD_GENERAL_RELATED_PAGES ] = implode( ',', $inputData[ self::FIELD_GENERAL_RELATED_PAGES ] );
		} // so an user is un-select all, it's need save as an empty string
		else if ( ! isset( $inputData[ self::FIELD_GENERAL_RELATED_PAGES ] ) ) {
			$inputData[ self::FIELD_GENERAL_RELATED_PAGES ] = '';
		}

		if ( isset( $inputData[ self::FIELD_GENERAL_IGNORE_PAGES ] ) &&
		     is_array( $inputData[ self::FIELD_GENERAL_IGNORE_PAGES ] ) ) {
			$inputData[ self::FIELD_GENERAL_IGNORE_PAGES ] = implode( ',', $inputData[ self::FIELD_GENERAL_IGNORE_PAGES ] );
		} // so an user is un-select all, it's need save as an empty string
		else if ( ! isset( $inputData[ self::FIELD_GENERAL_IGNORE_PAGES ] ) ) {
			$inputData[ self::FIELD_GENERAL_IGNORE_PAGES ] = '';
		}

		$clearStdResponse = DATA_TYPES::Clear( DATA_TYPES::STRING, $inputData[ self::FIELD_CURRENT_TAB ], [
			DATA_TYPES::_WHITE__LIST => $this->_getTabsInfo( true ),
		] );

		if ( ! $clearStdResponse[ STD_RESPONSE::IS_SUCCESS ] ) {

			add_settings_error( $this->_optionName, 'not_correct_call', __( 'Settings doesn\'t updated - the form data is broken', 'light-live-chat' ), 'error' );

			return $this->_settings;
		}

		$formTab = $clearStdResponse[ STD_RESPONSE::ARGS ][ DATA_TYPES::_ARG__VALUE ];

		if ( key_exists( self::BUTTON_NAME_RESET, $_POST ) ) {

			$this->_settings = $this->_getDefaults();
			add_settings_error( $this->_optionName, 'success_restored', __( 'Settings success restored', 'light-live-chat' ), 'updated' );

			return $this->_settings;
		}

		$fieldsInfo = $this->_getFieldsInfo();

		foreach ( $fieldsInfo as $fieldKey => $fieldInfo ) {

			if ( $formTab !== $fieldInfo[ self::_TAB ] ) {
				continue;
			}

			if ( ! key_exists( $fieldKey, $inputData ) &&
			     DATA_TYPES::BOOL !== $fieldInfo[ self::_TYPE ] ) {
				continue;
			}

			$fieldValue = '';

			if ( DATA_TYPES::BOOL === $fieldInfo[ self::_TYPE ] ) {
				$fieldValue = key_exists( $fieldKey, $inputData );
			} else {

				$cleanResult = $this->_cleanInput( $fieldKey, $fieldInfo[ self::_TYPE ], $inputData[ $fieldKey ] );
				if ( ! $cleanResult[ STD_RESPONSE::IS_SUCCESS ] ) {

					add_settings_error( $this->_optionName, 'not_correct_call', __( 'Settings doesn\'t updated - one of the form fields is broken', 'light-live-chat' ), 'error' );

					return $this->_settings;
				}

				$fieldValue = $cleanResult[ STD_RESPONSE::ARGS ][ DATA_TYPES::_ARG__VALUE ];

			}

			$this->_settings[ $fieldKey ] = $fieldValue;

		}

		// for prevent twice display message updated in case if option not exist in db (called in update/called in add)

		$this->_isExistUpdatedMessage = true;
		add_settings_error( $this->_optionName, 'success_updated', __( 'Settings success updated', 'light-live-chat' ), 'updated' );


		return $this->_settings;
	}

	/**
	 * @param array $args
	 *
	 * @return void
	 */
	public function printInput( $args ) {

		if ( ! is_array( $args ) ||
		     ! key_exists( 'fieldKey', $args ) ||
		     ! key_exists( 'fieldInfo', $args )
		) {
			return;
		}

		$fieldKey  = $args['fieldKey'];
		$fieldInfo = $args['fieldInfo'];

		// print only current fields

		if ( $this->_tabCurrent !== $fieldInfo[ self::_TAB ] ) {
			return;
		}

		$fieldValue = $this->getOption( $fieldKey );

		$inputId    = esc_attr( $fieldKey );
		$inputName  = esc_attr( $this->_optionName . "[{$inputId}]" );
		$inputStyle = esc_attr( $fieldInfo[ self::_CSS ] );
		$inputValue = esc_attr( $fieldValue );

		if ( $this->_printCustomInput( $fieldKey, $inputId, $inputName, $inputStyle, $inputValue ) ) {
			return;
		}

		switch ( $fieldInfo[ self::_TYPE ] ) {
			case DATA_TYPES::STRING:
				echo "<input type='text' id='{$inputId}' name='{$inputName}' value='{$inputValue}' style='{$inputStyle}'/>";
				break;
			case DATA_TYPES::BOOL:
				$checked = $fieldValue ? 'checked' : '';
				echo "<input type='checkbox' id='{$inputId}' name='{$inputName}' style='{$inputStyle}' {$checked}/>";
				break;
		}

	}

	/**
	 * @return void
	 */
	public function render() {

		echo Html::Instance()->render( 'settings', [
			'header'     => $this->_getHeaderBlock(),
			'navigation' => $this->_getNavigationBlock(),
			'form'       => $this->_getFormBlock(),
		] );

	}

}
