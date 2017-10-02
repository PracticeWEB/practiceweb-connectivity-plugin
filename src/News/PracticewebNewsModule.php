<?php

namespace Sift\Practiceweb\Connectivity\News;

use \ET_Builder_Module_Blog;
use \ET_Builder_Element;

/**
 * Class PracticewebNewsModule
 * @package Sift\Practiceweb\Connectivity\News *
 */
class PracticewebNewsModule extends ET_Builder_Module_Blog {
    /**
     * Setup the News Module.
     */
    function init()
    {
        parent::init();
        $this->name = esc_html__('Practiceweb News', 'practiceweb');
        $this->slug = 'et_pb_pw_news';
        $this->fb_support = false;
    }

    /**
     * Update
     *
     * @return array|array[]
     */
    function get_fields()
    {
        $fields = parent::get_fields();
        $fields['__posts']['computed_callback'] = array(__CLASS__,'get_blog_posts');
        $fields['__page']['computed_callback'] = array(__CLASS__,'get_blog_posts');
        // This stops an upstream error
        $fields['__page']['computed_depends_on'] = array();
        return $fields;
    }


    static function get_blog_posts($args = array(), $conditional_tags = array(), $current_page = array())
    {
        global $paged, $post, $wp_query, $et_fb_processing_shortcode_object, $et_pb_rendering_column_content;

        $global_processing_original_value = $et_fb_processing_shortcode_object;

        // Default params are combination of attributes that is used by et_pb_blog and
        // conditional tags that need to be simulated (due to AJAX nature) by passing args
        $defaults = array(
            'fullwidth'                     => '',
            'posts_number'                  => '',
            'include_categories'            => '',
            'meta_date'                     => '',
            'show_thumbnail'                => '',
            'show_content'                  => '',
            'show_author'                   => '',
            'show_date'                     => '',
            'show_categories'               => '',
            'show_comments'                 => '',
            'show_pagination'               => '',
            'background_layout'             => '',
            'show_more'                     => '',
            'offset_number'                 => '',
            'masonry_tile_background_color' => '',
            'use_dropshadow'                => '',
            'overlay_icon_color'            => '',
            'hover_overlay_color'           => '',
            'hover_icon'                    => '',
            'use_overlay'                   => '',
        );

        // WordPress' native conditional tag is only available during page load. It'll fail during component update because
        // et_pb_process_computed_property() is loaded in admin-ajax.php. Thus, use WordPress' conditional tags on page load and
        // rely to passed $conditional_tags for AJAX call
        $is_front_page               = et_fb_conditional_tag( 'is_front_page', $conditional_tags );
        $is_search                   = et_fb_conditional_tag( 'is_search', $conditional_tags );
        $is_single                   = et_fb_conditional_tag( 'is_single', $conditional_tags );
        $et_is_builder_plugin_active = et_fb_conditional_tag( 'et_is_builder_plugin_active', $conditional_tags );

        $container_is_closed = false;

        // remove all filters from WP audio shortcode to make sure current theme doesn't add any elements into audio module
        remove_all_filters( 'wp_audio_shortcode_library' );
        remove_all_filters( 'wp_audio_shortcode' );
        remove_all_filters( 'wp_audio_shortcode_class');

        $args = wp_parse_args( $args, $defaults );

        $overlay_output = '';
        $hover_icon = '';

        if ( 'on' === $args['use_overlay'] ) {
            $data_icon = '' !== $args['hover_icon']
                ? sprintf(
                    ' data-icon="%1$s"',
                    esc_attr( et_pb_process_font_icon( $args['hover_icon'] ) )
                )
                : '';

            $overlay_output = sprintf(
                '<span class="et_overlay%1$s"%2$s></span>',
                ( '' !== $args['hover_icon'] ? ' et_pb_inline_icon' : '' ),
                $data_icon
            );
        }

        $overlay_class = 'on' === $args['use_overlay'] ? ' et_pb_has_overlay' : '';

        $query_args = array(
            'posts_per_page' => intval( $args['posts_number'] ),
            'post_status'    => 'publish',
        );

        if ( defined( 'DOING_AJAX' ) && isset( $current_page[ 'paged'] ) ) {
            $paged = intval( $current_page[ 'paged' ] );
        } else {
            $paged = $is_front_page ? get_query_var( 'page' ) : get_query_var( 'paged' );
        }

        // support pagination in VB
        if ( isset( $args['__page'] ) ) {
            $paged = $args['__page'];
        }

        if ( '' !== $args['include_categories'] ) {
            $query_args['cat'] = $args['include_categories'];
        }

        if ( ! $is_search ) {
            $query_args['paged'] = $paged;
        }

        if ( '' !== $args['offset_number'] && ! empty( $args['offset_number'] ) ) {
            /**
             * Offset + pagination don't play well. Manual offset calculation required
             * @see: https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
             */
            if ( $paged > 1 ) {
                $query_args['offset'] = ( ( $paged - 1 ) * intval( $args['posts_number'] ) ) + intval( $args['offset_number'] );
            } else {
                $query_args['offset'] = intval( $args['offset_number'] );
            }
        }

        if ( $is_single ) {
            $query_args['post__not_in'][] = get_the_ID();
        }

        $query_args['post_type'] = 'news';

        // Get query
        $query = new WP_Query( $query_args );

        // Keep page's $wp_query global
        $wp_query_page = $wp_query;

        // Turn page's $wp_query into this module's query
        $wp_query = $query;

        ob_start();

        if ( $query->have_posts() ) {
            if ( 'on' !== $args['fullwidth'] ) {
                echo '<div class="et_pb_salvattore_content" data-columns>';
            }

            while( $query->have_posts() ) {
                $query->the_post();
                global $et_fb_processing_shortcode_object;

                $global_processing_original_value = $et_fb_processing_shortcode_object;

                // reset the fb processing flag
                $et_fb_processing_shortcode_object = false;

                $thumb          = '';
                $width          = 'on' === $args['fullwidth'] ? 1080 : 400;
                $width          = (int) apply_filters( 'et_pb_blog_image_width', $width );
                $height         = 'on' === $args['fullwidth'] ? 675 : 250;
                $height         = (int) apply_filters( 'et_pb_blog_image_height', $height );
                $classtext      = 'on' === $args['fullwidth'] ? 'et_pb_post_main_image' : '';
                $titletext      = get_the_title();
                $thumbnail      = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
                $thumb          = $thumbnail["thumb"];
                $no_thumb_class = '' === $thumb || 'off' === $args['show_thumbnail'] ? ' et_pb_no_thumb' : '';

                $post_format = et_pb_post_format();
                if ( in_array( $post_format, array( 'video', 'gallery' ) ) ) {
                    $no_thumb_class = '';
                }

                // Print output
                ?>
                <article id="" <?php post_class( 'et_pb_post clearfix' . $no_thumb_class . $overlay_class ) ?>>
                    <?php
                    et_divi_post_format_content();

                    if ( ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) {
                        if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) :
                            $video_overlay = has_post_thumbnail() ? sprintf(
                                '<div class="et_pb_video_overlay" style="background-image: url(%1$s); background-size: cover;">
											<div class="et_pb_video_overlay_hover">
												<a href="#" class="et_pb_video_play"></a>
											</div>
										</div>',
                                $thumb
                            ) : '';

                            printf(
                                '<div class="et_main_video_container">
											%1$s
											%2$s
										</div>',
                                $video_overlay,
                                $first_video
                            );
                        elseif ( 'gallery' === $post_format ) :
                            et_pb_gallery_images( 'slider' );
                        elseif ( '' !== $thumb && 'on' === $args['show_thumbnail'] ) :
                            if ( 'on' !== $args['fullwidth'] ) echo '<div class="et_pb_image_container">'; ?>
                            <a href="<?php esc_url( the_permalink() ); ?>" class="entry-featured-image-url">
                                <?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
                                <?php if ( 'on' === $args['use_overlay'] ) {
                                    echo $overlay_output;
                                } ?>
                            </a>
                            <?php
                            if ( 'on' !== $args['fullwidth'] ) echo '</div> <!-- .et_pb_image_container -->';
                        endif;
                    }
                    ?>

                    <?php if ( 'off' === $args['fullwidth'] || ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) { ?>
                        <?php if ( ! in_array( $post_format, array( 'link', 'audio' ) ) ) { ?>
                            <h2 class="entry-title"><a href="<?php esc_url( the_permalink() ); ?>"><?php the_title(); ?></a></h2>
                        <?php } ?>

                        <?php
                        if ( 'on' === $args['show_author'] || 'on' === $args['show_date'] || 'on' === $args['show_categories'] || 'on' === $args['show_comments'] ) {
                            printf( '<p class="post-meta">%1$s %2$s %3$s %4$s %5$s %6$s %7$s</p>',
                                (
                                'on' === $args['show_author']
                                    ? et_get_safe_localization( sprintf( __( 'by %s', 'et_builder' ), '<span class="author vcard">' .  et_pb_get_the_author_posts_link() . '</span>' ) )
                                    : ''
                                ),
                                (
                                ( 'on' === $args['show_author'] && 'on' === $args['show_date'] )
                                    ? ' | '
                                    : ''
                                ),
                                (
                                'on' === $args['show_date']
                                    ? et_get_safe_localization( sprintf( __( '%s', 'et_builder' ), '<span class="published">' . esc_html( get_the_date( $args['meta_date'] ) ) . '</span>' ) )
                                    : ''
                                ),
                                (
                                (( 'on' === $args['show_author'] || 'on' === $args['show_date'] ) && 'on' === $args['show_categories'] )
                                    ? ' | '
                                    : ''
                                ),
                                (
                                'on' === $args['show_categories']
                                    ? get_the_category_list(', ')
                                    : ''
                                ),
                                (
                                (( 'on' === $args['show_author'] || 'on' === $args['show_date'] || 'on' === $args['show_categories'] ) && 'on' === $args['show_comments'])
                                    ? ' | '
                                    : ''
                                ),
                                (
                                'on' === $args['show_comments']
                                    ? sprintf( esc_html( _nx( '%s Comment', '%s Comments', get_comments_number(), 'number of comments', 'et_builder' ) ), number_format_i18n( get_comments_number() ) )
                                    : ''
                                )
                            );
                        }

                        $post_content = et_strip_shortcodes( et_delete_post_first_video( get_the_content() ), true );

                        // reset the fb processing flag
                        $et_fb_processing_shortcode_object = false;
                        // set the flag to indicate that we're processing internal content
                        $et_pb_rendering_column_content = true;
                        // reset all the attributes required to properly generate the internal styles
                        ET_Builder_Element::clean_internal_modules_styles();

                        echo '<div class="post-content">';

                        if ( 'on' === $args['show_content'] ) {
                            global $more;

                            // page builder doesn't support more tag, so display the_content() in case of post made with page builder
                            if ( et_pb_is_pagebuilder_used( get_the_ID() ) ) {
                                $more = 1;

                                echo apply_filters( 'the_content', $post_content );

                            } else {
                                $more = null;
                                echo apply_filters( 'the_content', et_delete_post_first_video( get_the_content( esc_html__( 'read more...', 'et_builder' ) ) ) );
                            }
                        } else {
                            if ( has_excerpt() ) {
                                the_excerpt();
                            } else {
                                if ( '' !== $post_content ) {
                                    // set the $et_fb_processing_shortcode_object to false, to retrieve the content inside truncate_post() correctly
                                    $et_fb_processing_shortcode_object = false;
                                    echo wpautop( et_delete_post_first_video( strip_shortcodes( truncate_post( 270, false, '', true ) ) ) );
                                    // reset the $et_fb_processing_shortcode_object to its original value
                                    $et_fb_processing_shortcode_object = $global_processing_original_value;
                                } else {
                                    echo '';
                                }
                            }
                        }

                        $et_fb_processing_shortcode_object = $global_processing_original_value;
                        // retrieve the styles for the modules inside Blog content
                        $internal_style = ET_Builder_Element::get_style( true );
                        // reset all the attributes after we retrieved styles
                        ET_Builder_Element::clean_internal_modules_styles( false );
                        $et_pb_rendering_column_content = false;
                        // append styles to the blog content
                        if ( $internal_style ) {
                            printf(
                                '<style type="text/css" class="et_fb_blog_inner_content_styles">
											%1$s
										</style>',
                                $internal_style
                            );
                        }

                        echo '</div>';

                        if ( 'on' !== $args['show_content'] ) {
                            $more = 'on' == $args['show_more'] ? sprintf( ' <a href="%1$s" class="more-link" >%2$s</a>' , esc_url( get_permalink() ), esc_html__( 'read more', 'et_builder' ) )  : '';
                            echo $more;
                        }
                        ?>
                    <?php } // 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote', 'gallery' ?>
                </article>
                <?php

                $et_fb_processing_shortcode_object = $global_processing_original_value;
            } // endwhile

            if ( 'on' !== $args['fullwidth'] ) {
                echo '</div>';
            }

            if ( 'on' === $args['show_pagination'] && ! $is_search ) {
                // echo '</div> <!-- .et_pb_posts -->'; // @todo this causes closing tag issue

                $container_is_closed = true;

                if ( function_exists( 'wp_pagenavi' ) ) {
                    wp_pagenavi( array(
                        'query' => $query
                    ) );
                } else {
                    if ( $et_is_builder_plugin_active ) {
                        include( ET_BUILDER_PLUGIN_DIR . 'includes/navigation.php' );
                    } else {
                        get_template_part( 'includes/navigation', 'index' );
                    }
                }
            }

            wp_reset_query();
        } else {
            if ( $et_is_builder_plugin_active ) {
                include( ET_BUILDER_PLUGIN_DIR . 'includes/no-results.php' );
            } else {
                get_template_part( 'includes/no-results', 'index' );
            }
        }

