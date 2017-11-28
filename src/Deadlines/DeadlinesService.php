<?php

namespace Sift\Practiceweb\Connectivity\Deadlines;

use Sift\Practiceweb\Connectivity\ServiceAbstract;
use Sift\Practiceweb\Connectivity\HookLoader;
use Sift\Practiceweb\Connectivity\TemplateHandler;

use DateTimeImmutable;
use DateInterval;

/**
 * Class FeedWordPressService.
 *
 * @package Sift\Practiceweb\Connectivity\FeedWordPress
 */
class DeadlinesService extends ServiceAbstract
{

    /**
     * Register actions.
     */
    public function addActions()
    {
        $this->addAction('practiceweb_connectivity_admin_menu', 'adminPageMenu');
        $this->addAction('admin_post_practiceweb_connectivity_deadlines_upload', 'uploadPageSubmit');
        $this->addAction('wp_enqueue_scripts', 'registerScripts');
        $this->addAction('admin_notices', 'showAdminNotices');
        $this->addAction('admin_enqueue_scripts', 'registerAdminScripts');
    }

    /**
     * Create deadlines post type and taxonomy.
     */
    public function createPostTypes()
    {
        $postNames = array(
            'post_type_name' => 'deadlines',
            'singluar' => 'Deadline',
            'plural' => 'Dates and deadlines',
            'slug' => 'deadlines',
        );
        $postOptions = array(
            'has_archive' => true,
            'supports' => array('title', 'editor', 'excerpt', 'custom-fields'),
        );
        $taxonomyNames = array(
            'taxonomy_name' => 'PracticeWEBDeadlines',
            'singular' => 'PracticeWEB Deadlines Category',
            'plural' => 'PracticeWEB Deadlines Categories',
            'slug' => 'practiceweb-deadlines-taxonomy',
        );
        $taxonomyOptions = array();

        // Just making a CPT instance triggers all we need.
        $deadlines = new \CPT($postNames, $postOptions);
        // Add taxonomy.
        $deadlines->register_taxonomy($taxonomyNames, $taxonomyOptions);

        // Use a closure to register a flush on activation.
        register_activation_hook($this->pluginFile, function () {
            $deadlines->flush();
        });
    }

    /**
     * Admin page callback.
     */
    public function adminPageMenu()
    {
        add_submenu_page(
            'practiceweb-connectivity',
            'Deadlines Upload',
            'Deadlines Upload',
            'manage_options',
            'deadlines-upload',
            array($this, 'uploadPage')
        );
    }

    /**
     * Register javascript.
     */
    public function registerScripts()
    {
        $jsPath = plugin_dir_url($this->pluginFile) . '/js/deadlines.js';
        wp_register_script('pw-deadlines', $jsPath, array('jquery'), '1.0.0', true);
    }

    /**
     * Register javascript.
     */
    public function registerAdminScripts()
    {
        $cssPath = plugin_dir_url($this->pluginFile) . '/css/deadlines-admin.css';
        wp_register_style('deadlines-admin', $cssPath);
    }

    /**
     * Page view for upload.
     */
    public function uploadPage()
    {
        // Add our css
        wp_enqueue_style('deadlines-admin');
        $vars = array();
        $this->renderTemplate('deadlines/upload', $vars);
    }

    /**
     * Submit handler for upload page.
     */
    public function uploadPageSubmit()
    {
        // TODO verify
        $loadMethod = $_REQUEST['loadMethod'];
        $handle = null;
        switch ($loadMethod) {
            case 'download':
                $config = get_option('practiceweb-connectivity-config', array());
                $apiKey = $config['apiKey'];
                $url = $_REQUEST['url'];
                $url = add_query_arg('apiKey', urlencode($apiKey), $url);
                $response = wp_remote_get($url);
                $body = wp_remote_retrieve_body($response);
                // Hold the content a stream so that we can use one method to read.
                $handle = fopen('php://temp', 'r+');
                fwrite($handle, $body);
                rewind($handle);
                break;
            case 'upload':
                $uploaded = $_FILES['deadlinesfile'];
                $handle = fopen($uploaded['tmp_name'], 'r');
                break;
        }

        // Assume that first line is header
        $header = fgetcsv($handle);
        while ($row = fgetcsv($handle)) {
            list($title, $uuid, $deadlineDate, $content, $teaser, $termsString, $guid) = $row;
            $terms = array_unique(array_map('trim', explode(',', $termsString)));
            $postInfo = array(
                'post_type' => 'deadlines',
                'post_title' => $title,
                'post_content' => $content,
                'post_excerpt' => $teaser,
                'post_status' => 'publish',
                'guid' => $guid,
                'meta_input' => array(
                    'deadlineDate' => $deadlineDate,
                ),
            );

            global $wpdb;
            // Detect existing GUID.
            $queryString = "SELECT ID FROM $wpdb->posts WHERE post_type='%s' AND guid = '%s'";
            $query = $wpdb->prepare($queryString, array('deadlines', $guid));
            $postId = $wpdb->get_var($query);
            if ($postId) {
                $postInfo['ID'] = $postId;
            }
            $postId = wp_insert_post($postInfo);
            if ($postId) {
                // Use wp_set_object_terms so that we can create new terms on demand.
                wp_set_object_terms($postId, $terms, 'PracticeWEBDeadlines');
            }
        }
        fclose($handle);
        $messageKey = get_current_user_id() . '_pwdeadlines';
        set_transient($messageKey, 'Uploaded deadlines data.');
        wp_redirect(admin_url('admin.php?page=deadlines-upload'));
    }

    /**
     * Helper to use transients as admin notices.
     */
    public function showAdminNotices()
    {
        // Get transients
        $messageKey = get_current_user_id() . '_pwdeadlines';
        $message = get_transient($messageKey);
        if ($message) {
            delete_transient($messageKey);
            $html = '<div class="notice notice-success is-dismissible"><p>%s</p></div>';
            $html = sprintf($html, $message);
            echo $html;
        }
    }
}
