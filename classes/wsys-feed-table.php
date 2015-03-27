<?php
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WSYS_Feed_Table extends WP_List_Table {
    public $publisher_id = null;

    function __construct($publisher_id=null){
        $this->publisher_id = $publisher_id;

        global $status, $page;

        parent::__construct( array(
            'singular'  => __( 'feed', 'feed_table' ),     //singular name of the listed records
            'plural'    => __( 'feeds', 'feed_table' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

        ) );
        add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'wsysadmin_publishers' != $page )
            return;
        echo '<style type="text/css">';
        echo '.wp-list-table.feeds .column-id { width: 5%; }';
        echo '.wp-list-table.feeds .column-name { width: 30%; }';
        echo '.wp-list-table.feeds .column-status { width: 10%; }';
        echo '</style>';
    }

    function no_items() {
        _e( 'No items found.' );
    }

    function column_default( $item, $column_name ) {
        if(array_key_exists($column_name,WSYS_Feed::$columns)) {
            return $item[$column_name];
        }
        else {
            return null;
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array();
        foreach(WSYS_Feed::$columns as $key => $column) {
            if($column['sortable']) $sortable_columns[$key] = array($key,false);
        }
        return $sortable_columns;
    }

    function get_columns(){
        $columns = array();
        $columns['cb'] = '<input type="checkbox" />';
        foreach(WSYS_Feed::$columns as $key => $column) {
            if(!$column['hidden']) $columns[$key] = __( $column['label'], 'feed_table' );
        }
        return $columns;
    }

    function column_name($item){
        $actions = array(
            //'edit'      => '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_feeds','edit',array('id' => $item['id'])).'">Edit</a>',
            //'delete'    => '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_feeds','delete',array('id' => $item['id'],'publisher_id' => $item['publisher_id'])).'">Delete</a>',
            'changeStatus'    => $item['status']==WSYS_Feed::STATUS_ACTIVE ? '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_feeds','changeStatus',array('id' => $item['id'], 'status' => WSYS_Feed::STATUS_DISABLED)).'" class="confirm" data-confirmation="Are you sure you want to disable this feed?">Disable</a>' : '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_feeds','changeStatus',array('id' => $item['id'], 'status' => WSYS_Feed::STATUS_ACTIVE)).'" class="confirm" data-confirmation="Are you sure you want to enable this feed?">Enable</a>',
        );
        if( $item['published_post_count']+$item['hidden_post_count']>0) {
            $actions['togglePosts'] = $item['published_post_count']>0 ? '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_feeds','togglePosts',array('id' => $item['id'], 'status' => WSYS_Publisher::STATUS_DISABLED)).'" class="confirm" data-confirmation="Are you sure you want to hide all posts from this feed?">Hide all posts</a>' : '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_feeds','togglePosts',array('id' => $item['id'], 'status' => WSYS_Feed::STATUS_ACTIVE)).'" class="confirm" data-confirmation="Are you sure you want to publish all posts from this feed?">Show all posts</a>';
        }

        return sprintf('%1$s %2$s', '<strong>'.$item['name'].'</strong><br/>'.$item['url'], $this->row_actions($actions) );
    }

    function column_post_count($item){
        return ($item['published_post_count']+$item['hidden_post_count']).($item['hidden_post_count']>0 ? ' ('.$item['hidden_post_count'].' hidden)' : '');
    }

    function column_pending_post_count($item){
        return $item['pending_post_count'] ? '<a href="'.admin_url('edit.php?s&post_status=pending&post_type=post&action=-1&m=0&cat=0&publisher_id='.$item['publisher_id'].'&feed_id='.$item['id'].'&filter_action=Filter&paged=1&mode=list&action2=-1').'" target="_blank">'.$item['pending_post_count'].'</a>' : $item['pending_post_count'];
    }

    function get_bulk_actions() {
        $actions = array(
            //'delete'    => 'Delete'
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />', $item['id']
        );
    }

    function column_last_fetch($item) {
        return sprintf('%1$s', ($item['last_fetch'] ? $item['last_fetch'] : 'Never').($item['plugin']!=WSYS_Feed::TYPE_PLUGIN ? '<br/>'.($item['status']==WSYS_Feed::STATUS_ACTIVE ? '<a class="button" href="'.admin_url(WP_WsysAdmin::build_url('admin.php','wsysadmin_feeds','update',array('id'=>$item['id']))).'">Update</a>' : '') : '') );
    }

    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->column_headers = array( $columns, $hidden, $sortable );
        global $wpdb;
        $filters = array();
        if(isset($_GET['s'])) $filters[] = array(
            'field' => 'name',
            'operator' => 'like',
            'value' => $_GET['s'],
        );
        if($this->publisher_id) $filters[] = array(
            'field' => 'publisher_id',
            'value' => $this->publisher_id,
        );
        $orderBy = isset($_GET['orderby']) ? $_GET['orderby'] : 'created_at';
        $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
        $count = WSYS_Feed::get_count($filters);
        //$count = $wpdb->get_var("select count(*) from bp_publisher where ".implode(' and ',$filters));
        //$results = $wpdb->get_results("select * from bp_publisher where ".implode(' and ',$filters)." order by ".$orderBy." ".$order, ARRAY_A );
        $results = WSYS_Feed::get_items($filters,$orderBy,$order);
        //usort( $results, array( &$this, 'usort_reorder' ) );

        /*
        $screen = get_current_screen();
        $option = $screen->get_option('books_per_page', 'option');
        if($option) {
            $user = get_current_user_id();
            $per_page = get_user_meta($user, $option, true);
            var_dump($option);
            if ( empty ( $per_page ) || $per_page < 1 ) {

                $per_page = $screen->get_option( 'per_page', 'default' );

            }
        }
        else {
            $per_page = $screen->get_option( 'per_page', 'default' );
        }
        */
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = $count;

        // only ncessary because we have sample data
        $this->found_data = array_slice( $results,( ( $current_page-1 )* $per_page ), $per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );
        $this->items = $this->found_data;
    }

    public function column_status($item) {
        $statuses = WSYS_Feed::getStatuses();
        return array_key_exists($item['status'],$statuses) ? $statuses[$item['status']] : '';
    }

    public function column_plugin($item) {
        $statuses = WSYS_Feed::getTypes();
        return array_key_exists($item['plugin'],$statuses) ? $statuses[$item['plugin']] : '';
    }
}