<?php

class WSYS_Rule extends WSYS_DB_Table {

    static $table_name = 'wsys_rule';

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
        'tags' => array(
            'db_field' => 'tags',
            'label' => 'Tags',
            'sortable' => false,
            'hidden' => false,
            'type' => '%s',
        ),
        'categories' => array(
            'db_field' => 'categories',
            'label' => 'Categories',
            'sortable' => false,
            'hidden' => false,
            'type' => '%s',
        ),
        'publisher_id' => array(
            'db_field' => 'publisher_id',
            'label' => 'Publisher',
            'sortable' => true,
            'hidden' => true,
            'type' => '%d',
        ),
    );

    function __construct(){
    }

}
