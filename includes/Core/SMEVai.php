<?php

namespace SMEVai\Core;

defined( 'ABSPATH' ) || exit;

use SMEVai\Core\Module;
use SMEVai\Platform\WooCommerce;

final class SMEVai {
	/**
	 * SMEVai version.
	 *
	 * @var string
	 */
	public $version = '1.0.2';

	/**
	 * The single instance of the class.
	 *
	 * @var SMEVai
	 * @since 2.0
	 */
	protected static $_instance = null;

	/**
	 * Main SMEVai Instance.
	 *
	 * Ensures only one instance of SMEVai is loaded or can be loaded.
	 *
	 * @since 2.0
	 * @static
	 * @see smevai()
	 * @return SMEVai - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'sme-accounting' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'sme-accounting' ), '1.0.0' );
	}

	/**
	 * SMEVai Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->define_constants();
		$this->set_locale();
		$this->init();
	}

	/**
	 * Define CONSTANTS
	 *
	 * @since 1.0.0
	 * @return void
	 */
    public function define_constants(){
        $this->define( 'SMEVAI_ABSPATH', dirname( SMEVAI_PLUGIN_FILE ) . '/' );
        $this->define( 'SMEVAI_ASSETS', plugin_dir_url( SMEVAI_PLUGIN_FILE ) . 'assets/' );
		$this->define( 'SMEVAI_PLUGIN_BASENAME', plugin_basename( SMEVAI_PLUGIN_FILE ) );
		$this->define( 'SMEVAI_VERSION', $this->version );
		$this->define( 'SMEVAI_API_ROOT', 'https://api.smevai.com/wp/v1/' );
    }

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Setting the locale for translation availability
	 * @since 1.0.0
	 * @return void
	 */
	public function set_locale(){
		add_action( 'init', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Loading Text Domain on init HOOK
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain(){
		load_plugin_textdomain( 'sme-accounting', false, dirname( SMEVAI_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Initialize SMEVai
	 * @since 1.0.0
	 * @return void
	 */
    public function init(){
        register_activation_hook(SMEVAI_PLUGIN_FILE, [$this, 'plugin_activator']);
        add_filter( 'http_request_args', function( $args ) {
            $args['reject_unsafe_urls'] = false;
            return $args;
        });
		// Initialize All Platform Here.
		WooCommerce::get_instance();

		$module = Module::get_instance();

		if( is_admin() ) {
			if( method_exists( $module->active()->module, 'admin' ) ) {
				$module->active()->module->admin();
			}
			Admin::get_instance();
		}

		// Invoke Active Platform
		$module->active()->module->init();
    }

	/**
	 * Invoked on plugin activation.
	 *
	 * @param boolean $network_wide
	 * @since 1.0.0
	 * @return void
	 */
    public function plugin_activator( $network_wide ) {
        if ( is_multisite() && $network_wide ) {
			return;
        }
        set_transient( 'smevai_activation_redirect', true, MINUTE_IN_SECONDS );
    }
}