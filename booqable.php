<?php
/**
 * Plugin Name: Booqable
 * Description: All-in-one rental software
 * Version: 0.0.1
 * Author: Akhela
 */

use Booqable\Controller;
use Booqable\Helper\Options;

if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

define('BOOQABLE_PLUGIN_VERSION', '0.0.1');
define('BOOQABLE_PLUGIN_URL', trim(plugin_dir_url( __FILE__ ), '/'));

class Booqable{

    private static $isConfigured;

    /**
     * initialize
     *
     * Sets up the Meta Steroids
     *
     * @return  void
     */
    function initialize() {

        include __DIR__.'/vendor/autoload.php';

        new Controller\Settings();

        if( !defined('BOOQABLE_KEY_PATH') )
            return;

        new Controller\Editor();
        new Controller\Product();
    }

    public static function isConfigured()
    {
        if( is_null(self::$isConfigured) )
            self::$isConfigured = Options::get('token') && Options::get('domain');

        return self::$isConfigured;
    }

    public static function isConnected()
    {
        //Request::get('/api/boomerang/settings/current');
    }
}


function booqable() {

    global $booqable;

    // Instantiate only once.
    if ( ! isset( $booqable ) ) {

        $booqable = new Booqable();
        $booqable->initialize();
    }

    return $booqable;
}

if( ( defined('WP_INSTALLING') && WP_INSTALLING ) || !defined('WPINC') )
    return;

// Instantiate.
booqable();
