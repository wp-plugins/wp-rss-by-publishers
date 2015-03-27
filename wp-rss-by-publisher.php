<?php
/**
 * Plugin Name: WP RSS By Publishers
 * Plugin URI: http://www.wisesystems.co/wp-rss-by-publishers/
 * Description: Create publishers and add RSS feeds to aggregate for each of them. Further, apply custom rules to direct content to different categories at a feed level or a publisher level. Also, track the content pulled from each publisher.
 * Version: 0.1
 * Author: Wise Systems
 * Author URI: http://wisesystems.co
 * License: Example: Wise Systems License 1.0
 */

include_once("config.php");

if(!class_exists('WP_WsysAdmin'))
{
    class WP_WsysAdmin
    {
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_menu'));
            add_action( 'add_meta_boxes', array(&$this, 'add_meta_box'));
            add_action( 'save_post', array(&$this, 'save'));
            add_filter( 'parse_query', array(&$this, 'posts_filter'));
            add_action( 'restrict_manage_posts', array(&$this, 'posts_filter_restrict_manage_posts'));
            //include plugin classes
            require_once('classes/wsys-db.class.php');
            require_once('classes/wsys-publisher.php');
            require_once('classes/wsys-publisher-table.php');
            require_once('classes/wsys-feed.php');
            require_once('classes/wsys-feed-log.php');
            require_once('classes/wsys-feed-table.php');
            require_once('classes/wsys-rule.php');
            require_once('classes/feedwordpress/magpiemocklink.class.php');
            require_once('classes/feedwordpress/feedfinder.class.php');
        }

        /**
         * Activate the plugin
         */
        public static function activate()
        {
            global $wpdb;
            $new_db = 8;
            $old_db = get_option('wsys_db_version');
            if(!$old_db) {
                $old_db = 0;
            }
            elseif($old_db=='0.2') {
                if(update_option('wsys_db_version',2)) {
                    $old_db = 2;
                }
                else {
                    wp_die('Could not update plugin db version');
                }
            }
            if($old_db!=$new_db) {
                if(glob(plugins_url('sql/*.sql', __FILE__))) {
                    $files = array();
                    foreach(glob(plugins_url('sql/*.sql', __FILE__)) as $file) {
                        $files[basename($file,'.sql')] = $file;
                    }
                    if(count($files)>0) {
                        $log = array();
                        $error = false;
                        $wpdb->query('start transaction');
                        for($i=$old_db+1;$i<=$new_db;$i++) {
                            if(isset($files[$i])) {
                                $sql = file_get_contents($files[$i]);
                                // make sure table names match the ones in the classes
                                $sql = str_replace('`wsys_publisher`','`'.WSYS_Publisher::$table_name.'`',$sql);
                                $sql = str_replace('`wsys_feed`','`'.WSYS_Feed::$table_name.'`',$sql);
                                $sql = str_replace('`wsys_feed_log`','`'.WSYS_Feed_log::$table_name.'`',$sql);
                                $sql = str_replace('`wsys_rule`','`'.WSYS_Rule::$table_name.'`',$sql);
                                $queries = explode( ';', $sql );
                                foreach($queries as $query) {
                                    if(trim($query)) {
                                        echo trim($query)."\n";
                                        $res = $wpdb->query(trim($query));
                                        if($res===false) {
                                            $log[] = $wpdb->last_error;
                                            $wpdb->query('rollback');
                                            $error = true;
                                        }
                                    }
                                }
                            }
                        }
                        if(!$error) {
                            $wpdb->query('commit');
                            update_option('wsys_db_version',$new_db);
                        }
                        else {
                            wp_die(implode("\n",$log));
                        }
                    }
                }
            }

            // check if guid index on wp_posts exists
            $count = $wpdb->get_var("select count(1) cnt FROM INFORMATION_SCHEMA.STATISTICS WHERE table_name='$wpdb->posts' and index_name = 'guid'");
            if($count==0) {
                $wpdb->query("ALTER TABLE $wpdb->posts ADD INDEX `guid` (`guid` ASC)");
            }

            wp_schedule_event( time(), 'hourly', 'hourly_events' );
        }

        /**
         * Deactivate the plugin
         */
        public static function deactivate()
        {
            // should drop all tables ?
            wp_clear_scheduled_hook('hourly_events');
        }

        public function admin_init()
        {
            // Set up the settings for this plugin
            $this->init_settings();
            // Possibly do additional admin_init tasks
            wp_enqueue_script('media-upload');
            wp_enqueue_script('thickbox');
            wp_enqueue_script('jquery');
            wp_enqueue_script('wsysadmin', plugins_url('js/admin.js', __FILE__ ), array('jquery'), '1.0.0', true );
            wp_enqueue_style('thickbox');
            wp_enqueue_style('wp-rss-by-publisher', plugins_url('css/main.css', __FILE__ ));
        }

        public function init_settings()
        {
            // register the settings for this plugin
            //register_setting('wp_plugin_template-group', 'setting_a');
            //register_setting('wp_plugin_template-group', 'setting_b');
            register_setting( 'wsys-settings-group', 'wsys-post-image-min-width' );
            register_setting( 'wsys-settings-group', 'wsys-post-image-min-height' );
            register_setting( 'wsys-settings-group', 'wsys-post-utm_source' );
            register_setting( 'wsys-settings-group', 'wsys-post-redirect' );
            register_setting( 'wsys-settings-group', 'wsys-ads-skip' );
            add_settings_section( 'general-settings', null, array(&$this,'wsys_settings_callback'), 'wsysadmin_settings' );
            //add_settings_section( 'post-settings', 'Post settings', array(&$this,'wsys_post_settings_callback'), 'wsysadmin_settings' );
            add_settings_field( 'wsys-post-image-min-width', 'Min width', array(&$this,'min_width_callback'), 'wsysadmin_settings', 'general-settings' );
            add_settings_field( 'wsys-post-image-min-height', 'Min height', array(&$this,'min_height_callback'), 'wsysadmin_settings', 'general-settings' );
            add_settings_field( 'wsys-post-utm_source', 'UTM Source', array(&$this,'utm_source_callback'), 'wsysadmin_settings', 'general-settings' );
            add_settings_field( 'wsys-post-redirect', 'Redirect', array(&$this,'redirect_callback'), 'wsysadmin_settings', 'general-settings' );
//            add_settings_field( 'wsys-post-skip', 'Skip posts', array(&$this,'skip_callback'), 'wsysadmin_settings', 'general-settings' );
        }

        public function add_menu()
        {
            global $submenu;
            $hook = add_menu_page('Publishers', 'Publishers', 'manage_options', 'wsysadmin_publishers', array(&$this, 'action'),'dashicons-rss');
            add_action( "load-$hook", array(&$this, 'add_options'));
            add_submenu_page('wsysadmin_publishers','Add publisher', 'Add publisher', 'manage_options', 'wsysadmin_add_publisher', array(&$this, 'action'));
            add_submenu_page(null,'Feeds', 'Feeds', 'manage_options', 'wsysadmin_feeds', array(&$this, 'action'));
            add_submenu_page(null,'Rules', 'Rules', 'manage_options', 'wsysadmin_rules', array(&$this, 'action'));
            add_submenu_page(null,'Admin', 'Admin', 'manage_options', 'wsysadmin', array(&$this, 'action'));
            add_submenu_page('wsysadmin_publishers','Settings', 'Settings', 'manage_options', 'wsysadmin_settings', array(&$this, 'settings'),'dashicons-admin-generic');
            $submenu['wsysadmin_publishers'][0][0] = 'All publishers';
        }

        function add_options() {
            global $publisher_table;
            $publisher_table = new WSYS_Publisher_Table();
        }

        public function set_option($status, $option, $value) {
            if ( 'books_per_page' == $option ) return $value;
            return $status;
        }

        public function check_permission() {
            if(!current_user_can('manage_options'))
            {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
        }

        public function settings() {
            $this->check_permission();
            ?>
            <div class="wrap">
                <h2>Settings</h2>
                <form action="options.php" method="POST">
                    <?php settings_fields( 'wsys-settings-group' ); ?>
                    <?php do_settings_sections( 'wsysadmin_settings' ); ?>
                    <?php submit_button(); ?>
                </form>
            </div>
        <?php
        }

        function wsys_settings_callback() {
        }

        function wsys_post_settings_callback() {
            //echo '';
        }
        function wsys_ads_settings_callback() {
            //echo '';
        }

        function min_width_callback() {
            $setting = esc_attr( get_option( 'wsys-post-image-min-width' ) );
            echo '<input type="text" name="wsys-post-image-min-width" value="'.$setting.'" /> <span>Minimum dimension for featured images in posts.</span>';
        }
        function min_height_callback() {
            $setting = esc_attr( get_option( 'wsys-post-image-min-height' ) );
            echo '<input type="text" name="wsys-post-image-min-height" value="'.$setting.'" /> <span>Minimum dimension for featured images in posts.</span>';
        }

        function utm_source_callback() {
            $setting = esc_attr( get_option( 'wsys-post-utm_source' ) );
            echo '<input type="text" name="wsys-post-utm_source" value="'.$setting.'" /> <span>utm_source parameter that is appended to post urls</span>';
        }

        function redirect_callback() {
            $setting = esc_attr( get_option( 'wsys-post-redirect' ) );
            echo '<label for="wsys-post-redirect"><input type="checkbox" id="wsys-post-redirect" name="wsys-post-redirect" '.($setting ? 'checked=""' : '').' /> Redirect to original post</label>';
        }

        function skip_callback() {
            $setting = esc_attr( get_option( 'wsys-ads-skip', 3 ) );
            echo '<input type="text" name="wsys-ads-skip" value="'.$setting.'" /> <span>No of posts between ads</span>';
        }

        public function action() {
            $this->check_permission();
            if(isset($_GET['page'])) {
                switch ($_GET['page']) {
                    case 'wsysadmin_add_publisher':
                        $_GET['action'] = 'add';
                    case 'wsysadmin_publishers':
                        if (isset($_GET['action'])) {
                            switch ($_GET['action']) {
                                case 'add':
                                    if (isset($_POST['save'])) {
                                        // Saved in the save_publisher() function
                                    }
                                    include('templates/edit-publisher.php');
                                    break;
                                case 'edit':
                                    if (!isset($_GET['id'])) {
                                        wp_die(__('Missing id.'));
                                    }
                                    $item = WSYS_Publisher::get(sanitize_text_field($_GET['id']));
                                    if (!$item) {
                                        wp_die(__('Publisher not found.'));
                                    }

                                    // Saved by save_publisher() function

                                    $feed_table = new WSYS_Feed_Table($item['id']);
                                    $rules = WSYS_Rule::get_items(array(array('field'=>'publisher_id', 'value'=>$item['id'])));
                                    include('templates/edit-publisher.php');
                                    break;
                                default:
                                    global $publisher_table;
                                    include('templates/publishers.php');
                                    break;
                            }
                        } else {
                            global $publisher_table;
                            include('templates/publishers.php');
                            break;
                        }
                        break;
                    case 'wsysadmin_feeds':
                        if (isset($_GET['action'])) {
                            switch ($_GET['action']) {
                                case 'add':
                                    $publisher_id = sanitize_text_field($_GET['publisher_id']);
                                    $publisher = WSYS_Publisher::get($publisher_id);
                                    if(!$publisher) {
                                        wp_die(__('Publisher not found.'));
                                    }
                                    $current_feeds = WSYS_Feed::get_items(array(array('field'=>'publisher_id','value'=>$publisher_id)));
                                    $subscribed_feeds = array();
                                    foreach($current_feeds as $current_feed) {
                                        $subscribed_feeds[] = $current_feed['url'];
                                    }
                                    if(isset($_GET['url']) && $_GET['url']!='URL') {
                                        $url = sanitize_text_field($_GET['url']);
                                    }
                                    else {
                                        $url = $publisher['url'];
                                    }
                                    if(!filter_var($url, FILTER_VALIDATE_URL)) {
                                        $this->add_notice('error','Invalid URL');
                                        $url = null;
                                    }
                                    $finder = new FeedFinder($url);
                                    $links = $finder->find();
                                    $feeds = array();
                                    foreach($links as $link) {
                                        if($finder->is_feed($link)) {
                                            $feeds[] = $link;
                                        }
                                    }
                                    // hook for saving feed: inside update_feeds
                                    include('templates/add-feed.php');
                                    break;
                                case 'update':
                                    if(!isset($_GET['id'])) {
                                        wp_die(__('Missing id.'));
                                    }
                                    global $wpdb;
                                    $item = new WSYS_Feed(sanitize_text_field($_GET['id']));
                                    if(!$item->id) {
                                        wp_die(__('Feed not found.'));
                                    }
                                    $results = $item->parse();
                                    if(is_wp_error($results)) {
                                        $this->add_notice('error', $results->get_error_message());
                                        wp_redirect(admin_url(WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$item->data['publisher_id']))));
                                        exit;
                                    }

                                    include('templates/feed-report.php');
                                    break;
                                default:
                                    global $publisher_table;
                                    include('templates/publishers.php');
                                    break;
                            }
                        } else {
                            global $publisher_table;
                            include('templates/publishers.php');
                            break;
                        }
                        break;
                    case 'wsysadmin_rules':
                        if (isset($_GET['action'])) {
                            switch ($_GET['action']) {
                                case 'add':
                                case 'edit':
                                    $item = array('feed_id' => false);
                                    $item['publisher_id'] = sanitize_text_field($_GET['publisher_id']);
                                    $id = isset($_GET['id']) && $_GET['id'] > 0 ? sanitize_text_field($_GET['id'])  : false;
                                    if($id) {
                                        $item = WSYS_Rule::get(sanitize_text_field($_GET['id']));
                                        if (!$item) {
                                            wp_die(__('Rule not found.'));
                                        }
                                        $item['categories'] = explode(',',$item['categories']);
                                    } else {
                                        $item['categories'] = array();
                                    }
                                    $feeds = WSYS_Feed::get_items(array(array('field'=>'publisher_id','value' => $item['publisher_id'])));

                                    // Saved by a hook: save_rule
                                    include('templates/edit-rule.php');
                                    break;
                                default:
                                    global $publisher_table;
                                    include('templates/publishers.php');
                                    break;
                            }
                        } else {
                            global $publisher_table;
                            include('templates/publishers.php');
                            break;
                        }
                        break;
                }
            }
            $this->clear_notices();
        }

        public static function build_url($uri='',$page=null,$action=null,$extra=array()) {
            $args = array();
            if($page) $args['page'] = $page;
            if($action) $args['action'] = $action;
            if(!empty($extra)) {
                $args = array_merge($args,$extra);
            }
            return $uri.'?'.http_build_query($args);
        }

        public function add_notice($class, $message){
            add_notice($class, $message);
        }

        public function show_notices() {
            if(isset($_SESSION['wsys_notices'])) {
                foreach($_SESSION['wsys_notices'] as $notice) {
                    echo '<div class="' . ($notice['class'] == 'success' ? 'updated' : 'error') . '"><p>' . $notice['message'] . '</p></div>';
                }
            }
        }

        public function clear_notices() {
            if(isset($_SESSION['wsys_notices'])) {
                unset($_SESSION['wsys_notices']);
            }
        }

        public function add_meta_box( $post_type ) {
            $post_types = array('post');     //limit meta box to certain post types
            if ( in_array( $post_type, $post_types )) {
                add_meta_box(
                    'wsysadmin_publisher'
                    ,__( 'Publisher', 'wsysadmin_textdomain' )
                    ,array( $this, 'render_meta_box_content' )
                    ,$post_type
                    ,'side'
                    ,'default'
                );
            }
        }

        public function save( $post_id ) {
            /*
             * We need to verify this came from the our screen and with proper authorization,
             * because save_post can be triggered at other times.
             */
            return $post_id;

            // Check if our nonce is set.
            if ( ! isset( $_POST['wsysadmin_inner_custom_box_nonce'] ) )
                return $post_id;

            $nonce = sanitize_text_field($_POST['wsysadmin_inner_custom_box_nonce']);

            // Verify that the nonce is valid.
            if ( ! wp_verify_nonce( $nonce, 'wsysadmin_inner_custom_box' ) )
                return $post_id;

            // If this is an autosave, our form has not been submitted,
            //     so we don't want to do anything.
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                return $post_id;

            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;

            /* OK, its safe for us to save the data now. */

            // Sanitize the user input.
            $mydata = sanitize_text_field( $_POST['wsysadmin_publisher'] );

            // Update the meta field.
            //update_post_meta( $post_id, 'publisher_id', $mydata );
        }

        public function render_meta_box_content( $post ) {

            // Add an nonce field so we can check for it later.
            wp_nonce_field( 'wsysadmin_inner_custom_box', 'wsysadmin_inner_custom_box_nonce' );

            // Use get_post_meta to retrieve an existing value from the database.
            $publisher_id = esc_attr(get_post_meta( $post->ID, 'publisher_id', true ));
            if($publisher_id) {
                $publisher = WSYS_Publisher::get($publisher_id);

                // Display the form, using the current value.
                echo '<strong id="myplugin_new_field">' .  ($publisher ? $publisher['name'] : '-' ) . '</strong>';
            } else {
                $publisher = null;
            }
        }

        public function posts_filter( $query )
        {
            global $pagenow;
            if ( is_admin() && $pagenow=='edit.php' && isset($_GET['feed_id']) && $_GET['feed_id'] != '') {
                $query->query_vars['meta_key'] = 'feed_id';
                $query->query_vars['meta_value'] = sanitize_text_field($_GET['feed_id']);
            }
            elseif ( is_admin() && $pagenow=='edit.php' && isset($_GET['publisher_id']) && $_GET['publisher_id'] != '') {
                $query->query_vars['meta_key'] = 'publisher_id';
                $query->query_vars['meta_value'] = sanitize_text_field($_GET['publisher_id']);
            } 

        }

        public function posts_filter_restrict_manage_posts()
        {
            $publishers = WSYS_Publisher::get_all(array());
            ?>
            <select name="publisher_id">
                <option value=""><?php _e('Filter By Publisher'); ?></option>
                <?php
                foreach ($publishers as $publisher) {
                    echo '<option value="'.$publisher['id'].'" '.(isset($_GET['publisher_id']) && $_GET['publisher_id']==$publisher['id'] ? 'selected=""' : '').'>'.$publisher['name'].'</option>';
                }
                ?>
            </select>
        <?php
            $feeds = WSYS_Feed::get_all(array());
            ?>
            <select name="feed_id">
                <option value=""><?php _e('Filter By Feed'); ?></option>
                <?php
                foreach ($feeds as $feed) {
                    echo '<option value="'.$feed['id'].'" '.(isset($_GET['feed_id']) && $_GET['feed_id']==$feed['id'] ? 'selected=""' : '').'>'.$feed['name'].'</option>';
                }
                ?>
            </select>
        <?php
        }
    }
}

