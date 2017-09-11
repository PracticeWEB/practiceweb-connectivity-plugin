<?php

namespace Sift\Practiceweb\Connectivity\FeedWordPress;

use Sift\Practiceweb\Connectivity\ServiceAbstract;

/**
 * Class FeedWordPressService.
 *
 * @package Sift\Practiceweb\Connectivity\FeedWordPress
 */
class FeedWordPressService extends ServiceAbstract
{

    /**
     * Register actions.
     */
    public function addActions()
    {
        $this->addAction('tgmpa_register', 'registerPlugin');
        $this->addAction('wp_head', 'setNoRobots');
        $this->addAction('feedwordpress_admin_page_posts_meta_boxes', 'addFeedRobotsMetabox');
        $this->addAction('feedwordpress_admin_page_posts_save', 'feedRobotsMetaboxSave', 10, 2);
        $this->addAction('practiceweb_connectivity_admin_menu', 'adminPageMenu');
        $this->addAction('admin_post_practiceweb_connectivity_feed_setup', 'setupPageSubmit');
        $this->addAction('practiceweb_feed_fetch', 'feedFetch');
    }

    /**
     * Register filters.
     */
    public function addFilters()
    {
        $this->addFilter('syndicated_feed_parameters', 'addFeedParameters', 10, 3);
    }

    /**
     * Register a tgm supported plugin.
     */
    public function registerPlugin()
    {
        $plugins = array(
            array(
                'name' => 'FeedWordPress',
                'slug' => 'feedwordpress',
                'required' => true,
            ),
        );
        $config = array(
            'id' => 'practiceweb-connectivity-plugin',
            'default_path' => '',
            'menu' => 'tgmpa-install-plugins',
            'parent_slug' => 'plugins.php',
            'capability' => 'manage_options',
            'has_notices' => true,
            'dismissable' => true,
            'dismiss_msg' => '',
            'is_automatic' => true,
            'message' => '',
        );
        tgmpa($plugins, $config);
    }

    /**
     * Set no robots for imported content.
     */
    public function setNoRobots()
    {
        if (is_single()) {
            $postId = get_the_ID();
            $feed = get_syndication_feed_object($postId);
            if ($feed) {
                $noRobots = $feed->settings['no robots'];
                if ($noRobots === 'yes') {
                    wp_no_robots();
                }
            }
        }
    }

    /**
     * Adds a metabox to the feed word press config.
     *
     * @param \FeedWordPressAdminPage $page
     *   FWP page object.
     */
    public function addFeedRobotsMetabox(\FeedWordPressAdminPage $page)
    {
        add_meta_box(
            'norobots_metabox',
            'Set no robots title',
            array($this, 'feedRobotsMetabox'),
            $page->meta_box_context(),
            $page->meta_box_context()
        );
    }

    /**
     * Render the metabox for no robots.
     *
     * @param \FeedWordPressAdminPage $page
     *   FeedWordpress page.
     * @param mixed $box
     *   Meta Box info.
     */
    public function feedRobotsMetabox(\FeedWordPressAdminPage $page, $box = null)
    {
        if ($page->for_feed_settings()) {
            $vars = array();
            // Get our current setting.
            $setting = $page->link->setting('no robots', null, null, 'no');
            $vars['checked'] = array();
            $vars['checked']['norobots']['yes'] = $setting == 'yes' ? 'checked=checked' : '';
            $vars['checked']['norobots']['no'] = $setting == 'no' ? 'checked=checked' : '';
            $this->renderTemplate('feedwordpress/norobots-metabox-feed-settings', $vars);
        } else {
            $this->renderTemplate('feedwordpress/norobots-global-settings');
        }
    }

