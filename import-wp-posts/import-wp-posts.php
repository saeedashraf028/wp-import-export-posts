<?php 
   /*
	Plugin Name: Import WP Posts
	Plugin URI: http://assignment.com
	description: >-a plugin to import wordpress posts from json format, ANY existing posts will be updated.
	Version: 1.0
	Author: Muahammad Saeed
	Author URI: www.linkedin.com/in/saeedashraf
	License: GPL2
   
	Copyright 2019 Muhammad Saeed (email : saeed028@gmail.com)
	(Import WP Posts) is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.
	 
	(Import WP Posts) is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	 
	You should have received a copy of the GNU General Public License
	along with (Import WP Posts). If not, see (http://assignment.com/license).
   */
	
	if ( preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF']) ) {
		die('You are not allowed to call this page directly.');
	}
	
	/*
		Add Menu Page, for user to click for import
	*/
	add_action( 'admin_menu', 'add_Custom_menu' );
	function add_Custom_menu(){
		add_menu_page(
				'Import WP Posts',
				'Import WP Posts',
				'manage_options',
				'import-posts',
				'import_posts',
				'dashicons-admin-post',
				6
			);
	}
	
	function import_posts(){
	
		// base directory path, used to store fetch files.	
		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['basedir'];
		$target_dir = $dir.'/wp-posts';	
		if (!file_exists($target_dir)) {
			echo '<div style=" background: aliceblue; padding: 10px 20px; margin: 20px; border: 1px solid #c7d0d8;">';
				echo "Products can not be import, wp-posts directory does not exist in wp-content/uploads/ see WP Export Plugin for details.";
			echo '</div>';
			die();
		}
		
		$page = 0; //add page number from json file
		
		$path = $target_dir.'/posts-page-'.$page.'.json';
		$posts = file_get_contents($path);
		$posts = json_decode($posts);
		
		$updated = 0;
		$inserted = 0;

		if(!empty($posts)){
			foreach($posts as $post){
				$path = $target_dir.'/post-id-'.$post.'.json';
				$post_details = file_get_contents($path);
				$post_details = json_decode($post_details);
				
				$post_data = [];
				
				if(!empty($post_details)){
					
					$post_data['post_title'] = $post_details->post_title;
					$post_data['post_content'] = $post_details->post_content;
					$post_data['post_excerpt'] = $post_details->post_excerpt;
					$post_data['comment_count'] = $post_details->comment_count;
					$post_data['post_status'] = 'publish';
					$post_data['tags_input'] = $post_details->tags_input; 
					
					global $wpdb;
					//check if post already exists
					$query = "SELECT ID FROM $wpdb->posts WHERE post_title = '" . esc_sql($post_data['post_title']) . "' AND post_type = 'post' AND ( post_status= 'publish' OR post_status= 'draft' OR  post_status = 'pending' OR post_status = 'future' ) ";
					$post_id = $wpdb->get_var( $query );
					
					if($post_id){
						$post_data['ID'] = $post_id;
						wp_update_post( $post_data );
						$updated++;
					}else{
						$post_id = wp_insert_post( $post_data );
						$inserted++;
					}
					
					//set categories for post
					wp_set_post_categories ( $post_id, $post_details->term_ids, true );
					
					//set featured image for post
					if( $post_details->featured_img !='' ){
						add_Featured_Image( $post_details->featured_img, $post_id );
					}
					
				}//$post_data
				
			}//foreach
		}
		
		echo '<div style=" background: aliceblue; padding: 10px 20px; margin: 20px; border: 1px solid #c7d0d8;">';
			echo $updated." Posts Updated <br />";
			echo $inserted." Posts Inserted";
		echo '</div>';
		
	}//import_posts

	function add_Featured_Image( $image_url, $post_id  ){
		
		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents($image_url);
		$filename = sanitize_file_name(basename($image_url));
		if(wp_mkdir_p($upload_dir['path']))     $file = $upload_dir['path'] . '/' . $filename;
		else                                    $file = $upload_dir['basedir'] . '/' . $filename;
		file_put_contents($file, $image_data);

		$wp_filetype = wp_check_filetype($filename, null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title' => sanitize_file_name($filename),
			'post_excerpt' => '', 
			'post_content' => '', 
			'post_status' => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		$res1= wp_update_attachment_metadata( $attach_id, $attach_data );
		$res2= set_post_thumbnail( $post_id, $attach_id );
	}