if(class_exists('WP_WsysAdmin'))
{
    // Installation and uninstallation hooks
    register_activation_hook(__FILE__, array('WP_WsysAdmin', 'activate'));
    register_deactivation_hook(__FILE__, array('WP_WsysAdmin', 'deactivate'));

    // instantiate the plugin class
    $wsys_plugin_object = new WP_WsysAdmin();
}

function register_session(){
    if( !session_id() )
        session_start();
}
add_action('init','register_session',1);

include_once('wsys-redirect-feed-posts.php');
include_once('wsys-stats.php');
include_once('wsys-seo.php');


/**
 * CRON
 */
/**
 * On the scheduled action hook, run the function.
 */
class FeedCron {
    public $wpdb;
    private $feed_table;

    public function __construct() {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->feed_table = WSYS_Feed::$table_name;
    }

    public function getFeeds($active = WSYS_Feed::STATUS_ACTIVE) {
        $query =  'SELECT * FROM ' . $this->feed_table . ' WHERE 1';
        switch($active) {
            case WSYS_Feed::STATUS_ACTIVE:
                $query .= ' AND status=' . WSYS_Feed::STATUS_ACTIVE;
                break;
            case WSYS_Feed::STATUS_PENDING:
                $query .= ' AND status=' . WSYS_Feed::STATUS_PENDING;
                break;
            case WSYS_Feed::STATUS_DISABLED:
                $query .= ' AND status=' . WSYS_Feed::STATUS_DISABLED;
                break;
        }
        $query .= ' AND processing=' . WSYS_Feed::PROCESSING_FALSE;

        return $this->wpdb->get_results($query);
    }
}