    /**
     * Save the settings from the metabox.
     *
     * @param array $params
     *   Array of saved data.
     * @param \FeedWordPressAdminPage $page
     *   FeedWordpress page.
     */
    public function feedRobotsMetaboxSave(array $params, \FeedWordPressAdminPage $page)
    {
        if (isset($params['save']) or isset($params['submit'])) {
            if ($page->for_feed_settings()) {
                $page->link->settings['no robots'] = $params['norobots'];
                $page->link->save_settings(true);
            }
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
            'syndicated post type' => 'post',
            'no robots' => 'yes',
            // Authors setting.
            'map authors' => array(
                'name' => array(
                    // This maps all posts to admin.
                    '*' => 1,
                ),
            ),
            // Category settings.
            'add/category' => 'yes',
            'unfamiliar category' => 'create:category',
            'match/cats' => array('category'),
        );
        $linkId = \FeedWordPress::syndicate_link('PracticeWEB News Feed', 'www.practiceweb.co.uk', $rssUrl);
        // Load the link.
        $link = new \SyndicatedLink($linkId);
        // Update settings.
        foreach ($feedSettings as $name => $value) {
            $link->update_setting($name, $value);
        }
        $link->save_settings();
        update_option('practiceweb-connectivity-newsfeed-link_id', $linkId);
        return $linkId;
    }

    /**
     * Admin page callback.
     */
    public function adminPageMenu()
    {
        add_submenu_page(
            'practiceweb-connectivity',
            'Feed Configuration',
            'Feed Configuration',
            'manage_options',
            'feed-configuration',
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
            $feedLinkId = get_option('practiceweb-connectivity-newsfeed-link_id', null);
            if ($feedLinkId) {
                $vars['exists'] = true;
                $feedLink = new \SyndicatedLink($feedLinkId);
                // Use the base link before any filters are applied.
                $vars['uri'] = $feedLink->link->link_rss;
            }
            $this->renderTemplate('feedwordpress/feed-setup', $vars);
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
        $validNonce = wp_verify_nonce($data['_wpnonce'], 'practiceweb_connectivity_feed_setup');
        if (current_user_can('manage_options') && $validNonce) {
            $uri = sanitize_text_field($data['uri']);
            if ($uri) {
                // TODO check if it already exists.
                $feedLinkId = get_option('practiceweb-connectivity-newsfeed-link_id', null);
                if ($feedLinkId) {
                    $feedLink = new \SyndicatedLink($feedLinkId);
                    if ($feedLink) {
                        $feedLink->set_uri($uri);
                    } else {
                        $this->createNewsFeed($uri);
                    }
                } else {
                    $this->createNewsFeed($uri);
                }
                if ($data['fetch'] == 'yes') {
                    wp_schedule_single_event(time(), 'practiceweb_feed_fetch');
                }
            }
            wp_redirect(admin_url('admin.php?page=feed-configuration'));
        } else {
            wp_die('Action not permitted.', 403);
        }
    }

    /**
     * Filter to add APIKey to a feed link.
     *
     * @param array|null $params
     *   Array of query params or null.
     * @param string $uri
     *   Uri params will be added to.
     * @param \SyndicatedLink $link
     *   Link object the uri belongs to.
     *
     * @return array
     *   Updated query param array.
     */
    public function addFeedParameters($params = array(), $uri = '', \SyndicatedLink $link = null)
    {
        // Is this our link?
        $feedLinkId = get_option('practiceweb-connectivity-newsfeed-link_id', null);
        if ($link->id === $feedLinkId) {
            $config = get_option('practiceweb-connectivity-config', array());
            $apiKey = $config['apiKey'];
            if ($apiKey) {
                $params[] = array('apiKey', $apiKey);
            }
        }
        return $params;
    }

    /**
     * Action to fetch the feed.
     */
    public function feedFetch()
    {
        $feedLinkId = get_option('practiceweb-connectivity-newsfeed-link_id', null);
        if ($feedLinkId) {
            $link = new \SyndicatedLink($feedLinkId);
            if ($link) {
                $link->poll();
            }
        }
    }
}
