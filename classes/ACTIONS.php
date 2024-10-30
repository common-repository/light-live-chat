<?php

namespace LightLiveChat;

defined( 'ABSPATH' ) ||
die( 'Constant missing' );

/**
 * Class ACTIONS
 * @package LightLiveChat
 */
abstract class ACTIONS {

	/**
	 * @return void
	 */
	public static function SetHooks() {


		$plugin     = Plugin::Instance();
		$settings   = Settings::Instance();
		$pluginName = $plugin->getName();

		//// actions

		add_action( 'admin_enqueue_scripts', [ $plugin, 'stylesAndScripts' ] );
		add_action( 'admin_menu', [ $settings, 'adminMenu' ] );
		add_action( 'admin_init', [ $settings, 'registerSettings' ] );
		add_action( 'plugins_loaded', [ $plugin, 'loadLanguages' ] );
		add_action( 'wp_head', [ $plugin, 'wpHead' ] );

		//// filters

		add_filter( "plugin_action_links_{$pluginName}", [ $settings, 'actionLinks' ] );

	}

	/**
	 * Never called, just for a parser
	 *
	 * @return void
	 */
	public static function MultilingualStub() {

		// plugin title and description

		__( 'Light live chat', 'light-live-chat' );
		__( 'Providing an easy way for a live chat widget integration. Multilingual. For LightChat.org customers (has a free tariff)', 'light-live-chat' );

		// settings dynamic tabs

		__( 'General', 'light-live-chat' );

	}

}