<?php

namespace SMEVai\Core;

abstract class Base {
	/**
	 * Holds the plugin instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @var Base
	 */
	private static $instances = [];

	/**
	 * Disable class cloning and throw an error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object. Therefore, we don't want the object to be cloned.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'sme-accounting' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'sme-accounting' ), '1.0.0' );
	}

	/**
	 * Sets up a single instance of the plugin.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return static An instance of the class.
	 */
	public static function get_instance( ...$args ) {
		$module = get_called_class();
		if ( ! isset( self::$instances[ $module ] ) ) {
			self::$instances[ $module ] = new $module( ...$args );
		}

		return self::$instances[ $module ];
	}

	/**
	 * This method is responsible for getting assets url.
	 * i.g: image, css, js
	 *
	 * @param string $name
	 * @param string $type images,css,js
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function get_assets( $name, $type = 'css' ){
		$filepath = SMEVAI_ASSETS . "$type/" . $name;
		return $filepath;
	}
}