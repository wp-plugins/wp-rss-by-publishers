<?php
require_once('wsys-advertisers.php');
class WSYS_Feed extends WSYS_DB_Table{

    public $id = null;
    public $data = array();
    public $settings = array();
    public $publisher = array();
    public $feed = null;
    public $rules = array();
    public $posts = array();
    public $sandbox = false;
    private $messages = array();

    const TYPE_NORMAL = 0;
    const TYPE_PLUGIN = 1;

    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2;
    const STATUS_PENDING = 0;
    const PROCESSING_FALSE = 0;
    const PROCESSING_TRUE = 1;

    const DEFAULT_TIMEOUT = 10;
    const DEFAULT_CACHE_LIFETIME = 60;

    static $table_name = 'wsys_feed';

    public static $columns = array(
        'id' => array(
            'db_field' => 'id',
            'label' => 'ID',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'publisher_id' => array(
            'db_field' => 'publisher_id',
            'label' => 'Publisher',
            'sortable' => false,
            'hidden' => true,
            'type' => '%d',
        ),
        'name' => array(
            'db_field' => 'name',
            'label' => 'Name',
            'sortable' => false,
            'hidden' => false,
            'type' => '%s',
        ),
        'url' => array(
            'db_field' => 'url',
            'label' => 'URL',
            'sortable' => false,
            'hidden' => true,
            'type' => '%s',
        ),
        'plugin' => array(
            'db_field' => 'plugin',
            'label' => 'Plugin',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'status' => array(
            'db_field' => 'status',
            'label' => 'Status',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'post_count' => array(
            'db_field' => null,
            'label' => 'Posts',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'published_post_count' => array(
            'db_field' => 'published_post_count',
            'label' => 'Published posts',
            'sortable' => false,
            'hidden' => true,
            'type' => '%d',
        ),
        'hidden_post_count' => array(
            'db_field' => 'hidden_post_count',
            'label' => 'Hidden posts',
            'sortable' => false,
            'hidden' => true,
            'type' => '%d',
        ),
        'pending_post_count' => array(
            'db_field' => 'pending_post_count',
            'label' => 'Pending posts',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'created_at' => array(
            'db_field' => 'created_at',
            'label' => 'Created at',
            'sortable' => true,
            'hidden' => false,
            'type' => '%s',
        ),
        'last_fetch' => array(
            'db_field' => 'last_fetch',
            'label' => 'Last fetch',
            'sortable' => true,
            'hidden' => false,
            'type' => '%s',
        ),
        'last_modified' => array(
            'db_field' => 'last_modified',
            'label' => 'Last modified',
            'sortable' => true,
            'hidden' => true,
            'type' => '%s',
        ),
    );

    function __construct($id) {
        $feed = self::get($id);
        if($feed!==false) {
            $this->id = $id;
            $this->data = $feed;
            $this->publisher = WSYS_Publisher::get($feed['publisher_id']);
        }
    }

    function startProcessing() {
        global $wpdb;
        $wpdb->query("update ".self::$table_name." set processing=1 where id=".$this->id);
        $this->data['processing'] = WSYS_Feed::PROCESSING_TRUE;
    }

    function stopProcessing() {
        global $wpdb;
        $wpdb->query("update ".self::$table_name." set processing=0,last_fetch=CURRENT_TIMESTAMP where id=".$this->id);
        $this->data['processing'] = WSYS_Feed::PROCESSING_FALSE;
    }

    function parse($sandbox = false, $fetch_mode = WSYS_Feed_Log::FETCH_MANUAL) {
        if($sandbox) {
            $this->sandbox = true;
        }
        if(!$this->data['url']) {
            return new WP_Error( 'missing_feed_url', __( "Missing feed url" ) );
        }
        if($this->data['processing']) {
            return new WP_Error( 'feed_processing', __( "Feed is already being processed" ) );
        }
        $this->messages = array();
        $start_time = time();
        // mark feed as processing
        $this->startProcessing();
        $this->getRules();
        //$this->getPosts();
        // fetch feed from cache
        $this->fetch();
        // if feed is error return
        if(is_wp_error($this->feed)) {
            return $this->feed;
        }
        // check feed last_update
        if(isset($this->feed->data['headers']['last-modified']) && $this->data['last_modified']==$this->feed->data['headers']['last-modified']) {
            $report = array(
                'total' => 0,
                'inserted' => 0,
                'updated' => 0,
                'no_change' => 0,
                'without_image' => 0,
                'errored' => 0,
                'messages' => 'Nothing changed',
                'duration' => time()-$start_time,
            );
            $this->stopProcessing();
        }
        else {
            $report = array(
                'total' => 0,
                'inserted' => 0,
                'updated' => 0,
                'no_change' => 0,
                'without_image' => 0,
                'errored' => 0,
                'messages' => ''
            );
            //$advertisers = WSYS_Advertisers::get_advertisers();
            $advertisers = new WSYS_Advertisers();
            foreach($this->feed->get_items() as $item) {
                $this->messages[] = '['.$item->get_permalink().']';
                $post = $this->parsePost($item);
                $advertisers->add_post($post);
                $report['total']++;
                if($post['featured_image']=='') {
                    $report['without_image']++;
                }
                if($post['ID'] && $post['post_modified_gmt'] && $this->posts[$post['ID']]['post_modified_gmt']!=$post['post_modified_gmt']) {
                    if(!$sandbox) {
                        if($this->savePost($post)) {
                            $report['updated']++;
                        }
                        else {
                            $report['errored']++;
                        }
                    }
                }
                elseif(!$post['ID']) {
                    if(!$sandbox) {
                        if($this->savePost($post)) {
                            $report['inserted']++;
                        }
                        else {
                            $report['errored']++;
                        }
                    }
                }
                else {
                    $report['no_change']++;
                }
                $this->messages[] = '[--'.$item->get_permalink().'--]';
            }
            $report['messages'] = implode("\n",$this->messages);
            $this->stopProcessing();
            $report['duration'] = time()-$start_time;
            $data = array(
                'fetch_time' => date('Y-m-d H:i',$start_time),
                'duration' => $report['duration'],
                'feed_id' => $this->id,
                'fetch_type' => $fetch_mode,
                'sandbox' => $sandbox,
                'posts_total' => $report['total'],
                'posts_inserted' => $report['inserted'],
                'posts_updated' => $report['updated'],
                'posts_unchanged' => $report['no_change'],
                'posts_no_image' => $report['without_image'],
                'posts_errored' => $report['errored'],
                'messages' => $report['messages'],
            );
            WSYS_Feed_Log::insert($data);
            //update feed last_update
            WSYS_Feed::update(array('id'=>$this->id,'last_modified'=>$this->feed->data['headers']['last-modified']));
        }
        return $report;
    }

    function fetch($timeout = self::DEFAULT_TIMEOUT, $cache = self::DEFAULT_CACHE_LIFETIME) {
        require_once(ABSPATH . WPINC . '/class-feed.php');

        $feed = new SimplePie();

        $feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );
        // We must manually overwrite $feed->sanitize because SimplePie's
        // constructor sets it before we have a chance to set the sanitization class
        $feed->sanitize = new WP_SimplePie_Sanitize_KSES();

        $feed->set_cache_class( 'WP_Feed_Cache' );
        $feed->set_file_class( 'WP_SimplePie_File' );

        $feed->set_feed_url( $this->data['url'] );
        $feed->set_timeout( $timeout );
        if($cache==0) {
            $feed->enable_cache(false);
        }
        else {
            $feed->set_cache_duration( $cache );
        }
        //$feed->set_useragent('csUap');
        $feed->init();
        $feed->handle_content_type();

        if ($feed->error()) {
            $this->feed = new WP_Error( 'simplepie-error', $feed->error() );
        }

        $this->feed = $feed;
    }

    function findPost($url) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("select ID, post_modified_gmt from $wpdb->posts where post_type='post' and post_status in ('publish','pending','private','trash') and guid=%s",$url),ARRAY_A);
        if($row) {
            $this->posts[$row['ID']] = $row;
            return $row['ID'];
        }
        return null;
    }

    function parsePost($item) {
        global $wpdb;
        $new_post = array(
            'ID'             => null,
            'post_content'   => wp_strip_all_tags($item->get_description()),
            'post_name'      => sanitize_title($item->get_title()),
            'post_title'     => $item->get_title(),
            'post_status'    => 'draft',
            'post_type'      => 'post',
            'post_author'    => $this->getAuthor(),
            'ping_status'    => 'open',
            'post_parent'    => 0,
            'guid'           => $item->get_permalink(),
            'post_date'  => $item->get_gmdate('Y-m-d H:i:s'),
            'post_date_gmt'  => $item->get_gmdate('Y-m-d H:i:s'),
            'post_modified'  => $item->get_updated_gmdate('Y-m-d H:i:s'),
            'post_modified_gmt'  => $item->get_updated_gmdate('Y-m-d H:i:s'),
            'comment_status' => 'open',
            'post_category'  => $this->getCategories($item->get_categories()),
            'tags_input'     => $this->getTags($item->get_categories()),
            'featured_image' => $this->getFeaturedImage($item),
            'original_author' => $item->get_author(),
        );
        
        $new_post['ID'] = $this->findPost($new_post['guid']);
        /*
        foreach($this->posts as $post) {
            if($post->guid==$item->get_permalink()) {
                $new_post['ID'] = $post->ID;
                break;
            }
        }
        */
        if(!empty($new_post['post_category'])) {
            $new_post['post_status'] = 'publish';
        }
        else {
            $new_post['post_status'] = 'pending';
        }
        return $new_post;
    }

    function getAuthor() {
        $users = get_users(array('meta_key' => 'publisher_id', 'meta_value' => $this->publisher['id'], 'fields' => 'ID'));
        if(count($users)>0) {
            return $users[0];
        }
        return 0;
    }

    function validUrl($url) {
        $file_headers = @get_headers($url);
        if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        return true;
    }


    function getFeaturedImage($item) {
        foreach ($item->get_enclosures() as $enclosure) {
            if(strpos($enclosure->get_type(),'image')!==false) {
                $img = $enclosure->get_link();
                if($this->testImageDimensions($img)) {
                    $this->messages[] = 'found enclosure';
                    return $img;
                }
            }
        }
        return $this->searchFeaturedImage($item->get_content());
    }

    function searchFeaturedImage($content) {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'utf-8');
        //echo $content
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8");
        $dom->loadHTML('<?xml encoding="UTF-8">'.'<node>'.html_entity_decode($content,ENT_HTML401,'UTF-8').'</node>');

        // dirty fix
        foreach ($dom->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $dom->removeChild($item); // remove hack
            }
        }
        $dom->encoding = 'UTF-8'; // insert proper

        $sxe = simplexml_import_dom($dom);
        if($sxe){
            $matches = $sxe->xpath('//img');
            if(count($matches)>0 && isset($matches[0]['src'])) {
                foreach($matches as $match) {
                    $img = $match['src']->__toString();
                    if($this->testImageDimensions($img)) {
                        return $img;
                    }
                }
            }
        }
        foreach(libxml_get_errors() as $error) {
            $this->messages[] = $error->message;
        }
        return '';
        /*
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string('<node>'.html_entity_decode($content).'</node>');
        var_dump($sx);
        if($sx){
            $matches = $sx->xpath('//img');
            if(count($matches)>0 && isset($matches[0]['src'])) {
                return $matches[0]['src']->__toString();
            }
        }
        return '';
        */
    }