function feeds_update() {
    $feedCron = new FeedCron();
    $active_feeds = $feedCron->getFeeds();

    if($active_feeds) {
        $reports = array();
        echo "Found feeds: " . count($active_feeds) . PHP_EOL;
        foreach($active_feeds as $feedObject) {
            echo "Parsing: #" . $feedObject->id . '; URL: ' . $feedObject->url . PHP_EOL;
            $feed = new WSYS_Feed($feedObject->id);
            $res = $feed->parse(false,WSYS_Feed_Log::FETCH_CRON);
            if(is_wp_error($res)) {
                echo 'ERROR parsing feed ' . $res->get_error_message() . PHP_EOL;
            }
            else{
                $reports[] = $res;
            }
        }
        echo "=============".PHP_EOL;
        echo "Feeds parsed:".count($reports).PHP_EOL;
        $report = array(
            'total' => 0,
            'inserted' => 0,
            'updated' => 0,
            'no_change' => 0,
            'without_image' => 0,
            'errored' => 0,
            'messages' => ''
        );
        foreach($reports as $r) {
            $report['total'] += $r['total'];
            $report['inserted'] += $r['inserted'];
            $report['updated'] += $r['updated'];
            $report['no_change'] += $r['no_change'];
            $report['without_image'] += $r['without_image'];
            $report['errored'] += $r['errored'];
            $report['messages'] .= $r['messages'] ? "\n\n".$r['messages'] : '';
        }
        echo "Total posts:".count($reports).PHP_EOL;
        echo "Inserted posts:".$report['inserted'].PHP_EOL;
        echo "Updated posts:".$report['updated'].PHP_EOL;
        echo "Posts left unchanged:".$report['no_change'].PHP_EOL;
        echo "Posts without image:".$report['without_image'].PHP_EOL;
        echo "Errored posts:".$report['errored'].PHP_EOL;
    }
}

