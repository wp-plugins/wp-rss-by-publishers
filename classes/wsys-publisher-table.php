<?php
if( ! class_exists( 'WP_List_Table' ) ) {
require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WSYS_Publisher_Table extends WP_List_Table {

    function __construct(){
        global $status, $page;

        parent::__construct( array(
            'singular'  => __( 'publisher', 'mylisttable' ),     //singular name of the listed records
            'plural'    => __( 'publishers', 'mylisttable' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

        ) );
        add_action( 'admin_head', array( &$this, 'admin_header' ) );
    }

    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'wsysadmin_publishers' != $page )
            return;
        echo '<style type="text/css">';
        echo '.wp-list-table.publishers .column-id { width: 3%; }';
        echo '.wp-list-table.publishers .column-name { width: 30%; }';
        echo '.wp-list-table.publishers .column-status { width: 7%; }';
        echo '.wp-list-table.publishers .column-feed_count { width: 6%; }';
        echo '</style>';
    }

    function no_items() {
        _e( 'No items found.' );
    }

    function column_default( $item, $column_name ) {
        if(array_key_exists($column_name,WSYS_Publisher::$columns)) {
            return $item[$column_name];
        }
        else {
            return null;
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array();
        foreach(WSYS_Publisher::$columns as $key => $column) {
            if($column['sortable']) $sortable_columns[$key] = array($key,false);
        }
        return $sortable_columns;
    }

    function get_columns(){
        $columns = array();
        $columns['cb'] = '<input type="checkbox" />';
        foreach(WSYS_Publisher::$columns as $key => $column) {
            if(!$column['hidden']) $columns[$key] = __( $column['label'], 'publisher_table' );
        }
        return $columns;
    }

    function column_name($item){
        $actions = array(
            'edit'      => '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_publishers','edit',array('id' => $item['id'])).'">Edit</a>',
            //'delete'    => '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_publishers','delete',array('id' => $item['id'])).'">Delete</a>',
            'changeStatus'    => $item['status']==WSYS_Publisher::STATUS_ACTIVE ? '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_publishers','changeStatus',array('id' => $item['id'], 'status' => WSYS_Publisher::STATUS_DISABLED)).'" class="confirm" data-confirmation="Are you sure you want to disable this publisher?">Disable</a>' : '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_publishers','changeStatus',array('id' => $item['id'], 'status' => WSYS_Publisher::STATUS_ACTIVE)).'" class="confirm" data-confirmation="Are you sure you want to enable this publisher?">Enable</a>',
        );
        if( $item['published_post_count']+$item['pending_post_count']+$item['hidden_post_count']>0) {
            $actions['togglePosts'] = $item['published_post_count']>0 ? '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_publishers','togglePosts',array('id' => $item['id'], 'status' => WSYS_Publisher::STATUS_DISABLED)).'" class="confirm" data-confirmation="Are you sure you want to hide all posts from this publisher?">Hide all posts</a>' : '<a href="'.WP_WsysAdmin::build_url('','wsysadmin_publishers','togglePosts',array('id' => $item['id'], 'status' => WSYS_Publisher::STATUS_ACTIVE)).'" class="confirm" data-confirmation="Are you sure you want to publish all posts from this publisher?">Show all posts</a>';
        }

        return sprintf('%1$s %2$s', '<strong><a href="'.WP_WsysAdmin::build_url('','wsysadmin_publishers','edit',array('id' => $item['id'])).'">'.$item['name'].'</a></strong>', $this->row_actions($actions) );
    }

    function column_url($item){
        return '<a href="'.$item['url'].'" target"_blank">'.$item['url'].'</a>';
    }

    function column_post_count($item){
        return ($item['published_post_count']+$item['hidden_post_count']).($item['hidden_post_count']>0 ? ' ('.$item['hidden_post_count'].' hidden)' : '');
    }

    function get_bulk_actions() {
        $actions = array(
            //'delete'    => 'Delete'
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="publisher[]" value="%s" />', $item['id']
        );
    }

    function display_search() {
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="wsysadmin_publishers" />';
        $this->search_box('search', 'search_id');
        echo '</form>';

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
        $orderBy = isset($_GET['orderby']) ? $_GET['orderby'] : 'created_at';
        $order = isset($_GET['order']) ? $_GET['order'] : 'asc';
        $count = WSYS_Publisher::get_count($filters);
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = $count;
        $results = WSYS_Publisher::get_items($filters,$orderBy,$order,$per_page,($current_page-1)*$per_page);

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );
        $this->items = $results;
    }

    public function column_status($item) {
        $statuses = WSYS_Publisher::getStatuses();
        return array_key_exists($item['status'],$statuses) ? $statuses[$item['status']] : '';
    }
}