<div class="container">
    <div class="wrap">
        <div class="header-title">
            <h3><?php _e('SMEVai API Key', 'sme-accounting') ?></h3>
            <?php
                if( isset( $nonce_error ) ) {
                    echo '<p>' . esc_html( $nonce_error ) . '</p>';
                }
            ?>
        </div>
        <div>
            <form action="" method="POST">
                <?php echo wp_nonce_field( 'smevai_settings', 'smevai_nonce' ); ?>
                <input type="text" style="width: 20%;" class="form-control" name="smevai_secret_key" id="smevaiSecretKey" value="<?php echo esc_attr($smevai_secret_key); ?>" placeholder="<?php echo esc_attr__('Enter your api key here...', 'sme-accounting'); ?>">
                <button type="submit" class="button button-primary" name="smevai_submit"><?php _e( 'Save & Sync', 'sme-accounting' ); ?></button>
            </form>
        </div>
    </div>
</div>