add_action( 'hourly_events',  'feeds_update' );

function save_publisher() {
    global $wpdb;
    if(!(isset($_GET['page']) && $_GET['page'] == 'wsysadmin_publishers' && $_POST)) {
        return false;
    }
    $item = array();
    if(isset($_GET['id'])) {
        $item = WSYS_Publisher::get(sanitize_text_field($_GET['id']));
        if($item) {
            $data['id'] = $item['id'];
        }
    }
    if(!isset($item['id'])) {
        $item['id'] = false;
    }
    if(!isset($item['author_id'])) {
        $item['author_id'] = false;
    }
    $is_valid = true;
    $data['name'] = sanitize_text_field($_POST['name']);
    $data['description'] = sanitize_text_field($_POST['description']);
    $data['url'] = sanitize_text_field($_POST['url']);
    $data['status'] = sanitize_text_field($_POST['status']);
    if($_POST['image_1']) {
        $data['image_1'] = sanitize_text_field($_POST['image_1']);
    }
    if($_POST['image_2']) {
        $data['image_2'] = sanitize_text_field($_POST['image_2']);
    }
    if($_POST['image_3']) {
        $data['image_3'] = sanitize_text_field($_POST['image_3']);
    }
    $item = array_merge($item,$data);
    if(trim($_POST['name'])=='') {
        $is_valid = false;
        add_notice('error','Publisher name is mandatory!');
    } elseif(WSYS_Publisher::get_count(array(array(
            'field'=>'name',
            'value'=>sanitize_text_field(
                $_POST['name'])
        ), array(
            'field'=>'id',
            'operator'=>'!=',
            'value'=>$item['id']
        )))>0) {
        add_notice('error','Publisher name must be unique');
        $is_valid = false;
    }
    if(trim($_POST['url'])=='') {
        $is_valid = false;
        add_notice('error','Publisher URL is mandatory!');
    }
    elseif(!filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
        add_notice('error','Publisher URL is not valid');
        $is_valid = false;
    }
    elseif(WSYS_Publisher::get_count(array(array('field'=>'url','value'=>sanitize_text_field($_POST['url'])),array('field'=>'id','operator'=>'!=','value'=>$item['id'])))>0) {
        add_notice('error','Publisher URL must be unique');
        $is_valid = false;
    }
    if($is_valid) {
        if(!$item['id']) {
            $res = WSYS_Publisher::insert($item);
        } else {
            $res = WSYS_Publisher::update($data);
        }

        if ($res !== false) {
            if ($item['author_id']) {
                $user = get_user_by('id',$item['author_id']);
                $user_id = $item['author_id'];
            }
            else {
                $user_id = username_exists(sanitize_user($_POST['name']));
            }
            if (!$user_id) {
                $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                $userdata = array(
                    'user_login' => sanitize_user($_POST['name']),
                    'display_name' => sanitize_text_field($_POST['name']),
                    'user_url' => sanitize_text_field($_POST['url']),
                    'user_pass' => $random_password
                );
                $user_id = wp_insert_user($userdata);
                add_user_meta( $user_id, 'publisher_id', $res, true ) || update_user_meta( $user_id, 'publisher_id', $res );

                $res2 = WSYS_Publisher::update(array(
                    'id'=>$res,
                    'author_id'=>$user_id
                ));
                if (!is_wp_error($res2)) {
                    add_notice('success','Publisher saved successfully!');
                    wp_redirect(admin_url('admin.php?page=wsysadmin_publishers'));
                    exit;
                }
            }
            else {
                $res2 = wp_update_user(array(
                    'ID'=>$user_id,
                    'user_login'=> sanitize_user($_POST['name']),
                    'user_nicename'=> sanitize_user($_POST['name']),
                    'display_name'=>sanitize_text_field($_POST['name'])));
                if (!is_wp_error($res2)) {
                    add_notice('success','Publisher saved successfully!');
                    wp_redirect(admin_url('admin.php?page=wsysadmin_publishers'));
                    exit;
                }
                else {
                    add_notice('error',$wpdb->last_error);
                }
            }
        } else {
            add_notice('error',$wpdb->last_error);
        }
    }
}
add_action( 'admin_init', 'save_publisher' );

