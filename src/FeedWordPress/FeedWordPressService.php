<?php

namespace Sift\Practiceweb\Connectivity\FeedWordPress;

use Sift\Practiceweb\Connectivity\ServiceAbstract;
use Sift\Practiceweb\Connectivity\HookLoader;
use Sift\Practiceweb\Connectivity\TemplateHandler;

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
        // No robots support.
        $this->addAction('feedwordpress_admin_page_posts_meta_boxes', 'addFeedRobotsMetabox');
        $this->addAction('feedwordpress_admin_page_posts_save', 'feedRobotsMetaboxSave', 10, 2);
        // Attach APIKey support.
        $this->addAction('feedwordpress_admin_page_feeds_meta_boxes', 'addFeedAPIKeyMetabox');
        $this->addAction('feedwordpress_admin_page_feeds_save', 'feedAPIKeyMetaboxSave', 10, 2);
        // Callback for fetching a feed.
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
     * Adds a metabox to the feed word press config for APIKey usage.
     *
     * @param \FeedWordPressAdminPage $page
     *   FWP page object.
     */
    public function addFeedAPIKeyMetabox(\FeedWordPressAdminPage $page)
    {
        add_meta_box(
            'page',
            'Include APIKey',
            array($this, 'feedAPIKeyMetabox'),
            $page->meta_box_context(),
            $page->meta_box_context()
        );
    }

    /**
     * Render the metabox for APIkey.
     *
     * @param \FeedWordPressAdminPage $page
     *   FeedWordpress page.
     * @param mixed $box
     *   Meta Box info.
     */
    public function feedApiKeyMetabox(\FeedWordPressAdminPage $page, $box = null)
    {
        if ($page->for_feed_settings()) {
            $vars = array();
            // Get our current setting.
            $setting = $page->link->setting('practiceweb apiKey', null, null, 'no');
            $vars['checked'] = array();
            $vars['checked']['practicewebApiKey']['yes'] = $setting == 'yes' ? 'checked=checked' : '';
            $vars['checked']['practicewebApiKey']['no'] = $setting == 'no' ? 'checked=checked' : '';
            $this->renderTemplate('feedwordpress/apikey-metabox-feed-settings', $vars);
        } else {
            $this->renderTemplate('feedwordpress/apikey-global-settings');
        }
    }

    /**
     * Save the key settings from the metabox.
     *
     * @param array $params
     *   Array of saved data.
     * @param \FeedWordPressAdminPage $page
     *   FeedWordpress page.
     */
    public function feedAPIKeyMetaboxSave(array $params, \FeedWordPressAdminPage $page)
    {
        if (isset($params['save']) or isset($params['submit'])) {
            if ($page->for_feed_settings()) {
                $page->link->settings['practiceweb apiKey'] = $params['practicewebApiKey'];
                $page->link->save_settings(true);
            }
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
        // Get APIKEY option
        $addKey = $link->settings['practiceweb apiKey'];
        if ($addKey == 'yes') {
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
     *
     * @param int $feedLinkId
     *   id of the feed to poll.
     */
    public function feedFetch($feedLinkId)
    {
        if ($feedLinkId) {
            $link = new \SyndicatedLink($feedLinkId);
            if ($link) {
                $link->poll();
            }
        }
    }
}
