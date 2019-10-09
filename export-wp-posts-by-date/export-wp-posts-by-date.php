<?php 
   /*
	Plugin Name: Export WP Posts
	Plugin URI: http://assignment.com
	description: >-a plugin to export wordpress posts in json format, Date filter can be applied to export filterd posts.
	Version: 1.0
	Author: Muahammad Saeed
	Author URI: www.linkedin.com/in/saeedashraf
	License: GPL2
   
	Copyright 2019 Muhammad Saeed (email : saeed028@gmail.com)
	(Export WP Posts) is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.
	 
	(Export WP Posts) is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	 
	You should have received a copy of the GNU General Public License
	along with (Export WP Posts). If not, see (http://assignment.com/license).
   */
	
	if ( preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF']) ) {
		die('You are not allowed to call this page directly.');
	}
	
	/*
		Add Menu Page, for user to click for export
	*/
	add_action( 'admin_menu', 'add_Custom_menu' );
	function add_Custom_menu(){
		add_menu_page(
				'Export WP Posts',
				'Export WP Posts',
				'manage_options',
				'export-posts',
				'export_posts',
				'dashicons-admin-post',
				6
			);
	}
	
	function export_posts(){
	
		// base directory path, used to store json files.	
		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['basedir'];
		$target_dir = $dir.'/wp-posts';	
		if (!file_exists($target_dir)) {
			mkdir($target_dir); //craete directory if not exists.
		}
		
		$page = 0;
		$limit = 20;
		$offset = $page * $limit;

		$query_string = array(
		  'post_type' => 'post', 
		  'posts_per_page' => 20,
		  'offset' => $offset,
		  'date_query' => array(
			'column' => 'post_date',
			'after' => '2019-09-20', //enter date start
			'before' => '2019-10-10' //enter date end
		  ),
		  'post_status' => 'publish'
		);

		$query = new WP_Query( $query_string );

		$count = 0;
		$wp_posts = [];

		if ( $query->have_posts() ) {
		 
			while ( $query->have_posts() ) {	
			
				$post_data = [];
			
				$query->the_post();
				$post_id = get_the_ID();
				
				//export post categories
				$term_ids = [];
				$terms = get_the_terms( $post_id, 'category' );
				if(!empty($terms))
				{
					foreach($terms as $term){
						$term_ids[] = $term->term_id;
					}
				}
				
				//export post tags
				$tags = get_the_tags( $post_id );
				$tag_array = [];
				if(!empty($tags)){
					foreach($tags as $tag){
						$tag_array[] = $tag->name;
					}
				}
				
				$post_details =  get_post( $post_id );
				$post_data['post_title'] = $post_details->post_title;
				$post_data['post_content'] = $post_details->post_content;
				$post_data['post_excerpt'] = $post_details->post_excerpt;
				$post_data['comment_count'] = $post_details->comment_count;
				$post_data['term_ids'] =  $term_ids;
				$post_data['tags_input'] = $tag_array;
				
				$featured_img = get_the_post_thumbnail_url( $post_id );
				if($featured_img!=''){
					$post_data['featured_img'] =  $featured_img;
				}
						
				$wp_posts[] = $post_id;
				
				$path = $target_dir.'/post-id-'.$post_id.'.json';
				file_put_contents($path, json_encode($post_data) );
				
				$count++;
				//break;
			} 
		}

		$path = $target_dir.'/posts-page-'.$page.'.json';
		file_put_contents($path, json_encode($wp_posts) );

		echo '<div style=" background: aliceblue; padding: 10px 20px; margin: 20px; border: 1px solid #c7d0d8;">';
			echo $count." Posts Exported in wp-content/uploads/wp-posts/";
		echo '</div>';
	
	}//export_posts