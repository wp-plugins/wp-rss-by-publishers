<?php

// In order to activate this plugin, WP SlimStat needs to be installed and active
if (!in_array('wp-slimstat/wp-slimstat.php', get_option('active_plugins'))) return;

class wp_slimstat_custom_reports
{

    // Function: _get_time_spent_on_site
    // Description: Fetches popular pages from the DB
    // Input: none
    // Output: array of results
    // Notes: wp_slimstat_view::$filters_parsed is an array containing all the filters set by the user through the dropdown menus
    //        Please refer to readme.txt for a list of filters and to learn how to leverage this information in your queries

    static $postviews = 0;


    public static function show_custom_reports()
    {
        require_once('classes/wsys-stats.class.php');
        self::$postviews = wp_slimstat_db::count_records("tci.content_type = 'post'");

        self::show_publisher_posts_report();
        self::show_publisher_views_report();
        self::show_post_views_report();
    }

    public static function show_post_views_report()
    {
        wsys_stats::report_header('slim_p7_03', 'wide', '', 'Views by posts', false);
        wsys_stats::show_results('popular', 'slim_p1_08', 'resource', array('total_for_percentage' => self::$postviews, 'custom_where'=>"tci.content_type = 'post'"));
        wsys_stats::report_footer();
    }

    public static function show_publisher_views_report()
    {
        wsys_stats::report_header('slim_p7_04', 'normal', '', 'Views by publisher', false);
        wsys_stats::show_results('popular', 'slim_p1_08', 'author', array('total_for_percentage' => self::$postviews, 'custom_where'=>"tci.content_type = 'post'"));
        wsys_stats::report_footer();
    }
    public static function show_publisher_posts_report()
    {
        wp_slimstat_reports::report_header('slim_p7_05', 'wide', '', 'Posts by publisher', false);
        //wp_slimstat_reports::show_results('popular', 'slim_p1_08', 'author', array('total_for_percentage' => self::$postviews, 'custom_where'=>"tci.content_type = 'post'"));
        $publishers = WSYS_Publisher::get_stats(wp_slimstat_db::$filters_normalized['utime']['start'],wp_slimstat_db::$filters_normalized['utime']['end']);
        ?>
        <div class="inside" id="slim_p7_04_inside">
            <?php if(count($publishers)>0): ?>
            <p class="pagination">Results 1 - <?php echo count($publishers) ?> of <?php echo count($publishers) ?></p>
            <?php foreach($publishers as $publisher): ?>
            <p>
                <a class="slimstat-filter-link" href="admin.php?page=wp-slim-view-7&amp;fs%5Bauthor%5D=equals+<?php echo urlencode($publisher['name']) ?>&amp;fs%5Binterval_direction%5D=equals+minus&amp;fs%5Bis_past%5D=equals+"><?php echo $publisher['name'] ?></a>
                <span><?php echo $publisher['post_count'] ?></span>
                <b class="slimstat-row-details expanded">Hits: <?php echo $publisher['views'] ?></b>
            </p>
            <?php endforeach ?>
        <?php else: ?>
            <p style="text-align:center">No results</p>
        <?php endif ?>
        </div>
        <?php
        wp_slimstat_reports::report_footer();
    }
}

add_action('wp_slimstat_custom_report', array('wp_slimstat_custom_reports', 'show_custom_reports'));