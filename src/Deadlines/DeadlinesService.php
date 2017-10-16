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
        $this->addAction('admin_post_practiceweb_connectivity_deadlines_setup', 'setupPageSubmit');
        $this->addAction('admin_post_practiceweb_connectivity_deadlines_upload', 'uploadPageSubmit');
        //$this->addAction('et_builder_ready', 'registerDiviModules');
        $this->addAction('wp_enqueue_scripts', 'registerScripts');
        $this->addAction('admin_notices', 'showAdminNotices');
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
            'taxonomy_name' => 'PracticeWEBContent',
            'singular' => 'PracticeWEB Category',
            'plural' => 'PracticeWEB Categories',
            'slug' => 'practiceweb-taxonomy',
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
        // FEED based config is disabled for now.
        /*
        add_submenu_page(
            'practiceweb-connectivity',
            'Deadlines Configuration',
            'Deadlines Configuration',
            'manage_options',
            'deadlines-configuration',
            array($this, 'setupPage')
        );
        */
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
            wp_redirect(admin_url('admin.php?page=deadlines-configuration'));
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
    public function createDeadlinesFeed($rssUrl)
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

    /**
     * Add shortcodes.
     */
    public function addShortcodes()
    {
        add_shortcode('practiceweb-deadlines', array($this, 'shortcode'));
    }

    /**
     * Shortcode for deadlines.
     *
     * @return string
     */
    public function shortcode()
    {
        wp_enqueue_script('pw-deadlines');

        // Wordpress has no abstraction.
        $taxonomy = isset($_REQUEST['taxonomy']) ? $_REQUEST['taxonomy'] : array();

        // Get filters
        $filter = '';
        $html = '<div class="deadlines-container">';


        $dates = array(
            'all' => 'All',
            'thisweek' => 'This Week',
            'next2weeks' => 'Next 2 weeks',
            'thismonth' => 'This Month',
            'next2months' => 'Next 2 Months',
            'thisquarter' => 'This Quarter',
            'next2quarters' => 'Next 2 Quarters',
            'thisyear' => 'This Year',
            'next2years' => 'Next 2 Years',
        );

        $dateRange = 'all';
        if (isset($_REQUEST['dateRange']) && isset($dates[$_REQUEST['dateRange']])) {
            $dateRange = $_REQUEST['dateRange'];
        }
        $filter .= '<div class="deadlines-filters"><form><fieldset>';
        $filter.= '<select name="dateRange">';
        foreach ($dates as $key => $label) {
            $selected = $dateRange == $key ? 'selected' : '';
            $filter.= sprintf('<option value="%s" %s>%s</option>', $key, $selected, $label);
        }
        $filter.= '</select>';
        $filter .= '</fieldset>';


        $terms = get_terms(array(
            'taxonomy' => 'PracticeWEBContent',
            'hide_empty' => true,
        ));
        // $html.= "<xmp>".print_r($terms, TRUE)."</xmp>";

        $filter .= '<fieldset>';
        foreach ($terms as $term) {
            $checked = (in_array($term->term_id, $taxonomy)) ? 'checked' : '';
            $inputTemplate = '<input type="checkbox" name="taxonomy" value="%d" %s> %s';
            $filter .= sprintf($inputTemplate, $term->term_id, $checked, $term->name);
        }
        $filter .= '</fieldset>';


        $filter .= '<input class="deadlines-apply-filter" type="button" value="Apply">';
        $filter .= '</form></div>';

        $html .= $filter;

        $queryArgs = array(
            'dateRange' => $dateRange,
            'taxonomy' => $taxonomy,
        );
        //$html .= "<xmp>".print_r($queryArgs, TRUE). "</xmp>";
        $query = $this->query($queryArgs);
        global $post;
        $currentDate = null;
        $html .= '<div class="deadlines-list-container"><dl class="deadlines-list">';
        while ($query->have_posts()) {
            $query->the_post();
            $meta = get_post_meta($post->ID);
            $showDate = false;
            if ($currentDate !== $meta['deadlineDate'][0]) {
                $currentDate = $meta['deadlineDate'][0];
                $showDate = true;
            }
            //  echo "<xmp>". print_r($post, TRUE) . "</xmp>";
            //   echo "<xmp>". print_r($meta, TRUE) . "</xmp>";
            if ($showDate) {
                $html .= "<dt><h2>{$meta['deadlineDate'][0]}</h2></dt>";
            }
            $html .= "<dd>";
            $html .= "<h3>{$post->post_title}</h3>";
            $html .= "<blockquote>{$post->post_excerpt}</blockquote>";
            $html .= '</dd>';
        }
        $html .= "</dl></div>";
        $html .= "</div>";
        wp_reset_postdata();
        return $html;
    }

    /**
     * Helper to build the Wordpress query.
     *
     * @param array $args
     *   Array of arguments to use.
     *
     * @return \WP_Query
     *   Wordpress Query object.
     */
    public function query($args = array())
    {
        $queryArgs = array(
            'post_type' => 'deadlines',
            'post_status' => array('publish'),
            'posts_per_page' => -1,
            'paged' => 1,
            'meta_key' => 'deadlineDate',
            'orderby' => 'meta_value',
            'order' => 'ASC'
        );
        // Date range for query.
        if (isset($args['dateRange'])) {
            $dateBegin = '1970-01-01';
            $dateEnd = '2030-12-12';
            $now = new DateTimeImmutable();
            $dateFormat = 'Y-m-d';
            switch ($args['dateRange']) {
                case 'today':
                    $dateBegin = $now->format($dateFormat);
                    $dateEnd = $now->format($dateFormat);
                    break;
                case 'thisweek':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P1W'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'next2weeks':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P2W'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'thismonth':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P1M'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'next2months':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P2M'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'thisquarter':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P3M'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'next2quarters':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P6M'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'thisyear':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P1Y'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'next2years':
                    $dateBegin = $now->format($dateFormat);
                    $end = $now->add(new DateInterval('P2Y'));
                    $dateEnd = $end->format($dateFormat);
                    break;
                case 'lastweek':
                    $start = $now->sub(new DateInterval('P1W'));
                    $dateBegin = $start->format($dateFormat);
                    $dateEnd = $now->format($dateFormat);
                    break;
            }
            $queryArgs['meta_query'] = array(
                array(
                    'key' => 'deadlineDate',
                    'value' => array($dateBegin, $dateEnd),
                    'compare' => 'BETWEEN',
                    'type' => 'DATE',
                ),
            );
        }
        // Taxonomy query.
        if (isset($args['taxonomy']) && ! empty($args['taxonomy'])) {
            $queryArgs['tax_query'][] = array(
                'taxonomy' => 'PracticeWEBContent',
                'field' => 'term_id',
                'terms' => array_values($args['taxonomy']),
            );
        }
        $query = new \WP_Query($queryArgs);
        return $query;
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
     * Page view for upload.
     */
    public function uploadPage()
    {
        $vars = array();
        $this->renderTemplate('deadlines/upload', $vars);
    }

    /**
     * Submit handler for upload page.
     */
    public function uploadPageSubmit()
    {
        // TODO verify
        $uploaded = $_FILES['deadlinesfile'];
        $fh = fopen($uploaded['tmp_name'], 'r');
        // Assume that first line is header
        $header = fgetcsv($fh);
        while ($row = fgetcsv($fh)) {
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
                wp_set_object_terms($postId, $terms, 'PracticeWEBContent');
            }
        }
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
