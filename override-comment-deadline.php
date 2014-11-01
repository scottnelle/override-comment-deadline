<?php
/*
Plugin Name: Override Comment Deadline
Plugin URI: http://www.scottnelle.com
Description: Allows you to enable comments on posts which are older than the comment deadline set in discussion settings
Author: Scott Nelle, Union Street Media
Author URI: http://www.scottnelle.com
Version: 1.0
*/

/*  Copyright 2014  Scott Nelle  (email : contact@scottnelle.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* define the meta box content for the post edit screen */
function ocd_render_meta_box( $post ) {
	if (get_option('close_comments_for_old_posts')) {
		$checked = '';
		$check = get_post_meta($post->ID, 'override_comment_deadline', true);
		if ($check == 1) { $checked = ' checked="checked"'; }
		wp_nonce_field( plugin_basename( __FILE__ ), 'ocd_nonce' );
		echo '<input type="checkbox" name="override-comment-deadline" id="override-comment-deadline" value="1"'.$checked.' /> ';
		echo '<label for="override-comment-deadline">Keep Comments Open</label>';
		echo '<p class="howto">You have comments set to disabled for posts which are older than '.get_option('close_comments_days_old').' days. You can override that for this post.</p>';
	}
	else {
		echo '<p>This plugin is not useful unless you have comments <a href="'.site_url().'/wp-admin/options-discussion.php">disabled on older posts</a>.</p>';
	}
}

/* add the meta box to the post edit screen */
function ocd_add_meta() {  
	add_meta_box( 'comment-override-box', 'Override Comment Deadline', 'ocd_render_meta_box', 'post', 'side', 'low' );  
}  
add_action( 'add_meta_boxes', 'ocd_add_meta' );

/* save the meta box when the post is saved */
function ocd_save($post_id) {
	// autosave
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return; 
	// nonce check
	if( !isset( $_POST['ocd_nonce'] ) || !wp_verify_nonce( $_POST['ocd_nonce'], plugin_basename( __FILE__ ) ) ) return;
	// permission check
    if ( !current_user_can( 'edit_post', $post_id ) ) return;
	
	$chk = isset( $_POST['override-comment-deadline'] ) && $_POST['override-comment-deadline'] == 1 ? 1 : 0;
	update_post_meta( $post_id, 'override_comment_deadline', $chk ); 
}
add_action( 'save_post', 'ocd_save' );

/* override the comment status if this post has been set to do so */
function ocd_comment_check($open) {
	if ( get_option('default_comment_status') != 'open' ) { return false; }
	
	if ( !get_option('close_comments_for_old_posts') ) { return $open; }
	
	global $post;
	// if the post has a comment override, use that
	if (get_post_meta($post->ID, 'override_comment_deadline', true) == 1 ) { return true; }
	
	// if not, go with what's already in place
	return $open;
}
add_action( 'comments_open', 'ocd_comment_check',10,1 );
?>