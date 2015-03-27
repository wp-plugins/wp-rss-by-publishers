<?php

class WSYS_Publisher extends WSYS_DB_Table {

    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2;
    const STATUS_PENDING = 0;
    static $table_name = 'wsys_publisher';

    public static $columns = array(
        'id' => array(
            'db_field' => 'id',
            'label' => 'ID',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'name' => array(
            'db_field' => 'name',
            'label' => 'Name',
            'sortable' => true,
            'hidden' => false,
            'type' => '%s',
        ),
        'description' => array(
            'db_field' => 'description',
            'label' => 'Description',
            'sortable' => false,
            'hidden' => true,
            'type' => '%s',
        ),
        'url' => array(
            'db_field' => 'url',
            'label' => 'URL',
            'sortable' => false,
            'hidden' => false,
            'type' => '%s',
        ),
        'status' => array(
            'db_field' => 'status',
            'label' => 'Status',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'api_key' => array(
            'db_field' => 'api_key',
            'label' => 'API KEY',
            'sortable' => true,
            'hidden' => true,
            'type' => '%s',
        ),
        'image_1' => array(
            'db_field' => 'image_1',
            'label' => 'Main image',
            'sortable' => false,
            'hidden' => true,
            'type' => '%s',
        ),
        'image_2' => array(
            'db_field' => 'image_2',
            'label' => 'Second image',
            'sortable' => false,
            'hidden' => true,
            'type' => '%s',
        ),
        'image_3' => array(
            'db_field' => 'image_3',
            'label' => 'Third image',
            'sortable' => false,
            'hidden' => true,
            'type' => '%s',
        ),
        'feed_count' => array(
            'db_field' => 'feed_count',
            'label' => 'Feeds',
            'sortable' => false,
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
        'author_id' => array(
            'db_field' => 'author_id',
            'label' => 'Author',
            'sortable' => false,
            'hidden' => true,
            'type' => '%d',
        ),
    );

    function __construct(){
    }

    public static function updatePostsStatus($id,$status) {
        global $wpdb;
        if(!isset($id) || !$id) {
            return false;
        }
        $res = $wpdb->query($wpdb->prepare("update $wpdb->posts set $wpdb->posts.post_status=%s where $wpdb->posts.post_type='post' and $wpdb->posts.ID in (select distinct($wpdb->postmeta.post_id) from $wpdb->postmeta where $wpdb->postmeta.meta_key='publisher_id' and $wpdb->postmeta.meta_value=%s)",($status==WSYS_Feed::STATUS_ACTIVE ? 'publish' : 'private'),$id));
        return $res;
    }

    public static function updateFeedsStatus($id,$status) {
        global $wpdb;
        if(!isset($id) || !$id) {
            return false;
        }
        $res = $wpdb->query($wpdb->prepare("update ".WSYS_Feed::$table_name." set status=%d where publisher_id=%d",$status,$id));
        return $res;
    }

    public static function get($id) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("select p.*,(select count(*) from ".WSYS_Feed::$table_name." where publisher_id=p.id) as feed_count from ".self::$table_name." p where id=%d",$id), ARRAY_A);
        return $result;
    }

    public static function getByApiKey($key) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("select p.*,(select count(*) from ".WSYS_Feed::$table_name." where publisher_id=p.id) as feed_count from ".self::$table_name." p where api_key=%s",$key), ARRAY_A);
        return $result;
    }

    public static function get_items($filters,$orderBy='created_at',$order='asc',$limit=10,$offset=0) {
        global $wpdb;
        $where = self::get_filters_sql($filters);
        $results = $wpdb->get_results("select p.*,(select count(*) from ".WSYS_Feed::$table_name." where publisher_id=p.id) as feed_count,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='publisher_id' and `meta_value`=p.`id` and $wpdb->posts.`post_status`='publish' and $wpdb->posts.`post_type`='post') as published_post_count,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='publisher_id' and `meta_value`=p.`id` and $wpdb->posts.`post_status`='pending' and $wpdb->posts.`post_type`='post') as pending_post_count,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='publisher_id' and `meta_value`=p.`id` and $wpdb->posts.`post_status`='private' and $wpdb->posts.`post_type`='post') as hidden_post_count, (select hidden_post_count+published_post_count) as post_count from ".self::$table_name." p ".$where." order by ".$orderBy." ".$order." limit ".$limit." offset ".$offset, ARRAY_A);
        return $results;
    }

    public static function get_all($filters,$orderBy='name',$order='asc') {
        global $wpdb;
        $where = self::get_filters_sql($filters);
        $results = $wpdb->get_results("select * from ".self::$table_name." p ".$where." order by ".$orderBy." ".$order, ARRAY_A);
        return $results;
    }

    public static function get_stats($start,$end) {
        global $wpdb;
        //echo "select p.*,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='publisher_id' and `meta_value`=p.`id` and $wpdb->posts.`post_status`='publish' and $wpdb->posts.`post_type`='post') as post_count,(select count(*) from wp_slim_stats t1 INNER JOIN wp_slim_content_info tci ON t1.content_info_id = tci.content_info_id WHERE tci.content_type = 'post' and binary tci.author = binary p.name) as views from ".self::$table_name." p where p.status=".WSYS_Publisher::STATUS_ACTIVE." order by post_count desc";
        $results = $wpdb->get_results("select p.*,(select count(*) from $wpdb->posts,$wpdb->postmeta where $wpdb->posts.`ID`=$wpdb->postmeta.`post_id` and $wpdb->postmeta.`meta_key`='publisher_id' and `meta_value`=p.`id` and $wpdb->posts.`post_status`='publish' and $wpdb->posts.`post_type`='post' and ".($start ? "post_date>=FROM_UNIXTIME(".$start.")" : "1=1")." and ".($end ? "post_date<=FROM_UNIXTIME(".$end.")" : "1=1").") as post_count,(select count(*) from wp_slim_stats t1 INNER JOIN wp_slim_content_info tci ON t1.content_info_id = tci.content_info_id WHERE tci.content_type = 'post' and binary tci.author = binary p.name and ".($start ? "t1.dt>=".$start : "1=1")." and ".($end ? "t1.dt<=".$end : "1=1").") as views from ".self::$table_name." p where p.status=".WSYS_Publisher::STATUS_ACTIVE." order by post_count desc", ARRAY_A);
        return $results;
    }

    public static function getStatuses() {
        return array(
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DISABLED => 'Disabled',
        );
    }
}
