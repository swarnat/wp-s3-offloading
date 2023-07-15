<?php
namespace WPS3\S3\Offload;

class Loader {

    public function run() {
        if(is_admin() && !defined('DOING_AJAX')) {
            require_once(WPS3_PLUGIN_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'admin.php');

            $admin = new Admin();
            $admin->load_hooks();
        }

        require_once(WPS3_PLUGIN_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'syncer.php');
        require_once(WPS3_PLUGIN_BASE_DIR . 'includes' . DIRECTORY_SEPARATOR . 'frontend.php');

        $frontend = new Frontend();
        $frontend->load_hooks();

        /*
        $syncer = new Syncer();
        $syncer->registerStreamWrapper();
        
        add_filter( 'upload_dir', [$syncer, 'upload_dir'], 999, 1 );
        */
    }
    
   

}