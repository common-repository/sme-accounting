<?php
/**
 * Module is a layer for all platform
 *
 * @since 1.0.0
 */
namespace SMEVai\Core;

class Module extends Base {
    /**
     * All modules
     * @var array
     */
    private $modules = [];

    /**
     * Adding modules in the list.
     *
     * @param array $module
     * @return void
     */
    public function add( $module ){
        $this->modules[ $module['id'] ] = $module;
    }

    /**
     * Determine the active module.
     *
     * @return Platform
     */
    public function active(){
        // FIXME: This active module name should comes from saved options. // for now its WooCommerce.
        $active = 'woocommerce';
        return (object) $this->modules[ $active ];
    }

    /**
     * Get all modules
     * @return void
     */
    public function get_all(){
        return $this->modules;
    }

}