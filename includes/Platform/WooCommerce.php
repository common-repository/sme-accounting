<?php

namespace SMEVai\Platform;

use function get_transient;
use function wp_json_encode;
use function wp_remote_post;
use function wp_remote_retrieve_response_code;
use function do_action;
use function update_option;
use function sanitize_text_field;

defined( 'ABSPATH' ) || exit;

class WooCommerce extends Platform {
    public $id = 'woocommerce';
    public $title = 'WooCommerce';
    public $settings = [];

    public function admin(){
        add_action( 'smevai::before_settings', [ $this, 'create_keys' ] );
        add_action( 'smevai::save_settings', [ $this, 'send_keys_to_smevai' ], 10, 1 );
    }

    /**
     * This function will be invoked if woocommerce is active as module.
     * By default woocommerce is active.
     *
     * @return void
     */
    public function init(){
        $this->settings = get_option('smevai_settings', []);
        add_action( 'woocommerce_webhook_http_args', [ $this, 'add_secret_key_to_header' ] );

        if( is_admin() ) {
            /**
             * Enqueueing Admin Scripts For WooCommerce
             */
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
            /**
             * WooCommerce Settings Tab for SMEVai.
             */
            add_filter( 'woocommerce_settings_tabs_array', [ $this, 'add_settings_tab' ], 50 );
            add_action( 'woocommerce_settings_sme-accounting', [ $this, 'settings_tab' ] );
            add_action( 'woocommerce_after_settings_sme-accounting', [ $this, 'settings_footer' ] );

            add_action( 'admin_init', [ $this, 'add_webhooks' ] );
            add_action( 'admin_init', [ $this, 'update_webhooks' ] );
            /**
             * Add Purchase Price Fields for Variations.
             */
            add_action( 'woocommerce_variation_options_pricing', [ $this, 'variation_options_pricing' ], 10, 3 );
            add_action( 'woocommerce_save_product_variation', [ $this, 'save_product_variation' ], 10, 2 );
            add_filter( 'woocommerce_available_variation', [ $this, 'available_variation' ] );

            /**
             * Add Purchase Price Field for Single Product. ( it will be there too, if it is a variable product )
             */
            add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
            add_action( 'save_post', [ $this, 'save_product_purchase_price' ] );
        }
    }
    /**
     * Enqueueing scripts
     *
     * @param string $hook
     * @return void
     */
    public function enqueue_scripts( $hook ){
        if( $hook != 'woocommerce_page_wc-settings' || isset( $_GET['tab'] ) && trim($_GET['tab']) !== 'sme-accounting' ) {
            return;
        }

        $this->enqueue( 'woocommerce' );
    }
    /**
     * SMEVai WooCommerce Settings Fields. ( Not in use right now )
     * @return void
     */
    public function get_settings_fields(){
        return [
            'section_title' => [
                'name' => __( 'SMEVai Settings', 'sme-accounting' ),
                'type' => 'title',
                'desc' => '',
                'id'   => 'smevai_section_title'
            ],
            'title' => [
                'name' => __( 'API Key', 'sme-accounting' ),
                'type' => 'text',
                'desc' => __( 'Enter your SMEVai API Key here.', 'sme-accounting' ),
                'id'   => 'smevai_secret_key'
            ],
            'section_end' => [
                'type' => 'sectionend',
                'id'   => 'smevai_section_end'
            ]
        ];
    }
    /**
     * Added SMEVai settings tab into WooCommerce Settings Tabs Array
     *
     * @param array $settings_tabs
     * @return void
     */
    public function add_settings_tab( $settings_tabs ){
        $settings_tabs['sme-accounting'] = __( 'SME Accounting', 'sme-accounting' );
        return $settings_tabs;
    }
    /**
     * SMEVai Settings for WooCommerce.
     * @return void
     */
    public function settings_tab(){
        // Hide the save button.
		// $GLOBALS['hide_save_button'] = true;

        do_action('smevai::before_settings');

		$this->settings_submit();
		$smevai_secret_key = ! empty( $this->settings['smevai_secret_key'] ) ? $this->settings['smevai_secret_key'] : '';

        include_once SMEVAI_ABSPATH . 'views/woocommerce.php';
    }
    /**
     * WooCommerce Settings Footer For SMEVai.
     * @return void
     */
    public function settings_footer(){
        include_once SMEVAI_ABSPATH . 'views/footer.php';
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
			// if( ! empty( $settings['smevai_secret_key'] ) ) {
            // }
            $this->settings = $settings;
            update_option('smevai_settings', $settings, 'no');
		}

