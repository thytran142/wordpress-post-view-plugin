<?php
/**
 * Plugin Name:       Study Plugin
 * Plugin URI:        https://vanntechs.com/
 * Description:       Learning journey from Vanntechs
 * Version:           1.10.3
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            Vannesa Tran
 * Author URI:        https://vanntechs.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
*/
###Create text domain for Translations
add_action('plugins_loaded', 'vanntechs_postviews_textdomain');
function vanntechs_postviews_textdomain() {
    load_plugin_textdomain('vanntechs-wp-postviews', false, dirname(plugin_basename(__FILE__)));
}

### Functions: Post Views Option Menu
add_action('admin_menu', 'vanntechs_postviews_menu');
function vanntechs_postviews_menu() {
    if (function_exists('add_options_page')) {
        add_options_page(__('PostViews', 'vanntechs-wp-postviews'), __('PostViews', 'vanntechs-wp-postviews'), 'manage_options', 'study-plugin/postviews-options.php');
    }
}

### Function calculate post views
add_action('wp_head', 'process_postviews');
function process_postviews() {
    global $user_ID, $post;
    if (is_int($post)) {
        $post = get_post($post);
    }
    if (!wp_is_post_revision($post) && !is_preview())
    {
        if (is_single() || is_page()) {
            $id = (int) $post->ID;
            $views_options = get_option('views_options');
            if (!$post_views = get_post_meta($post->ID, 'views', true)) {
                $post_views = 0;
            }
            $should_count = false;
            switch((int) $views_options['count']) {
                case 0:
                    $should_count = true;
                    break;
                case 1:
                    if (empty($_COOKIE[USER_COOKIE]) && (int)$user_ID == 0) {
                        $should_count = true;
                    }
                    break;
                case 2:
                    if ((int)$user_ID > 0) {
                        $should_count = true;
                    }
                    break;
            }
            if (isset($views_options['exclude_bots']) && (int)$views_options['exclude_bots'] == 1) {
                $bots = array(
                    'Google Bot' => 'google',
                    'MSN' => 'msnbot',
                 'Alex' => 'ia_archiver'
                , 'Lycos' => 'lycos'
                , 'Ask Jeeves' => 'jeeves'
                , 'Altavista' => 'scooter'
                , 'AllTheWeb' => 'fast-webcrawler'
                , 'Inktomi' => 'slurp@inktomi'
                , 'Turnitin.com' => 'turnitinbot'
                , 'Technorati' => 'technorati'
                , 'Yahoo' => 'yahoo'
                , 'Findexa' => 'findexa'
                , 'NextLinks' => 'findlinks'
                , 'Gais' => 'gaisbo'
                , 'WiseNut' => 'zyborg'
                , 'WhoisSource' => 'surveybot'
                , 'Bloglines' => 'bloglines'
                , 'BlogSearch' => 'blogsearch'
                , 'PubSub' => 'pubsub'
                , 'Syndic8' => 'syndic8'
                , 'RadioUserland' => 'userland'
                , 'Gigabot' => 'gigabot'
                , 'Become.com' => 'become.com'
                , 'Baidu' => 'baiduspider'
                , 'so.com' => '360spider'
                , 'Sogou' => 'spider'
                , 'soso.com' => 'sosospider'
                , 'Yandex' => 'yandex'
                );
                $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT']: '';
                foreach ($bots as $name => $lookfor) {
                    if (!empty($useragent) && (false !== stripos($useragent, $lookfor))) {
                        $should_count = false;
                        break;
                    }
                }
            }
            $should_count = apply_filters('postviews_should_count', $should_count, $id);
            if ($should_count &&
                ((isset($views_options['use_ajax']) && (int)$views_options['use_ajax'] == 0)
                    || (!defined('WP_CACHE') || !WP_CACHE))) {
                update_post_meta($id, 'views', $post_views + 1);
                do_action('postviews_increment_views', $post_views + 1);
            }
        }
    }
}

### Function: Calculate Post Views with WP_CACHE Enabled
add_action('wp_enqueue_scripts', 'vanntechs_wp_postview_cache_count_enqueue');
function vanntechs_wp_postview_cache_count_enqueue() {
    global $user_ID, $post;

    if (!defined('WP_CACHE') || !WP_CACHE) {
        return ;
    }

    $views_options = get_option('view_options');
    if (isset($views_options['use_ajax']) && (int) $views_options['use_ajax'] == 0) {
        return ;
    }
    if (!wp_is_post_revision($post) && (is_single() || is_page())) {
        $should_count = false;
        switch ((int) $views_options['count']) {
            case 0:
                $should_count = true;
                break;
            case 1:
                if (empty($_COOKIE[USER_COOKIE]) && (int) $user_ID == 0) {
                    $should_count = true;
                }
                break;
            case 2:
                if ((int)$user_ID > 0) {
                    $should_count = true;
                }
                break;
        }
        $should_count = apply_filters('postviews_should_count', $should_count, (int)$post->ID);
        if ($should_count) {
            wp_enqueue_script('wp-postviews-cache', plugins_url('postview-cache.js', __FILE__), array('jquery'), '1.0', true);
            wp_localize_script('wp-postviews-cache', 'viewsCacheL10n', array('admin_ajax_url' => admin_url('admin-ajax.php'), 'post_id' => (int)$post->ID));
        }
    }
}

