<?php

class WSYS_Advertisers {

	private $advertisers = array();

	function __construct(){
		$this->advertisers = $this->get_advertisers();
	}

	function get_advertisers(){
		$taxonomy = array('advertiser');
		$args = array(
		    'orderby'           => 'name', 
		    'order'             => 'ASC',
		    'hide_empty'        => false
		); 
		$terms = get_terms($taxonomy, $args);
		return $terms;
	}

	function add_post(&$post,$cron=false){
		$count = 0;
		$terms = array();
		if (empty($this->advertisers)) {
			return $post;
		} else {
			foreach ($this->advertisers as $advertiser) {
				if (stripos($post['post_title'],$advertiser->name) !== FALSE || stripos($post['post_content'],$advertiser->name) !== FALSE) {
					array_push($terms, $advertiser->term_id);
					$count=1;
				}
			}
				if ($count > 0) {
					if ($cron) {
						$taxonomy = 'advertiser';
						wp_set_post_terms( $post['ID'], $terms, $taxonomy );
						echo "Found article ".$post['post_title']."</br>
";
					} else {
						$post['tax_input'] = array('advertiser' => $terms); 
					}
			}
			return $post;
		}
		
	}



	
}