		do_action('smevai::save_settings', $this->settings);
	}
    /**
     * This functions add smevai_secret_key to http headers.
     *
     * @param array $http_args
     * @return array
     */
    public function add_secret_key_to_header( $http_args ) {
        $smeSecretKey = $this->settings['smevai_secret_key'];
        if ( !empty($smeSecretKey) ) {
            $http_args['headers']['smevai-secret-key'] = $smeSecretKey;
            $http_args['headers']['Authorization'] = 'Bearer ' . $smeSecretKey;
        }

        return $http_args;
    }
    /**
     * Add webhooks in WooCommerce Settings>Advanced>Webhooks
     *
     * @return void
     */
    public function add_webhooks() {
        if( empty( $this->settings['smevai_secret_key'] )) {
            return;
        }

        if( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $secret     = $this->settings['smevai_secret_key'];
        $apiVersion = 3;

        $webhookLists = [
            [
                'name'        => 'Product Create',
                'status'      => 'active',
                'topic'       => 'product.created',
                'deliveryUrl' => $this->makeDeliveryUrl('product/create'),
            ],
            [
                'name'        => 'Product Update',
                'status'      => 'active',
                'topic'       => 'product.updated',
                'deliveryUrl' => $this->makeDeliveryUrl('product/update'),
            ],
            [
                'name'        => 'Product Delete',
                'status'      => 'active',
                'topic'       => 'product.deleted',
                'deliveryUrl' => $this->makeDeliveryUrl('product/delete'),
            ],
            [
                'name'        => 'Order Create',
                'status'      => 'active',
                'topic'       => 'order.created',
                'deliveryUrl' => $this->makeDeliveryUrl('order/create'),
            ],
            [
                'name'        => 'Order Update',
                'status'      => 'active',
                'topic'       => 'order.updated',
                'deliveryUrl' => $this->makeDeliveryUrl('order/update'),
            ],
            [
                'name'        => 'Order Delete',
                'status'      => 'active',
                'topic'       => 'order.deleted',
                'deliveryUrl' => $this->makeDeliveryUrl('order/delete'),
            ]
        ];

        if( get_option('_smevai_woocommerce_webhook_added', false) == false ) {
            foreach($webhookLists as $webhookList) {
                $webhook = new \WC_Webhook();
                $webhook->set_name( $webhookList['name'] );
                $webhook->set_status( $webhookList['status'] );
                $webhook->set_delivery_url( $webhookList['deliveryUrl'] );
                $webhook->set_secret( $secret );
                $webhook->set_topic( $webhookList['topic'] );
                $webhook->set_api_version( $apiVersion );
                $webhook->set_user_id( get_current_user_id() );
                $webhook->set_pending_delivery( false );
                $webhook->save();
            }

            update_option('_smevai_woocommerce_webhook_added', true);
        }
    }

    public function update_webhooks() {
        if( empty( $this->settings['smevai_secret_key'] )) {
            return;
        }

        if( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        global $wpdb;

        $secret     = $this->settings['smevai_secret_key'];
        $apiVersion = 3;

        if( get_option('_smevai_woocommerce_webhook_added', true) == true ) {
            $previousWebhooks = $wpdb->get_results(
                "
                    SELECT *
                    FROM {$wpdb->prefix}wc_webhooks
                "
            );

            foreach($previousWebhooks as $previousWebhook) {
                $name = str_replace(" ", "/", strtolower($previousWebhook->name));
                $deliveryUrl = $this->makeDeliveryUrl($name);
               
                if ( $previousWebhook->delivery_url != $deliveryUrl ) {
                    $webhook = wc_get_webhook( $previousWebhook->webhook_id );
                    $webhook->set_delivery_url( $deliveryUrl );
                    $webhook->save();
                }
            }
        }
    }

    public function variation_options_pricing( $loop, $variation_data, $variation ){
        woocommerce_wp_text_input( array(
            'wrapper_class' => 'form-row',
            'custom_attributes' => [
                'required' => true,
            ],
            'id' => 'purchase_price' . $loop,
            'name' => 'purchase_price[' . $loop . ']',
            'class' => 'short wc_input_price',
            'label' => __( 'Purchase Price', 'sme-accounting' ),
            'placeholder' => __( 'Purchase Price', 'sme-accounting' ),
            'value' => get_post_meta( $variation->ID, 'purchase_price', true )
        ) );
    }

    public function save_product_variation($variation_id, $i){
        $purchase_price_field = sanitize_text_field( ! empty( $_POST['purchase_price'][$i] ) ? $_POST['purchase_price'][$i] : 0 );
        if ( ! empty( $purchase_price_field ) ) {
            update_post_meta( $variation_id, 'purchase_price', esc_attr( $purchase_price_field ) );
        }
    }

    public function available_variation( $variations ){
        $html = '<div class="smevai-woocommerce-custom-field">';
            $html .= \sprintf(
                '%s: <span>%s</span>',
                __( 'Custom Field', 'sme-accounting' ),
                get_post_meta( $variations[ 'variation_id' ], 'purchase_price', true )
            );
        $html .= '</div>';

        $variations['purchase_price'] = $html;
        return $variations;
    }

    public function add_meta_boxes(){
        add_meta_box(
            'single_purchase_price',
            __( 'Add your product purchase price', 'sme-accounting' ),
            [$this, 'purchase_meta_box'],
            'product',
            'normal',
            'high'
        );
    }

    public function purchase_meta_box() {
        global $post;
        $purchase_price = get_post_meta($post->ID, 'purchase_price', true);

        wp_nonce_field( 'smevai_purchase_price_nonce', 'smevai_pp_nonce' );
        ?>
            <table>
                <tr>
                    <td><label for="purchase_price"><strong><?php _e('Product Purchase Price', 'sme-accounting'); ?>: </strong></label></td>
                    <td><input required type="text" id="purchase_price" placeholder="<?php echo esc_attr__('Product Purchase Price', 'sme-accounting'); ?>" name="purchase_price" value="<?php echo esc_attr($purchase_price); ?>" size="60" /></td>
                </tr>
            </table>
        <?php
    }

    public function save_product_purchase_price( $id ){
        if(!wp_verify_nonce($_POST['smevai_pp_nonce'], 'smevai_purchase_price_nonce')) {
            return $id;
        }

        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if(!current_user_can('edit_page', $id)) {
            return;
        }

        $singlePurchase_price = sanitize_text_field($_POST['purchase_price']);

        if (get_post_meta($id, 'purchase_price', false)) {
            update_post_meta($id, 'purchase_price', $singlePurchase_price);
        } else {
            add_post_meta($id, 'purchase_price', $singlePurchase_price);
        }
    }

    public function create_keys(){
        if( get_transient( 'smevai_woocommerce_rest_keys' ) !== false ) {
            return;
        }

        global $wpdb;

        $description = sprintf(
			'%s - API (%s)',
			'SMEVai WooCommerce Read',
			gmdate( 'Y-m-d H:i:s' )
		);

        $permissions     = 'read_write';
		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

        $user = wp_get_current_user();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $user->ID,
				'description'     => $description,
				'permissions'     => $permissions,
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

        $data = array(
            'url' => site_url(),
			'key_id'          => $wpdb->insert_id,
			'user_id'         => $user->ID,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'permissions' => $permissions,
		);

        set_transient( 'smevai_woocommerce_rest_keys', $data );
    }

    public function send_keys_to_smevai( $settings ){
        $smevaiSecretKey = ! empty( $settings['smevai_secret_key'] ) ? $settings['smevai_secret_key'] : '';

		if( ! empty( $smevaiSecretKey ) ) {
			$keys = get_transient( 'smevai_woocommerce_rest_keys' );
			$endpoint = SMEVAI_API_ROOT . 'settings';
			$body = wp_json_encode( $keys );
			$options = [
				'body'        => $body,
				'headers'     => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $smevaiSecretKey,
				],
				'method'      => 'POST',
				'timeout'     => 60,
				'redirection' => 5,
				'blocking'    => true,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'data_format' => 'body',
			];

			$response = wp_remote_post( $endpoint, $options );
			$response_code = wp_remote_retrieve_response_code($response);
			if( $response_code != 200 ) {
				//TODO: What if request failed?
			}
		}
    }
}