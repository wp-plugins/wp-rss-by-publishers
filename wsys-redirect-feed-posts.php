<?php

const SKIP_REDIRECT_KEY = 'skipr';

/**
 * When a post is viewed this will check if it is comming from a feed
 * - if YES redirect to source
 */
function redirect_feed_posts() {
    if(is_singular()) {
        global $post;

        $redirect = esc_attr( get_option( 'wsys-post-redirect' ) );

        if($redirect) {
            // Is coming from feed?
            $pid = $post->ID;
            $feedId = get_post_meta( $pid, 'feed_id', true);

            if($feedId && !skipRedirect()) {
                $url = apply_filters('append_utm_source',$post->guid);
                wp_redirect($url, 301);
                exit();
            }
        }
    }
}
add_action( 'wp', 'redirect_feed_posts' );

/**
 * Some browsers may pre-fetch content. Because of this "next" links will register stats even if the user is not actually getting there
 * - if a pre-fetch header is found, return a blank response with 404 Header status
 */
function prefetch_detected() {
    $headers = getallheaders();
    if($headers) {
        foreach ($headers as $key => $value) {
            if (strpos(strtolower($value), 'prefetch') !== false) {
                header("HTTP/1.0 403 forbidden");
                die('Prefetch is now allowed here!');
            }
        }
    }
}
add_action( 'init', 'prefetch_detected' );

// Gets a list of headers
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Checks for a skip redirect key
 * @return bool
 */
function skipRedirect() {
    $skipRedirect = 0;
    if (isset($_GET[SKIP_REDIRECT_KEY]))
    {
        $skipRedirect = $_GET[SKIP_REDIRECT_KEY];
    }
    return !!$skipRedirect;
}

function wsys_utm_source($url) {
    $utm_source = get_option('wsys-post-utm_source','');
    if($utm_source) {
        if(function_exists('http_build_url')) {
            $url = http_build_url($url,
                array(
                    "query" => "utm_source=".$utm_source
                ),
                HTTP_URL_JOIN_QUERY
            );
        }
        else {
            $parts = parse_url($url);
            parse_str((isset($parts['query']) ? $parts['query'] : ''), $query_args);
            $query = array_merge($query_args,array('utm_source'=>$utm_source));
            $url = (isset($parts['scheme']) ? $parts['scheme'] : 'http').'://'.(isset($parts['host']) ? $parts['host'] : '').(isset($parts['port']) ? ':'.$parts['port'] : '').(isset($parts['path']) ? $parts['path'] : '').'?'.http_build_query($query);
        }
    }
    return $url;
}
add_filter('append_utm_source','wsys_utm_source');