function add_notice($class, $message) {
    $notice = array(
        'class' => $class,
        'message' => $message,
    );
    if(isset($_SESSION['wsys_notices'])) {
        $notices = $_SESSION['wsys_notices'];
    }
    else {
        $notices = array();
    }
    $notices[] = $notice;
    $_SESSION['wsys_notices'] = $notices;
}

function update_publisher() {
    if (isset($_GET['page']) && $_GET['page'] == 'wsysadmin_publishers' && isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'changeStatus':
                if(!isset($_GET['id'])) {
                    wp_die(__('Missing id.'));
                }
                global $wpdb;
                $item = WSYS_Publisher::get(sanitize_text_field($_GET['id']));
                if(!$item) {
                    wp_die(__('Publisher not found.'));
                }
                $data['id'] = $item['id'];
                $data['status'] = sanitize_text_field($_GET['status']);
                $res1 = WSYS_Publisher::update($data);
                $res2 = WSYS_Publisher::updateFeedsStatus($item['id'],$data['status']);
                if($res1!==false || $res2!==false) {
                    add_notice('success','Publisher updated successfully!');
                }
                else {
                    add_notice('error',$wpdb->last_error);
                }
                wp_redirect(admin_url('admin.php?page=wsysadmin_publishers&action=edit&id=' . $_GET['id']));
                exit;
                break;
            case 'togglePosts':
                if(!isset($_GET['id'])) {
                    wp_die(__('Missing id.'));
                }
                global $wpdb;
                $item = WSYS_Publisher::get(sanitize_text_field($_GET['id']));
                if(!$item) {
                    wp_die(__('Publisher not found.'));
                }
                $res = WSYS_Publisher::updatePostsStatus(
                    sanitize_text_field($_GET['id']),
                    sanitize_text_field($_GET['status']
                    ));
                if($res!==false) {
                    add_notice('sucess','All publisher\'s posts have been updated!');
                }
                else {
                    add_notice('error',$wpdb->last_error);
                }
                wp_redirect(admin_url('admin.php?page=wsysadmin_publishers'));
                exit;
                break;
            case 'delete':
                if(!isset($_GET['id'])) {
                    wp_die(__('Missing id.'));
                }
                global $wpdb;
                $res = WSYS_Publisher::delete(sanitize_text_field($_GET['id']));
                if($res!==false) {
                    add_notice('success','Publisher deleted successfully!');
                }
                else {
                    add_notice('error', $wpdb->last_error);
                }
                wp_redirect(admin_url('admin.php?page=wsysadmin_publishers'));
                exit;
                break;
        }
    }
}
add_action( 'admin_init', 'update_publisher' );

