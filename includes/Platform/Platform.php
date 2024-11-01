<?php

namespace SMEVai\Platform;

defined( 'ABSPATH' ) || exit;

use SMEVai\Core\Base;
use SMEVai\Core\Module;

abstract class Platform extends Base {
    public $id;
    public $title;

    public function __construct(){
        $module = Module::get_instance();
        $module->add([
            'id' => $this->id,
            'title' => $this->title,
            'module' => $this,
        ]);
    }

    /**
     * You must implement this in your platform class.
     *
     * @return void
     */
    abstract public function init();

    /**
     * Get API Delivery URL
     *
     * @param string $endpoint
     * @return string
     */
    protected function makeDeliveryUrl( $endpoint ){
        return SMEVAI_API_ROOT . $endpoint;
    }
    /**
     * For enqueueing scripts dynamically.
     *
     * @param string $handle Handle is a filename to be specific.
     * @param string $type style|script
     * @param array $deps
     * @param array $args
     * @return void
     */
    public function enqueue( $handle, $type = 'style', $deps = [], $args = [] ){
        $src = $type === 'style' ? SMEVAI_ASSETS . 'css/' . $handle . '.css' : SMEVAI_ASSETS . 'js/' . $handle . '.js';
        $last_arg = '';

        switch( $type ) {
            case 'style':
                $last_arg = empty( $args['media'] ) ? 'all' : trim( $args['media'] );
                break;
            case 'script':
                $last_arg = empty( $args['in_footer'] ) ? true : (bool) $args['in_footer'];
                break;
        }

        call_user_func_array( "wp_enqueue_$type",  [
            'smevai-' . $handle, $src, $deps,
            ! empty( $args['version'] ) ? $args['version'] : '',
            $last_arg
        ]);
    }
    /**
     * For getting assets url.
     *
     * @param string $name
     * @param string $type
     * @return void
     */
    public function assets( $name, $type = 'images' ){
        return SMEVAI_ASSETS . $type . '/' . $name;
    }
}