<?php

class wsys_stats extends wp_slimstat_reports {
    public static function show_results($_type = 'recent', $_id = 'p0', $_column = 'id', $_args = array()){
        // Initialize default values, if not specified
        $_args = array_merge(array('custom_where' => '', 'more_columns' => '', 'join_tables' => '', 'having_clause' => '', 'order_by' => '', 'total_for_percentage' => 0, 'as_column' => '', 'filter_op' => 'equals', 'use_date_filters' => true), $_args);
        $column = !empty($_args['as_column'])?$_column:wp_slimstat_db::get_table_alias($_column).'.'.$_column;

        // Get ALL the results
        $temp_starting = wp_slimstat_db::$filters_normalized['misc']['start_from'];
        $temp_limit_results = wp_slimstat_db::$filters_normalized['misc']['limit_results'];
        wp_slimstat_db::$filters_normalized['misc']['start_from'] = 0;
        wp_slimstat_db::$filters_normalized['misc']['limit_results'] = 9999;

        //$count_all_results = wp_slimstat_db::count_records();
        switch($_type){
            case 'recent':
                $all_results = wp_slimstat_db::get_recent($column, $_args['custom_where'], $_args['join_tables'], $_args['having_clause'], $_args['order_by'], $_args['use_date_filters']);
                break;
            case 'popular':
                $all_results = wp_slimstat_db::get_popular($column, $_args['custom_where'], $_args['more_columns'], $_args['having_clause'], $_args['as_column']);
                break;
            case 'popular_complete':
                $all_results = wp_slimstat_db::get_popular_complete($column, $_args['custom_where'], $_args['join_tables'], $_args['having_clause'], $_args['outer_select_column'], $_args['max_min']);
                break;
            case 'popular_outbound':
                $all_results = wp_slimstat_db::get_popular_outbound();
                break;
            default:
        }

        // Restore the filter
        wp_slimstat_db::$filters_normalized['misc']['start_from'] = $temp_starting;
        wp_slimstat_db::$filters_normalized['misc']['limit_results'] = $temp_limit_results;

        // Slice the array
        $results = array_slice($all_results, wp_slimstat_db::$filters_normalized['misc']['start_from'], wp_slimstat_db::$filters_normalized['misc']['limit_results']);

        $count_page_results = count($results);

        if ($count_page_results == 0){
            echo '<p class="nodata">'.__('No data to display','wp-slimstat').'</p>';
            return true;
        }

        // Sometimes we use aliases for columns
        if (!empty($_args['as_column'])){
            $_column = trim(str_replace('AS', '', $_args['as_column']));
        }

        wp_slimstat_reports::report_pagination($_id, $count_page_results, count($all_results));
        $is_expanded = (wp_slimstat::$options['expand_details'] == 'yes')?' expanded':'';

        // Traffic sources: display pageviews with no referrer
        if ($_column == 'referer'){
            $count_all = wp_slimstat_db::count_records();
            $count_no_referer = wp_slimstat_db::count_records('(t1.referer IS NULL OR t1.referer = "")');
            $percentage = number_format(sprintf("%01.2f", (100*$count_no_referer/$count_all)), 2, wp_slimstat_db::$formats['decimal'], wp_slimstat_db::$formats['thousand']);
            echo "<p>Direct Access <span>$percentage%</span> <b class='slimstat-row-details$is_expanded'>Hits: $count_no_referer</b></p>";
        }

        $_column_for_results = empty($_args['outer_select_column'])?$_column:$_args['outer_select_column'];
        for($i=0;$i<$count_page_results;$i++){
            $row_details = $percentage = '';
            $element_pre_value = '';
            $element_value = $results[$i][$_column_for_results];

            // Convert the IP address
            if (!empty($results[$i]['ip'])) $results[$i]['ip'] = long2ip($results[$i]['ip']);

            // Some columns require a special pre-treatment
            switch ($_column_for_results){
                case 'browser':
                    if (!empty($results[$i]['user_agent']) && wp_slimstat::$options['show_complete_user_agent_tooltip'] == 'yes') $element_pre_value = self::inline_help($results[$i]['user_agent'], false);
                    $element_value = $results[$i]['browser'].((isset($results[$i]['version']) && intval($results[$i]['version']) != 0)?' '.$results[$i]['version']:'');
                    break;
                case 'category':
                    $row_details .= '<br>'.__('Category ID','wp-slimstat').": {$results[$i]['category']}";
                    $cat_ids = explode(',', $results[$i]['category']);
                    if (!empty($cat_ids)){
                        $element_value = '';
                        foreach ($cat_ids as $a_cat_id){
                            if (empty($a_cat_id)) continue;
                            $cat_name = get_cat_name($a_cat_id);
                            if (empty($cat_name)) {
                                $tag = get_term($a_cat_id, 'post_tag');
                                if (!empty($tag->name)) $cat_name = $tag->name;
                            }
                            $element_value .= ', '.(!empty($cat_name)?$cat_name:$a_cat_id);
                        }
                        $element_value = substr($element_value, 2);
                    }
                    break;
                case 'country':
                    $row_details .= '<br>'.__('Country Code','wp-slimstat').": {$results[$i]['country']}";
                    $element_value = __('c-'.$results[$i]['country'], 'wp-slimstat');
                    break;
                case 'ip':
                    if (wp_slimstat::$options['convert_ip_addresses'] == 'yes'){
                        $element_value = gethostbyaddr($results[$i]['ip']);
                    }
                    else{
                        $element_value = $results[$i]['ip'];
                    }
                    break;
                case 'language':
                    $row_details = '<br>'.__('Language Code','wp-slimstat').": {$results[$i]['language']}";
                    $element_value = __('l-'.$results[$i]['language'], 'wp-slimstat');
                    break;
                case 'platform':
                    $row_details = '<br>'.__('OS Code','wp-slimstat').": {$results[$i]['platform']}";
                    $element_value = __($results[$i]['platform'], 'wp-slimstat');
                    break;
                case 'resource':
                    $post_id = url_to_postid(strtok($results[$i]['resource'], '?'));
                    if ($post_id > 0) $row_details = '<br>'.htmlentities($results[$i]['resource'], ENT_QUOTES, 'UTF-8');
                    $element_value = self::get_resource_title($results[$i]['resource']);
                    break;
                case 'searchterms':
                    if ($_type == 'recent'){
                        $row_details = '<br>'.__('Referrer','wp-slimstat').": {$results[$i]['domain']}";
                        $element_value = self::get_search_terms_info($results[$i]['searchterms'], $results[$i]['domain'], $results[$i]['referer'], true);
                    }
                    else{
                        $element_value = htmlentities($results[$i]['searchterms'], ENT_QUOTES, 'UTF-8');
                    }
                    break;
                case 'user':
                    $element_value = $results[$i]['user'];
                    if (wp_slimstat::$options['show_display_name'] == 'yes'){
                        $element_custom_value = get_user_by('login', $results[$i]['user']);
                        if (is_object($element_custom_value)) $element_value = $element_custom_value->display_name;
                    }
                    break;
                case 'visit_id':
                    $element_value = $results[$i]['resource'];
                    break;
                default:
            }

            $element_value = "<a class='slimstat-filter-link' href='".self::fs_url($_column_for_results.' '.$_args['filter_op'].' '.$results[$i][$_column_for_results])."'>$element_value</a>";

            if ($_type == 'recent'){
                $row_details = date_i18n(wp_slimstat::$options['date_format'].' '.wp_slimstat::$options['time_format'], $results[$i]['dt'], true).$row_details;
            }
            else{
                $percentage = ' <span>'.$results[$i]['counthits'].'</span>';
                $row_details = __('Hits','wp-slimstat').': '.number_format($results[$i]['counthits'], 0, wp_slimstat_db::$formats['decimal'], wp_slimstat_db::$formats['thousand']).$row_details;
            }

            // Some columns require a special post-treatment
            if ($_column_for_results == 'resource' && strpos($_args['custom_where'], '404') === false){
                $base_url = '';
                if (isset($results[$i]['blog_id'])){
                    $base_url = parse_url(get_site_url($results[$i]['blog_id']));
                    $base_url = $base_url['scheme'].'://'.$base_url['host'];
                }
                $element_value = '<a target="_blank" class="slimstat-font-logout" title="'.__('Open this URL in a new window','wp-slimstat').'" href="'.$base_url.htmlentities($results[$i]['resource'], ENT_QUOTES, 'UTF-8').'"></a> '.$base_url.$element_value;
            }
            if ($_column_for_results == 'referer'){
                $element_url = htmlentities($results[$i]['referer'], ENT_QUOTES, 'UTF-8');
                $element_value = '<a target="_blank" class="slimstat-font-logout" title="'.__('Open this URL in a new window','wp-slimstat').'" href="'.$element_url.'"></a> '.$element_value;
            }
            if (!empty($results[$i]['ip']) && $_column_for_results != 'ip' && wp_slimstat::$options['convert_ip_addresses'] != 'yes'){
                $row_details .= '<br> IP: <a class="slimstat-filter-link" href="'.self::fs_url('ip equals '.$results[$i]['ip']).'">'.$results[$i]['ip'].'</a>'.(!empty($results[$i]['other_ip'])?' / '.long2ip($results[$i]['other_ip']):'').'<a title="WHOIS: '.$results[$i]['ip'].'" class="slimstat-font-location-1 whois" href="'.wp_slimstat::$options['ip_lookup_service'].$results[$i]['ip'].'"></a>';
            }
            if (!empty($row_details)){
                $row_details = "<b class='slimstat-row-details$is_expanded'>$row_details</b>";
            }

            echo "<p>$element_pre_value$element_value$percentage $row_details</p>";
        }
    }

}