function update_feeds()
{
    if (isset($_GET['page']) && $_GET['page'] == 'wsysadmin_feeds' && isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'add':
                if (isset($_POST['Use'])) {
                    global $wpdb;
                    $item = array();
                    $item['name'] = sanitize_text_field($_POST['name']);
                    $item['url'] = sanitize_text_field($_POST['url']);
                    $item['status'] = sanitize_text_field($_POST['status']);
                    $item['publisher_id'] = sanitize_text_field($_GET['publisher_id']);
                    if(!filter_var($item['url'], FILTER_VALIDATE_URL)) {
                        add_notice('error','Invalid URL');
                    }
                    else {
                        $res = WSYS_Feed::insert($item);
                        if ($res !== false) {
                            add_notice('success','Feed saved successfully!');
                            wp_redirect(admin_url(WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$item['publisher_id']))));
                            exit;
                        }
                        else {
                            add_notice('error', $wpdb->last_error);
                        }
                    }
                }
                break;
            case 'changeStatus':
                if(!isset($_GET['id'])) {
                    wp_die(__('Missing id.'));
                }
                global $wpdb;
                $item = WSYS_Feed::get($_GET['id']);
                if(!$item) {
                    wp_die(__('Feed not found.'));
                }
                $item['status'] = $_GET['status'];
                $res = WSYS_Feed::update($item);
                if($res!==false) {
                    add_notice('success', 'Feed updated successfully!');
                }
                else {
                    add_notice('error',$wpdb->last_error);
                }
                wp_redirect(admin_url(WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$item['publisher_id']))));
                exit;
                break;
            case 'togglePosts':
                if(!isset($_GET['id'])) {
                    wp_die(__('Missing id.'));
                }
                global $wpdb;
                $item = WSYS_Feed::get($_GET['id']);
                if(!$item) {
                    wp_die(__('Feed not found.'));
                }
                $res = WSYS_Feed::updatePostsStatus($_GET['id'],$_GET['status']);
                if($res!==false) {
                    add_notice('success', 'All feed\'s posts have been updated!');
                }
                else {
                    add_notice('error', $wpdb->last_error);
                }
                wp_redirect(admin_url(WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$item['publisher_id']))));
                exit;
                break;
            case 'delete':
                if(!isset($_GET['id'])) {
                    wp_die(__('Missing id.'));
                }
                global $wpdb;
                $res = WSYS_Feed::delete($_GET['id']);
                if($res!==false) {
                    add_notice('success','Feed deleted successfully!');
                }
                else {
                    add_notice('error', $wpdb->last_error);
                }
                wp_redirect(admin_url(WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$_GET['publisher_id']))));
                exit;
                break;
        }
    }
}
add_action( 'admin_init', 'update_feeds' );

