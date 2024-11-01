<?php
/**
 * Plugin Name: SME Accounting
 * Plugin URI:  https://smevai.com/sme-hishab
 * Description: One Stop Business & Accounting Solution For SMEs From Anywhere Around The World
 * Version:     1.0.2
 * Author:      SMEVai
 * Author URI:  https://smevai.com
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: sme-accounting
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SMEVAI_PLUGIN_FILE' ) ) {
	define( 'SMEVAI_PLUGIN_FILE', __FILE__ );
}

if( ! class_exists('\SMEVai\Core\SMEVai') ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if( ! function_exists('smevai_accounting') ) {
	function smevai_accounting(){
		return \SMEVai\Core\SMEVai::instance();
	}
}

$GLOBALS['smevai_accounting'] = smevai_accounting();