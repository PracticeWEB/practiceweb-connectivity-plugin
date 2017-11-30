<?php

namespace Sift\Practiceweb\Connectivity\AdminPage;

use Sift\Practiceweb\Connectivity\ServiceAbstract;
use Sift\Practiceweb\Connectivity\HookLoader;
use Sift\Practiceweb\Connectivity\TemplateHandler;

/**
 * Class AdminPageService.
 *
 * @package Sift\Practiceweb\Connectivity\AdminPage
 */
class AdminPageService extends ServiceAbstract
{

    /**
     * Register actions.
     */
    public function addActions()
    {
        $this->addAction('admin_init', 'registerSettings');
        $this->addAction('admin_menu', 'adminMenu');
        $this->addAction('update_option_practiceweb-connectivity-config', 'processUpdate');
    }

    /**
     * Register wordpress settings.
     */
    public function registerSettings()
    {
        register_setting(
            'practiceweb-connectivity-group',
            'practiceweb-connectivity-config',
            array(
                'sanitize_callback' => array($this, 'sanitizeInput')
            )
        );
    }

    /**
     * Admin menu callback.
     */
    public function adminMenu()
    {
        add_menu_page(
            'PracticeWEB Connectivity',
            'PracticeWEB Connectivity',
            'manage_options',
            'practiceweb-connectivity',
            array($this, 'adminOptionspage')
        );
        // Call a custom hook for anything that wants to add it's own admin page.
        do_action('practiceweb_connectivity_admin_menu');
    }

    /**
     * Options page callback.
     */
    public function adminOptionsPage()
    {
        $vars = array();
        $vars['practiceweb-connectivity-config'] = get_option('practiceweb-connectivity-config', array());

        // Get options
        if (!empty($vars['practiceweb-connectivity-config']['service'])) {
            foreach ($vars['practiceweb-connectivity-config']['service'] as $key => $value) {
                $vars['checked'][$key] = 'checked';
            }
        }

        $this->renderTemplate('adminpage/core-settings-page', $vars);
        // Call a custom hook for extending the options.
        do_action('practiceweb_connectivity_admin_options');
    }

    /**
     * Sanitize inputs for admin page.
     *
     * @param array $input
     *   Array of input fields.
     *
     * @return array
     *   Array of cleaned inputs.
     */
    public function sanitizeInput(array $input)
    {
        $input['apiKey'] = sanitize_text_field($input['apiKey']);
        return $input;
    }

    public function processUpdate(array $old = array(), array $new = array())
    {
        if (array_diff($old['service'], $new['service'])) {
            // If services have changed then ensure that the rewrite rules update for post types.
            flush_rewrite_rules();
        }
    }
}
