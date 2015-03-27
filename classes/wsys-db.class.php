<?php

class WSYS_DB_Table {

    static $table_name = '';

    public static $columns = array(
        'id' => array(
            'db_field' => 'id',
            'label' => 'ID',
            'sortable' => true,
            'hidden' => false,
            'type' => '%d',
        ),
    );

    function __construct(){
    }

    public static function insert($data) {
        global $wpdb;
        if(empty($data)) {
            return new WP_Error('no data','No data for insert provided');
        }

        $set = array();
        $values = array();
        foreach(static::$columns as $field=>$column) {
            if(array_key_exists($field,$data)) {
                $set[] = $column['db_field']."=".$column['type'];
                $values[] = $data[$field];
            }
        }
        $res = $wpdb->query($wpdb->prepare("insert into ".static::$table_name." set ".implode(', ',$set),$values));
        if(!is_wp_error($res)) {
            return $wpdb->insert_id;
        }
        return $res;
    }

    public static function update($data) {
        global $wpdb;
        if(!isset($data['id']) || !$data['id']) {
            return new WP_Error('no data','missing id for update');
        }

        $id = $data['id'];
        unset($data['id']);
        if(empty($data)) {
            return new WP_Error('no data','No data for update provided');
        }
        $set = array();
        $values = array();
        foreach(static::$columns as $field=>$column) {
            if(array_key_exists($field,$data)) {
                $set[] = $column['db_field']."=".$column['type'];
                $values[] = $data[$field];
            }
        }
        $res = $wpdb->query($wpdb->prepare("update ".static::$table_name." set ".implode(', ',$set),$values).$wpdb->prepare(" where id=%d",$id));
        //$res = $wpdb->query($wpdb->prepare("update ".self::$table_name." set tags=%s, category_id=%d where id=%d",$data['tags'],$data['category_id'],$data['id']));
        return $res;
    }

    public static function delete($id) {
        global $wpdb;
        if(is_array($id) && empty($id)) {
            return false;
        }
        if(!is_array($id) && !$id) {
            return false;
        }
        if(!is_array($id)) {
            $id = array($id);
        }
        $res = $wpdb->query("delete from ".static::$table_name." where id in (".implode(',',$id).")");
        return $res;
    }

    public static function get($id) {
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare("select * from ".static::$table_name." where id=%d",$id), ARRAY_A);
        return $result;
    }

    public static function get_items($filters) {
        global $wpdb;
        $where = static::get_filters_sql($filters);
        $results = $wpdb->get_results("select * from ".static::$table_name." ".$where." order by id asc ", ARRAY_A);
        return $results;
    }

    public static function get_count($filters) {
        global $wpdb;
        $where = static::get_filters_sql($filters);
        return $wpdb->get_var("select count(*) from ".static::$table_name." ".$where);
    }

    public static function get_filters_sql($filters) {
        global $wpdb;
        $where = array();
        foreach($filters as $filter) {
            if(is_array($filter) && isset($filter['field']) && isset($filter['value']) && array_key_exists($filter['field'],static::$columns)) {
                $operator = isset($filter['operator']) ? $filter['operator'] : '=';
                switch($operator) {
                    case "like":
                        $where[] = static::$table_name.".`".$filter['field']."` like '%".$filter['value']."%'";
                        break;
                    case "in":
                        $where[] = static::$table_name.".`".$filter['field']."` in (".implode(',',$filter['value']).")";
                        break;
                    default:
                        $where[] = $wpdb->prepare(static::$table_name.".`".static::$columns[$filter['field']]['db_field']."` ".$operator." ".static::$columns[$filter['field']]['type'],$filter['value']);
                }
            }
        }
        if(count($where)>0) {
            return "where ".implode(' and ',$where);
        }
        return '';
    }

}