###Function: Determine if Post Views should be displayed
function should_views_be_displayed($view_options = null) {
    if ($view_options == null) {
        $view_options = get_option('views_option');
    }
    $display_option = 0;
    if (is_home()) {
        if (array_key_exists('display_home', $view_options)) {
            $display_option = $view_options['display_home'];
        }
    }elseif(is_single()) {
        if (array_key_exists('display_single', $view_options)) {
            $display_option = $view_options['display_single'];
        }
    }elseif(is_page()) {
        if (array_key_exists('display_page', $view_options)) {
            $display_option = $view_options['display_page'];
        }
    } elseif(is_archive()) {
        if (array_key_exists('display_archive', $view_options)) {
            $display_option = $view_options['display_archive'];
        }
    } elseif(is_search()) {
        if (array_key_exists('display_search', $view_options)) {
            $display_option = $view_options['display_search'];
        }
    }else {
        if (array_key_exists('display_other', $view_options)) {
            $display_option = $view_options['display_other'];
        }
    }
    return (($display_option == 0) || (($display_option == 1) && is_user_logged_in()));
}
### Function: Display The Post Views
function the_views($display = true, $prefix = '', $postfix = '', $always = false) {
    $post_views = (int) get_post_meta(get_the_ID(), 'views', true);
    $views_options = get_option('views_options');
    if ($always || should_views_be_displayed($views_options)) {
        $output = $prefix.str_replace( array( '%VIEW_COUNT%', '%VIEW_COUNT_ROUNDED%' ), array( number_format_i18n( $post_views ), postviews_round_number( $post_views) ), stripslashes( $views_options['template'] ) ).$postfix;
        if($display) {
            echo apply_filters('the_views', $output);
        } else {
            return apply_filters('the_views', $output);
        }
    } elseif (!$display) {
        return '';
    }
}
###Function: ShortCode for Inserting Views Into Posts
add_shortcode('views', 'views_shortcode');
function views_shortcode($atts) {
    $attributes = shortcode_atts(array('id' => 0), $atts);
    $id = (int)$attributes['id'];
    if ($id == 0) {
        $id = get_the_ID();
    }
    $views_options = get_option('views_options');
    $post_views = (int)get_post_meta($id, 'views', true);
    $output = str_replace( array( '%VIEW_COUNT%', '%VIEW_COUNT_ROUNDED%' ), array( number_format_i18n( $post_views ), postviews_round_number( $post_views) ), stripslashes( $views_options['template'] ) );

    return apply_filters( 'the_views', $output );
}

###Function: Display Least Viewed page/post
if (!function_exists('get_least_viewed')) {
    function get_least_viewed($mode = '', $limit = 10, $chars = 0, $display = true) {
        $views_options = get_option('views_options');
        $output = '';

        $least_viewed = new WP_Query(array(
            'post_type' => (empty($mode) || $mode == 'both' ) ? 'any' : $mode,
            'posts_per_page' => $limit,
            'orderby' => 'meta_value_num',
            'order' => 'asc',
            'meta_key' => 'views'
        ));
        if ($least_viewed->have_posts()) {
            while($least_viewed->have_posts()) {
                $least_viewed->the_post();

                // Post Views
                $post_views = get_post_meta(get_the_ID(), 'views', true);

                // Post Title
                $post_title = get_the_title();
                if ($chars > 0) {
                    $post_title = snippet_text($post_title, $chars);
                }
                $categories = get_the_category();
                $post_category_id = 0;
                if (!empty($categories)) {
                    $post_category_id = $categories[0]->term_id;
                }

                $temp = stripslashes($views_options['most_viewed_template']);
                $temp = str_replace( '%VIEW_COUNT%', number_format_i18n( $post_views ), $temp );
                $temp = str_replace( '%VIEW_COUNT_ROUNDED%', postviews_round_number( $post_views ), $temp );
                $temp = str_replace( '%POST_TITLE%', $post_title, $temp );
                $temp = str_replace( '%POST_EXCERPT%', get_the_excerpt(), $temp );
                $temp = str_replace( '%POST_CONTENT%', get_the_content(), $temp );
                $temp = str_replace( '%POST_URL%', get_permalink(), $temp );
                $temp = str_replace( '%POST_DATE%', get_the_time( get_option( 'date_format' ) ), $temp );
                $temp = str_replace( '%POST_TIME%', get_the_time( get_option( 'time_format' ) ), $temp );
                $temp = str_replace( '%POST_THUMBNAIL%', get_the_post_thumbnail( null,'thumbnail',true ), $temp);
                $temp = str_replace( '%POST_CATEGORY_ID%', $post_category_id, $temp );
                $temp = str_replace( '%POST_AUTHOR%', get_the_author(), $temp );
                $output .= $temp;
            }
            wp_reset_postdata();
        } else {
            $output = '<li>'.__('N/A', 'vanntechs-wp-postviews').'</li>'."\n";
        }
        if ($display) {
            echo $output;
        } else {
            return $output;
        }
    }
}

###Function: Display Most Viewed Post/Page
if (!function_exists('get_most_viewed')) {
    function get_most_viewed($mode = '', $limit = 10, $chars = 0, $display = true) {
        $views_options = get_option('views_options');

    }
}