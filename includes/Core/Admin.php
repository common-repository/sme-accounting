<?php

namespace SMEVai\Core;
use SMEVai\Core\Base;

class Admin extends Base {
	/**
	 * Settings Data
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $settings = [];

	/**
	 * Admin Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// add_action('admin_menu', [$this, 'menu']);
		add_action('admin_init', [$this, 'maybe_redirect']);
	}

	/**
	 * Menu List
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function menu_list(  ) {
		$menu_items = [
			'smevai' => [
				'page_title' => 'SMEVai',
				'menu_title' => 'SMEVai',
				'capability' => 'manage_options',
				'callback' => [ $this, 'smevai' ],
				'icon' => $this->get_assets('menu-icon.png', 'images'),
				'position' => 59
			]
		];
		return apply_filters('smevai_menu_items', $menu_items);
	}

	/**
	 * Initialize Menu List
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function menu() {
		foreach( $this->menu_list() as $slug => $menu ) {
			add_menu_page(
				$menu['page_title'],
				$menu['menu_title'],
				$menu['capability'],
				$slug,
				$menu['callback'],
				! empty( $menu['icon'] ) ? $menu['icon'] : '',
				! empty( $menu['position'] ) ? $menu['position'] : ''
			);
		}
	}

	/**
	 * Settings Page Submit Processor.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function settings_submit(){
		if ( ! isset( $_POST['smevai_nonce'] ) || ! wp_verify_nonce( $_POST['smevai_nonce'], 'smevai_settings' ) ) {
			$nonce_error = __( 'Invalid Nonce. Please refresh and try again.', 'sme-accounting' );
			return;
		}

		$settings = [];
		if( isset( $_POST['smevai_secret_key'] ) ) {
			$settings['smevai_secret_key'] = sanitize_text_field($_POST['smevai_secret_key']);
			if( ! empty( $settings['smevai_secret_key'] ) ) {
                $this->settings = $settings;
                update_option('smevai_settings', $settings, 'no');
			}
		}

		do_action('smevai::save_settings', $this->settings);
	}

	/**
	 * Dashboard View Callback
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function smevai(){
		do_action('smevai::before_settings');

        $this->settings = get_option('smevai_settings', []);

		$this->settings_submit();
		$smevai_secret_key = ! empty( $this->settings['smevai_secret_key'] ) ? $this->settings['smevai_secret_key'] : '';

        include_once SMEVAI_ABSPATH . 'views/admin.php';
    }

	/**
	 * This method is responsible for determining the action for redirect.
	 *
	 * @return void
	 */
	public function maybe_redirect(){
        if ( ! get_transient( 'smevai_activation_redirect' ) ) {
			return;
		}
		if ( wp_doing_ajax() ) {
			return;
		}

		delete_transient( 'smevai_activation_redirect' );
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
        }
        // Safe Redirect to SMEVai Admin Page
        // wp_safe_redirect( admin_url( 'admin.php?page=sme-accounting' ) );
        wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=sme-accounting' ) );
        exit;
	}
}