function save_rule() {
    if(isset($_GET['page'])) {
        switch ($_GET['page']) {
            case 'wsysadmin_rules':if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'add':
                    case 'edit':
                        $item = array();
                        $id = isset($_GET['id']) && $_GET['id'] > 0 ? sanitize_text_field($_GET['id']) : false;
                        $item['publisher_id'] = $_GET['publisher_id'];
                        if ($id) {
                            $item = WSYS_Rule::get($_GET['id']);
                            if (!$item) {
                                wp_die(__('Rule not found.'));
                            }
                            $item['categories'] = explode(',', $item['categories']);
                        } else {
                            $item['categories'] = array();
                        }
                        if (isset($_POST['save'])) {
                            global $wpdb;
                            if (isset($_POST['tags'])) {
                                $tags = explode(',', sanitize_text_field($_POST['tags']));
                                $item['tags'] = implode(', ', array_filter(array_map('trim', $tags)));
                            } else {
                                $item['tags'] = '';
                            }

                            if (isset($_POST['post_category']) && !empty($_POST['post_category'])) {
                                $item['categories'] = sanitize_text_field(implode(',', $_POST['post_category']));

                                if ($_POST['feed']) {
                                    $item['feed_id'] = sanitize_text_field($_POST['feed']);
                                }
                                $data = $item;
                                if (!(isset($item['feed_id']) && $item['feed_id'])) {
                                    unset($data['feed_id']);
                                }
                                if ($id) {
                                    $res = WSYS_Rule::update($data);
                                } else {
                                    $res = WSYS_Rule::insert($data);
                                }
                                if ($res !== false) {
                                    add_notice('success', 'Rule saved successfully!');
                                    wp_redirect(admin_url(WP_WsysAdmin::build_url('admin.php', 'wsysadmin_publishers', 'edit', array('id' => $item['publisher_id']))));
                                    exit;
                                } else {
                                    add_notice('error', $wpdb->last_error);
                                }
                            } else {
                                add_notice('error', 'No categories have been selected! Please choose at least one.');
                            }
                        }
                        break;
                    case 'delete':
                        if(!isset($_GET['id'])) {
                            wp_die(__('Missing id.'));
                        }
                        global $wpdb;
                        $res = WSYS_Rule::delete(sanitize_text_field($_GET['id']));
                        if($res!==false) {
                            add_notice('success', 'Rule deleted successfully!');
                        }
                        else {
                            add_notice('error', $wpdb->last_error);
                        }
                        wp_redirect(admin_url(WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array(
                            'id' => sanitize_text_field($_GET['publisher_id'])
                        ))));
                        exit;
                        break;
                }
            }
        }
    }
}
add_action('admin_init', 'save_rule');