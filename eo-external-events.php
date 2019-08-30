<?php
/**
 * Plugin Name: External Events for The Event Organizer
 * Plugin URI:  https://www.wpcodelabs.com/
 * Description: Extend The Event Organizer to include External Events
 * Version:     1.0.0
 * Author:      WP Code Labs
 * Author URI:  https://www.wpcodelabs.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: eo_external_events
 */

// If this file is called directly, abort
if ( !defined( 'WPINC' ) ) {
    die( 'Bugger Off Script Kiddies!' );
}

/**
 * Add additional field to the event detail metabox
 *
 * Used the 'eventorganiser_metabox_after_core_fields' action to add additional
 * metabox fields. Located on line 346 in event-organiser-edit.php (at the time of this writing)
 *
 * @param  [object] $post : post object
 */
function wpcl_external_events_add_eo_event_details( $post ) {

	$url = get_post_meta( $post->ID, '_event_external_url', true );

	$url = !empty( $url ) ? esc_url_raw( $url ) : '';

	include 'partials/details-metabox.php';
}
add_action( 'eventorganiser_metabox_after_core_fields', 'wpcl_external_events_add_eo_event_details' );

/**
 * Save the additional fields added to the event detail metabox
 *
 * @param  [int] $post_id : the id of the post
 * @see  https://developer.wordpress.org/reference/hooks/save_post/
 */
function wpcl_external_events_save_eo_event_details( $post_id ) {

	if( !isset( $_POST['_eononce'] ) || ! wp_verify_nonce( $_POST['_eononce'], 'eventorganiser_event_update_' . $post_id . '_' . get_current_blog_id() ) ) {
		return;
	}

	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if( !current_user_can( 'edit_event', $post_id ) ) {
		return;
	}

	if( !isset( $_POST['_event_external_url'] ) ) {
		return;
	}

	update_post_meta( $post_id, '_event_external_url', sanitize_text_field( $_POST['_event_external_url'] ) );
}
add_action( 'save_post', 'wpcl_external_events_save_eo_event_details' );

/**
 * Filter the permalink based on external link
 *
 * Checks to see if an event link has been set, and if so, returns that url in place
 * of the standard permalink. Also adds a query arg, so JS manipulation will be possible
 * in order to open in a new tab
 *
 * @param  [string] $post_link : the permalink to the post
 * @param  [object] $post : the WP_POST object
 * @param  [bool] $leavename : Whether to leave the post name
 * @param  [bool] $sample : Is it a sample permalink
 * @return [string] maybe modified permalink
 * @see    https://developer.wordpress.org/reference/hooks/post_type_link/
 * @see    https://developer.wordpress.org/reference/functions/add_query_arg/
 * @todo   Create JS to open in new tab based on query arg
 */
function wpcl_external_events_filter_permalink( $post_link, $post, $leavename, $sample ) {

	$url = get_post_meta( $post->ID, '_event_external_url', true );

	if( !empty( $url ) && $sample === false ) {
		$post_link = esc_url_raw( $url );
		$post_link = add_query_arg( 'location', 'external', $post_link );
	}

	return $post_link;
}
add_filter( 'post_type_link', 'wpcl_external_events_filter_permalink', 10, 4 );

/**
 * Redirect old permalink
 *
 * If an URL is specified, the existing permalink will be redirected in cases
 * where 'get_permalink' isn't being used to get the permalink
 *
 * @see  https://developer.wordpress.org/reference/functions/wp_redirect/
 */
function wpcl_external_events_redirect_external_events() {

	$url = get_post_meta( get_the_id(), '_event_external_url', true );

	if( !empty( $url ) && is_single( 'event' ) ) {
		if ( wp_redirect( esc_url_raw( $url ), 301 ) ) {
			exit;
		}
	}

}
add_action( 'wp', 'wpcl_external_events_redirect_external_events' );