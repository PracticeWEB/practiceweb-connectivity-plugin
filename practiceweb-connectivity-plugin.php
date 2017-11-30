<?php

/**
 * PracticeWEB connectivity Plugin
 *
 * @link              http://www.practiceweb.co.uk
 * @since             1.0.0
 * @package           Practiceweb_Connectivity_Plugin
 *
 * @wordpress-plugin
 * Plugin Name:       PracticeWEB Connectivity Plugin
 * Plugin URI:        www.practiceweb.co.uk
 * Description:       Connectivity features from PracticeWEB.
 * Version:           1.0.0-beta.5
 * Author:            David Robinson
 * Author URI:        http://practiceweb.co.uk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       practiceweb-connectivity-plugin
 * Domain Path:       /languages
 */

use Sift\Practiceweb\Connectivity\Plugin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Composer autoloader.
require __DIR__ . '/vendor/autoload.php';

/**
 * Bootstrap the plugin.
 */
function launch_practiceweb_connectivity_plugin()
{
    $plugin = new Plugin(plugin_dir_path(__FILE__), __FILE__);
    // Register services
    // Admin page is required as a core service.
    $plugin->registerService('adminpage', Sift\Practiceweb\Connectivity\AdminPage\AdminPageService::class);

    // Get config
    $config = get_option('practiceweb-connectivity-config', array());

    // Force FWP for now as a background service
    $plugin->registerService('fwp', Sift\Practiceweb\Connectivity\FeedWordPress\FeedWordPressService::class);

    // TODO Ideally a config file would define these.
    $serviceMap = array(
      'news' =>  Sift\Practiceweb\Connectivity\News\NewsService::class,
      'deadlines' =>  Sift\Practiceweb\Connectivity\Deadlines\DeadlinesService::class,
    );

    if (!empty($config['service'])) {
        foreach ($config['service'] as $service) {
            if (isset($serviceMap[$service])) {
                $plugin->registerService($service, $serviceMap[$service]);
            }
        }
    }

    $plugin->run();
}

// Bootstrap the plugin after plugins are loaded.
add_action('plugins_loaded', 'launch_practiceweb_connectivity_plugin');
