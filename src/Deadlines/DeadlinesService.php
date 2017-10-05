<?php

namespace Sift\Practiceweb\Connectivity\Deadlines;

use Sift\Practiceweb\Connectivity\ServiceAbstract;
use Sift\Practiceweb\Connectivity\HookLoader;
use Sift\Practiceweb\Connectivity\TemplateHandler;

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
        $this->addAction('admin_post_practiceweb_connectivity_deadlines_setup', 'setupPageSubmit');
        //$this->addAction('et_builder_ready', 'registerDiviModules');
    }

    public function createPostTypes()
    {
        $postNames= array(
            'post_type_name' => 'deadlines',
            'singluar' => 'Deadline',
            'plural' => 'Dates and deadlines',
            'slug' => 'deadlines',
        );
        $postOptions = array(
            'has_archive' => true,
        );
        $taxonomyNames = array(
            'taxonomy_name' => 'PracticeWEBContent',
            'singular' => 'PracticeWEB Category',
            'plural' => 'PracticeWEB Categories',
            'slug' => 'practiceweb-taxonomy',
        );
        $taxonomyOptions = array(

        );

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
            'Deadlines Configuration',
            'Deadlines Configuration',
            'manage_options',
            'deadlines-configuration',
            array($this, 'setupPage')
        );
    }

    /**
     * Config page callback.
     */
    public function setupPage()
    {
        // CHeck current status.
        if (is_plugin_active('feedwordpress/feedwordpress.php')) {
            $vars = array();
            // Get current id.
            $feedLinkId = get_option('practiceweb-connectivity-deadlinesfeed-link_id', null);
            if ($feedLinkId) {
                $vars['exists'] = true;
                $feedLink = new \SyndicatedLink($feedLinkId);
                // Use the base link before any filters are applied.
                $vars['uri'] = $feedLink->link->link_rss;
            }
            $this->renderTemplate('deadlines/feed-setup', $vars);
        } else {
            // TODO can we interrogate TGMA.
            $this->renderTemplate('feedwordpress/fwp-not-enabled');
        }
    }

    /**
     * Submit handler for feed setup page.
     */
    public function setupPageSubmit()
    {
        $data = $_REQUEST;
        // Confirm capability and nonce.
        $validNonce = wp_verify_nonce($data['_wpnonce'], 'practiceweb_connectivity_deadlines_setup');
        if (current_user_can('manage_options') && $validNonce) {
            $uri = sanitize_text_field($data['uri']);
            if ($uri) {
                $feedLinkId = get_option('practiceweb-connectivity-deadlinesfeed-link_id', null);
                if ($feedLinkId) {
                    $feedLink = new \SyndicatedLink($feedLinkId);
                    if ($feedLink) {
                        $feedLink->set_uri($uri);
                    } else {
                        $feedLinkId = $this->createDeadlinesFeed($uri);
                    }
                } else {
                    $feedLinkId = $this->createDeadlinesFeed($uri);
                }
                if ($data['fetch'] == 'yes') {
                    wp_schedule_single_event(time(), 'practiceweb_feed_fetch', array($feedLinkId));
                }
            }
            wp_redirect(admin_url('admin.php?page=feed-configuration'));
        } else {
            wp_die('Action not permitted.', 403);
        }
    }


    /**
     * Create a feed word press feed link.
     *
     * @param string $rssUrl
     *   RSS url.
     *
     * @return int
     *   The fwp link id.
     */
    public function createNewsFeed($rssUrl)
    {
        $feedSettings = array(
            // Feeds section.
            'update/hold' => 'scheduled',
            'update/window' => 60,
            'update/minimum' => 'yes',
            'fetch timeout' => 20,
            'update_incremental' => 'incremental',
            'tombstones' => 'yes',
            // Posts section.
            'post status' => 'publish',
            'freeze updates' => 'no',
            'resolve relative' => 'no',
            'munge permalink' => 'no',
            'munge comments feed links' => 'no',
            'comment status' => 'closed',
            'ping status' => 'closed',
            'syndicated post type' => 'deadlines',
            'no robots' => 'yes',
            'postmeta' => array(
                'deadlineDate' => '$(ev:startdate)',
            ),
            // Authors setting.
            'map authors' => array(
                'name' => array(
                    // This maps all posts to admin.
                    '*' => 1,
                ),
            ),
            // Category settings.
            'add/PracticeWEB Content' => 'yes',
            'unfamiliar category' => 'create:PracticeWEBContent',
            'match/cats' => array('PracticeWEBContent'),
            // Add Key.
            'practiceweb apiKey' => 'yes'
        );
        $linkId = \FeedWordPress::syndicate_link('PracticeWEB Deadlines Feed', 'www.practiceweb.co.uk', $rssUrl);
        // Load the link.
        $link = new \SyndicatedLink($linkId);
        // Update settings.
        foreach ($feedSettings as $name => $value) {
            $link->update_setting($name, $value);
        }
        $link->save_settings();
        update_option('practiceweb-connectivity-deadlinesfeed-link_id', $linkId);
        return $linkId;
    }

}