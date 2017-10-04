<?php

namespace Sift\Practiceweb\Connectivity\News;

use Sift\Practiceweb\Connectivity\ServiceAbstract;
use Sift\Practiceweb\Connectivity\HookLoader;
use Sift\Practiceweb\Connectivity\TemplateHandler;
use CPT;



/**
 * Class NewsService.
 *
 * @package Sift\Practiceweb\Connectivity\News
 */
class NewsService extends ServiceAbstract
{

    /**
     * Register actions.
     */
    public function addActions()
    {
        $this->addAction('practiceweb_connectivity_admin_menu', 'adminPageMenu');
        $this->addAction('admin_post_practiceweb_connectivity_feed_setup', 'setupPageSubmit');
        $this->addAction('et_builder_ready', 'registerDiviModules');
    }

    public function createPostTypes()
    {
        $postNames= array(
            'post_type_name' => 'news',
            'singluar' => 'News Article',
            'plural' => 'News Articles',
            'slug' => 'news',
        );
        $postOptions = array(
            'has_archive' => true,
        );
        // Just making a CPT instance triggers all we need.
        $news = new \CPT($postNames, $postOptions);
        // TODO add taxonomy.
        // Use a closure to register a flush on activation.
        register_activation_hook($this->pluginFile, function () {
            $news->flush();
        });
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
            'syndicated post type' => 'news',
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
            // Add Key.
            'practiceweb apiKey' => 'yes'
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
                $feedLinkId = get_option('practiceweb-connectivity-newsfeed-link_id', null);
                if ($feedLinkId) {
                    $feedLink = new \SyndicatedLink($feedLinkId);
                    if ($feedLink) {
                        $feedLink->set_uri($uri);
                    } else {
                        $feedLinkId = $this->createNewsFeed($uri);
                    }
                } else {
                    $feedLinkId = $this->createNewsFeed($uri);
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

    public function registerDiviModules() {
        new PracticewebNewsModule();
    }

    public function addShortcodes()
    {
        add_shortcode('practiceweb-news', array($this, 'newsShortcode'));
    }

    public function newsShortcode($atts = array())
    {
        $query = $this->newsQuery();
        global $post;
        $this->renderTemplate('news/list-header');
        while ($query->have_posts()) {
            $query->the_post();
            $renderArgs = array(
                'post' => $post,
            );
            $this->renderTemplate('news/list-item', $renderArgs);
        }
        wp_reset_query();
        $footerArgs = array(
            // TODO generate paging.
         //   'pagination' => news_pagination(array('paged => '));
        );
        $this->renderTemplate('news/list-footer', $footerArgs);
    }

    public function newsQuery()
    {
        $queryArgs = array(
            'post_type' => 'news',
            'post_status' => array('publish'),
            'orderby' => 'date',
            'order' => 'DESC',
            'posts_per_page' => 10,
            'paged' => 1
        );
        $query = new \WP_Query($queryArgs);
        return $query;
    }
}
