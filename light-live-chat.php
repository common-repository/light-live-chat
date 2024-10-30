<?php
/*
Plugin Name: Light live chat
Description: Providing an easy way for a live chat widget integration. Multilingual. For LightChat.org customers (has a free tariff)
Version: 1.0.5
Author: LightChat.org
Author URI: https://lightchat.org/en/
License: GPLv2 or later
Text Domain: light-live-chat
Domain Path: /lang
*/

namespace LightLiveChat;

defined( 'ABSPATH' ) ||
die( 'Constant missing' );

include_once __DIR__ . '/vendors/vendor/autoload.php';

$plugin = Plugin::Instance();
$plugin->setName( plugin_basename( __FILE__ ) );
$plugin->setRootUrl( plugin_dir_url( __FILE__ ) );
$plugin->setRelativeRootPath( dirname( plugin_basename( __FILE__ ) ) );

ACTIONS::SetHooks();