    function testImageDimensions($img) {
        if(substr($img,0,2)=='//') {
            $img = 'http:'.$img;
        }
        if(strpos($img,'base64')===false && !$this->validUrl($img)) {
            return '';
        }
        // check image dimensions are ok
        $min_width = esc_attr(get_option('wsys-post-image-min-width'));
        $min_height = esc_attr(get_option('wsys-post-image-min-width'));
        $dim_ok = true;
        if ($min_width && $min_height) {
            if (strpos($img, 'base64') !== false && substr($img, 0, 5) != 'data:') {
                $img = 'data:' . $img;
            }
            if (ini_get('allow_url_fopen') || strpos($img, 'base64') !== false) {
                $dim = getimagesize($img);
                //if($dim==false) echo $content;
            } else {
                $tmp = download_url($img);
                if (is_wp_error($tmp)) {
                    @unlink($tmp);
                    $this->messages[] = $tmp->get_error_message();
                } else {
                    $dim = getimagesize($tmp);
                }
            }
            if (!empty($dim)) {
                if ($dim[0] < $min_width || $dim[1] < $min_height) {
                    if (isset($tmp) && $tmp) @unlink($tmp);
                    $this->messages[] = 'Image ' . $img . ' too small.';
                    $dim_ok = false;
                }
            } else {
                if (isset($tmp) && $tmp) @unlink($tmp);
                $this->messages[] = 'Could not get dimensions for image ' . $img . '.';
                $dim_ok = false;
            }
        }
        if ($dim_ok) {
            return $img;
        }
    }

