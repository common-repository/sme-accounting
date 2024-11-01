        <div class="smevai-header-title">
            <h3><?php _e('SMEVai API Key', 'sme-accounting') ?></h3>
            <?php
                if( isset( $nonce_error ) ) {
                    echo '<p>' . $nonce_error . '</p>';
                }
            ?>
        </div>
        <div class="smevai-api-input-wrapper">
                <?php echo wp_nonce_field( 'smevai_settings', 'smevai_nonce' ); ?>
                <input
                    type="password"
                    class="smevai_secret_key"
                    name="smevai_secret_key"
                    id="smevaiSecretKey"
                    value="<?php echo esc_attr($smevai_secret_key); ?>"
                    placeholder="<?php echo esc_attr__('Enter your api key here...', 'sme-accounting'); ?>"
                />
        </div>