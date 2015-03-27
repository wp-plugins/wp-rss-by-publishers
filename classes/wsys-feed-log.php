<?php

class WSYS_Feed_Log extends WSYS_DB_Table{

    const FETCH_MANUAL = 0;
    const FETCH_CRON = 1;
    const FETCH_PLUGIN = 2;

    static $table_name = 'wsys_feed_log';

    public static $columns = array(
        'id' => array(
            'db_field' => 'id',
            'label' => 'ID',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'feed_id' => array(
            'db_field' => 'feed_id',
            'label' => 'Feed',
            'sortable' => false,
            'hidden' => false,
            'type' => '%d',
        ),
        'fetch_time' => array(
            'db_field' => 'fetch_time',
            'label' => 'Fetch time',
            'sortable' => true,
            'hidden' => false,
            'type' => '%s',
        ),
        'fetch_type' => array(
            'db_field' => 'fetch_type',
            'label' => 'Fetch type',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'sandbox' => array(
            'db_field' => 'sandbox',
            'label' => 'Sandbox',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'duration' => array(
            'db_field' => 'duration',
            'label' => 'Duration',
            'sortable' => true,
            'hidden' => false,
            'type' => '%s',
        ),
        'posts_total' => array(
            'db_field' => 'posts_total',
            'label' => 'Total',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'posts_no_image' => array(
            'db_field' => 'posts_no_image',
            'label' => 'Without image',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'posts_inserted' => array(
            'db_field' => 'posts_inserted',
            'label' => 'Inserted',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'posts_updated' => array(
            'db_field' => 'posts_updated',
            'label' => 'Updated',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'posts_unchanged' => array(
            'db_field' => 'posts_unchanged',
            'label' => 'Unchanged',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'posts_errored' => array(
            'db_field' => 'posts_errored',
            'label' => 'Errored',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
        'messages' => array(
            'db_field' => 'messages',
            'label' => 'Log messages',
            'sortable' => false,
            'hidden' => false,
            'type' => '%s',
        ),
    );

    function __construct(){
    }

}