    function getCategories($categories) {
        $new_categories = array();
        foreach($this->rules as $rule) {
            if($rule['feed_id']==$this->id || !$rule['feed_id']) {
                if (!$rule['tags']) {
                    $new_categories = array_merge($new_categories, explode(',', $rule['categories']));
                } else {
                    $tags = array_filter(array_map('trim', explode(',', $rule['tags'])));

                    if($categories) {
                        foreach($categories as $category) {
                            if(strtolower($category->term)=='uncategorized') {
                                continue;
                            }
                            foreach ($tags as $tag) {
                                if (strtolower(html_entity_decode($tag)) == strtolower(html_entity_decode($category->term))) {
                                    $new_categories = array_merge($new_categories, explode(',', $rule['categories']));
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_unique($new_categories);
    }

    function getTags($categories) {
        $new_categories = array();
        if($categories) {
            foreach($categories as $category) {
                if(strtolower($category->term)!='uncategorized') {
                    $new_categories[] = $category->term;
                }
            }
        }
        return $new_categories;
    }

    function savePost($post) {
        $post_id = $post['ID'] ? wp_update_post($post,true) : wp_insert_post($post,true);
        if(is_wp_error($post_id)) {
            return false;
        }
        else {
            $tmp = download_url( $post['featured_image'] );
            if ( is_wp_error( $tmp ) ) {
                @unlink($tmp);
                $this->messages[] = $tmp->get_error_message();
            }
            else {
                $file_array = array();

                // Set variables for storage
                // fix file filename for query strings
                preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $post['featured_image'], $matches);
                if(count($matches)>0) {
                    $file_array['name'] = basename($matches[0]);
                    $file_array['tmp_name'] = $tmp;
                    // If error storing temporarily, unlink

                    // do the validation and storage stuff
                    $id = media_handle_sideload( $file_array, $post_id, $post['post_title'] );

                    // If error storing permanently, unlink
                    if ( is_wp_error($id) ) {
                        @unlink($file_array['tmp_name']);
                        $this->messages[] = $id->get_error_message();
                    }
                    else {
                        set_post_thumbnail( $post_id, $id );
                    }
                }
            }

            add_post_meta( $post_id, 'original_author', $post['original_author'], true ) || update_post_meta( $post_id, 'original_author', $post['original_author'] );
            add_post_meta( $post_id, 'feed_id', $this->id, true ) || update_post_meta( $post_id, 'feed_id', $this->id );
            add_post_meta( $post_id, 'publisher_id', $this->publisher['id'], true ) || update_post_meta( $post_id, 'publisher_id', $this->publisher['id'] );
            return true;
        }
    }

    function getRules() {
        $this->rules = WSYS_Rule::get_items(array(array('field'=>'publisher_id','value'=>$this->data['publisher_id'])));
    }

    function getPosts() {
        global $wpdb;
        $this->posts = $wpdb->get_results("select ID, guid, post_modified_gmt from $wpdb->posts where post_type='post' and post_status in ('publish','pending','private','trash')",OBJECT_K );
    }

    /*** db helpers ****/

    public static function updatePostsStatus($id,$status) {
        global $wpdb;
        if(!isset($id) || !$id) {
            return false;
        }
        $res = $wpdb->query($wpdb->prepare("update $wpdb->posts set $wpdb->posts.post_status=%s where $wpdb->posts.post_type='post' and $wpdb->posts.ID in (select distinct($wpdb->postmeta.post_id) from $wpdb->postmeta where $wpdb->postmeta.meta_key='feed_id' and $wpdb->postmeta.meta_value=%s)",($status==WSYS_Feed::STATUS_ACTIVE ? 'publish' : 'private'),$id));
        return $res;
    }

    public static function get_items($filters,$orderBy='created_at',$order='asc') {
        global $wpdb;
        $where = self::get_filters_sql($filters);
        $results = $wpdb->get_results("select ".self::$table_name.".*,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='feed_id' and `meta_value`=".self::$table_name.".`id` and $wpdb->posts.`post_status`='publish' and $wpdb->posts.`post_type`='post') as published_post_count,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='feed_id' and `meta_value`=".self::$table_name.".`id` and $wpdb->posts.`post_status`='pending' and $wpdb->posts.`post_type`='post') as pending_post_count,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='feed_id' and `meta_value`=".self::$table_name.".`id` and $wpdb->posts.`post_status`='private' and $wpdb->posts.`post_type`='post') as hidden_post_count, (select hidden_post_count+published_post_count) as post_count from ".self::$table_name."  ".$where." order by ".$orderBy." ".$order, ARRAY_A);
        return $results;
    }

    public static function get_all($filters,$orderBy='name',$order='asc') {
        global $wpdb;
        $where = self::get_filters_sql($filters);
        $results = $wpdb->get_results("select * from ".self::$table_name." p ".$where." order by ".$orderBy." ".$order, ARRAY_A);
        return $results;
    }

    public static function getPluginFeed($publisher_id) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("select * from ".static::$table_name." where plugin=%d and publisher_id=%d limit 1",WSYS_Feed::TYPE_PLUGIN,$publisher_id), ARRAY_A);
        return $result;
    }

    public static function getStatuses() {
        return array(
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DISABLED => 'Disabled',
        );
    }

    public static function getTypes() {
        return array(
            self::TYPE_NORMAL => 'No',
            self::TYPE_PLUGIN => 'Yes',
        );
    }
}