        wp_reset_postdata();

        // Reset $wp_query to its origin
        $wp_query = $wp_query_page;

        $posts = ob_get_contents();

        ob_end_clean();

        return $posts;
    }

    function shortcode_callback( $atts, $content = null, $function_name ) {
        /**
         * Cached $wp_filter so it can be restored at the end of the callback.
         * This is needed because this callback uses the_content filter / calls a function
         * which uses the_content filter. WordPress doesn't support nested filter
         */
        global $wp_filter;
        $wp_filter_cache = $wp_filter;

        $module_id           = $this->shortcode_atts['module_id'];
        $module_class        = $this->shortcode_atts['module_class'];
        $fullwidth           = $this->shortcode_atts['fullwidth'];
        $posts_number        = $this->shortcode_atts['posts_number'];
        $include_categories  = $this->shortcode_atts['include_categories'];
        $meta_date           = $this->shortcode_atts['meta_date'];
        $show_thumbnail      = $this->shortcode_atts['show_thumbnail'];
        $show_content        = $this->shortcode_atts['show_content'];
        $show_author         = $this->shortcode_atts['show_author'];
        $show_date           = $this->shortcode_atts['show_date'];
        $show_categories     = $this->shortcode_atts['show_categories'];
        $show_comments       = $this->shortcode_atts['show_comments'];
        $show_pagination     = $this->shortcode_atts['show_pagination'];
        $background_layout   = $this->shortcode_atts['background_layout'];
        $show_more           = $this->shortcode_atts['show_more'];
        $offset_number       = $this->shortcode_atts['offset_number'];
        $masonry_tile_background_color = $this->shortcode_atts['masonry_tile_background_color'];
        $use_dropshadow      = $this->shortcode_atts['use_dropshadow'];
        $overlay_icon_color  = $this->shortcode_atts['overlay_icon_color'];
        $hover_overlay_color = $this->shortcode_atts['hover_overlay_color'];
        $hover_icon          = $this->shortcode_atts['hover_icon'];
        $use_overlay         = $this->shortcode_atts['use_overlay'];

        global $paged;

        $module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

        $container_is_closed = false;

        // some themes do not include these styles/scripts so we need to enqueue them in this module to support audio post format
        wp_enqueue_style( 'wp-mediaelement' );
        wp_enqueue_script( 'wp-mediaelement' );

        // remove all filters from WP audio shortcode to make sure current theme doesn't add any elements into audio module
        remove_all_filters( 'wp_audio_shortcode_library' );
        remove_all_filters( 'wp_audio_shortcode' );
        remove_all_filters( 'wp_audio_shortcode_class');

        if ( '' !== $masonry_tile_background_color ) {
            ET_Builder_Element::set_style( $function_name, array(
                'selector'    => '%%order_class%%.et_pb_blog_grid .et_pb_post',
                'declaration' => sprintf(
                    'background-color: %1$s;',
                    esc_html( $masonry_tile_background_color )
                ),
            ) );
        }

        if ( '' !== $overlay_icon_color ) {
            ET_Builder_Element::set_style( $function_name, array(
                'selector'    => '%%order_class%% .et_overlay:before',
                'declaration' => sprintf(
                    'color: %1$s !important;',
                    esc_html( $overlay_icon_color )
                ),
            ) );
        }

        if ( '' !== $hover_overlay_color ) {
            ET_Builder_Element::set_style( $function_name, array(
                'selector'    => '%%order_class%% .et_overlay',
                'declaration' => sprintf(
                    'background-color: %1$s;',
                    esc_html( $hover_overlay_color )
                ),
            ) );
        }

        if ( 'on' === $use_overlay ) {
            $data_icon = '' !== $hover_icon
                ? sprintf(
                    ' data-icon="%1$s"',
                    esc_attr( et_pb_process_font_icon( $hover_icon ) )
                )
                : '';

            $overlay_output = sprintf(
                '<span class="et_overlay%1$s"%2$s></span>',
                ( '' !== $hover_icon ? ' et_pb_inline_icon' : '' ),
                $data_icon
            );
        }

        $overlay_class = 'on' === $use_overlay ? ' et_pb_has_overlay' : '';

        if ( 'on' !== $fullwidth ){
            if ( 'on' === $use_dropshadow ) {
                $module_class .= ' et_pb_blog_grid_dropshadow';
            }

            wp_enqueue_script( 'salvattore' );

            $background_layout = 'light';
        }

        $args = array( 'posts_per_page' => (int) $posts_number );

        $et_paged = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );

        if ( is_front_page() ) {
            $paged = $et_paged;
        }

        if ( '' !== $include_categories )
            $args['cat'] = $include_categories;

        if ( ! is_search() ) {
            $args['paged'] = $et_paged;
        }

        if ( '' !== $offset_number && ! empty( $offset_number ) ) {
            /**
             * Offset + pagination don't play well. Manual offset calculation required
             * @see: https://codex.wordpress.org/Making_Custom_Queries_using_Offset_and_Pagination
             */
            if ( $paged > 1 ) {
                $args['offset'] = ( ( $et_paged - 1 ) * intval( $posts_number ) ) + intval( $offset_number );
            } else {
                $args['offset'] = intval( $offset_number );
            }
        }

        if ( is_single() && ! isset( $args['post__not_in'] ) ) {
            $args['post__not_in'] = array( get_the_ID() );
        }

        $args['post_type'] = 'news';

        ob_start();

        query_posts( $args );

        if ( have_posts() ) {
            if ( 'off' === $fullwidth ) {
                echo '<div class="et_pb_salvattore_content" data-columns>';
            }

            while ( have_posts() ) {
                the_post();

                $post_format = et_pb_post_format();

                $thumb = '';

                $width = 'on' === $fullwidth ? 1080 : 400;
                $width = (int) apply_filters( 'et_pb_blog_image_width', $width );

                $height = 'on' === $fullwidth ? 675 : 250;
                $height = (int) apply_filters( 'et_pb_blog_image_height', $height );
                $classtext = 'on' === $fullwidth ? 'et_pb_post_main_image' : '';
                $titletext = get_the_title();
                $thumbnail = get_thumbnail( $width, $height, $classtext, $titletext, $titletext, false, 'Blogimage' );
                $thumb = $thumbnail["thumb"];

                $no_thumb_class = '' === $thumb || 'off' === $show_thumbnail ? ' et_pb_no_thumb' : '';

                if ( in_array( $post_format, array( 'video', 'gallery' ) ) ) {
                    $no_thumb_class = '';
                } ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class( 'et_pb_post clearfix' . $no_thumb_class . $overlay_class  ); ?>>

                    <?php
                    et_divi_post_format_content();

                    if ( ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) {
                        if ( 'video' === $post_format && false !== ( $first_video = et_get_first_video() ) ) :
                            $video_overlay = has_post_thumbnail() ? sprintf(
                                '<div class="et_pb_video_overlay" style="background-image: url(%1$s); background-size: cover;">
								<div class="et_pb_video_overlay_hover">
									<a href="#" class="et_pb_video_play"></a>
								</div>
							</div>',
                                $thumb
                            ) : '';

                            printf(
                                '<div class="et_main_video_container">
								%1$s
								%2$s
							</div>',
                                $video_overlay,
                                $first_video
                            );
                        elseif ( 'gallery' === $post_format ) :
                            et_pb_gallery_images( 'slider' );
                        elseif ( '' !== $thumb && 'on' === $show_thumbnail ) :
                            if ( 'on' !== $fullwidth ) echo '<div class="et_pb_image_container">'; ?>
                            <a href="<?php esc_url( the_permalink() ); ?>" class="entry-featured-image-url">
                                <?php print_thumbnail( $thumb, $thumbnail["use_timthumb"], $titletext, $width, $height ); ?>
                                <?php if ( 'on' === $use_overlay ) {
                                    echo $overlay_output;
                                } ?>
                            </a>
                            <?php
                            if ( 'on' !== $fullwidth ) echo '</div> <!-- .et_pb_image_container -->';
                        endif;
                    } ?>

                    <?php if ( 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote' ) ) ) { ?>
                        <?php if ( ! in_array( $post_format, array( 'link', 'audio' ) ) ) { ?>
                            <h2 class="entry-title"><a href="<?php esc_url( the_permalink() ); ?>"><?php the_title(); ?></a></h2>
                        <?php } ?>

                        <?php
                        if ( 'on' === $show_author || 'on' === $show_date || 'on' === $show_categories || 'on' === $show_comments ) {
                            printf( '<p class="post-meta">%1$s %2$s %3$s %4$s %5$s %6$s %7$s</p>',
                                (
                                'on' === $show_author
                                    ? et_get_safe_localization( sprintf( __( 'by %s', 'et_builder' ), '<span class="author vcard">' .  et_pb_get_the_author_posts_link() . '</span>' ) )
                                    : ''
                                ),
                                (
                                ( 'on' === $show_author && 'on' === $show_date )
                                    ? ' | '
                                    : ''
                                ),
                                (
                                'on' === $show_date
                                    ? et_get_safe_localization( sprintf( __( '%s', 'et_builder' ), '<span class="published">' . esc_html( get_the_date( $meta_date ) ) . '</span>' ) )
                                    : ''
                                ),
                                (
                                (( 'on' === $show_author || 'on' === $show_date ) && 'on' === $show_categories)
                                    ? ' | '
                                    : ''
                                ),
                                (
                                'on' === $show_categories
                                    ? get_the_category_list(', ')
                                    : ''
                                ),
                                (
                                (( 'on' === $show_author || 'on' === $show_date || 'on' === $show_categories ) && 'on' === $show_comments)
                                    ? ' | '
                                    : ''
                                ),
                                (
                                'on' === $show_comments
                                    ? sprintf( esc_html( _nx( '%s Comment', '%s Comments', get_comments_number(), 'number of comments', 'et_builder' ) ), number_format_i18n( get_comments_number() ) )
                                    : ''
                                )
                            );
                        }

                        echo '<div class="post-content">';
                        global $et_pb_rendering_column_content;

                        $post_content = et_strip_shortcodes( et_delete_post_first_video( get_the_content() ), true );

                        $et_pb_rendering_column_content = true;

                        if ( 'on' === $show_content ) {
                            global $more;

                            // page builder doesn't support more tag, so display the_content() in case of post made with page builder
                            if ( et_pb_is_pagebuilder_used( get_the_ID() ) ) {
                                $more = 1;
                                echo apply_filters( 'the_content', $post_content );
                            } else {
                                $more = null;
                                echo apply_filters( 'the_content', et_delete_post_first_video( get_the_content( esc_html__( 'read more...', 'et_builder' ) ) ) );
                            }
                        } else {
                            if ( has_excerpt() ) {
                                the_excerpt();
                            } else {
                                echo wpautop( et_delete_post_first_video( strip_shortcodes( truncate_post( 270, false, '', true ) ) ) );
                            }
                        }

                        $et_pb_rendering_column_content = false;

                        if ( 'on' !== $show_content ) {
                            $more = 'on' == $show_more ? sprintf( ' <a href="%1$s" class="more-link" >%2$s</a>' , esc_url( get_permalink() ), esc_html__( 'read more', 'et_builder' ) )  : '';
                            echo $more;
                        }

                        echo '</div>';
                        ?>
                    <?php } // 'off' === $fullwidth || ! in_array( $post_format, array( 'link', 'audio', 'quote', 'gallery' ?>

                </article> <!-- .et_pb_post -->
                <?php
            } // endwhile

            if ( 'off' === $fullwidth ) {
                echo '</div><!-- .et_pb_salvattore_content -->';
            }

            if ( 'on' === $show_pagination && ! is_search() ) {
                if ( function_exists( 'wp_pagenavi' ) ) {
                    wp_pagenavi();
                } else {
                    if ( et_is_builder_plugin_active() ) {
                        include( ET_BUILDER_PLUGIN_DIR . 'includes/navigation.php' );
                    } else {
                        get_template_part( 'includes/navigation', 'index' );
                    }
                }

                echo '</div> <!-- .et_pb_posts -->';

                $container_is_closed = true;
            }
        } else {
            if ( et_is_builder_plugin_active() ) {
                include( ET_BUILDER_PLUGIN_DIR . 'includes/no-results.php' );
            } else {
                get_template_part( 'includes/no-results', 'index' );
            }
        }

        wp_reset_query();

        $posts = ob_get_contents();

        ob_end_clean();

        $class = " et_pb_module et_pb_bg_layout_{$background_layout}";

        $output = sprintf(
            '<div%5$s class="%1$s%3$s%6$s">
				<div class="et_pb_ajax_pagination_container">
					%2$s
				</div>
			%4$s',
            ( 'on' === $fullwidth ? 'et_pb_posts' : 'et_pb_blog_grid clearfix' ),
            $posts,
            esc_attr( $class ),
            ( ! $container_is_closed ? '</div> <!-- .et_pb_posts -->' : '' ),
            ( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
            ( '' !== $module_class ? sprintf( ' %1$s', esc_attr( $module_class ) ) : '' )
        );

        if ( 'on' !== $fullwidth )
            $output = sprintf( '<div class="et_pb_blog_grid_wrapper">%1$s</div>', $output );

        // Restore $wp_filter
        $wp_filter = $wp_filter_cache;
        unset($wp_filter_cache);

        return $output;
    }
}