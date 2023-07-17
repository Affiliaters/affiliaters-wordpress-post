<?php
/*
Plugin Name: Affiliaters.in Wordpress Poster
Plugin URI:  https://affiliaters.in
Description:  This plugin expose an API to create post with title, content, featured image for post.
Version:     0.1
Author:      Govind Tiwari
Author URI:  https://github.com/Affiliaters/affiliaters-wordpress-post
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: affiliaters-post-creator
*/

defined( 'ABSPATH' ) or die( 'No direct access!' );


require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
// Creating the endpoint for the API
add_action('rest_api_init', 'affiliaters_post_creator_endpoint');
function affiliaters_post_creator_endpoint() {
  register_rest_route(
    'affiliaters-post-creator/v1',
    '/create-post/',
    array(
      'methods'  => 'POST',
      'callback' => 'affiliaters_create_post',
      'args' => array(
        'post_type' => array(
            'required' => true,
            'validate_callback' => function($param, $request, $key) {
              return is_string($param);
            }
        ),
        'title' => array(
            'required' => true,
            'validate_callback' => function($param, $request, $key) {
              return is_string($param);
            }
        ),
        'content' => array(
            'required' => true,
            'validate_callback' => function($param, $request, $key) {
              return is_string($param);
            }
        ),
        'featured_img_url' => array(
            'required' => true,
            'validate_callback' => function($param, $request, $key) {
              return is_string($param);
            }
        ),
        'custom_meta' => array(
            'required' => false,
            'validate_callback' => function($param, $request, $key) {
              return is_array($param);
            }
        ),
        'external_url' => array(
            'required' => false,
            'validate_callback' => function($param, $request, $key) {
              return is_string($param);
            }
        ),
        'affiliate_url' => array(
            'required' => false,
            'validate_callback' => function($param, $request, $key) {
              return is_string($param);
            }
        ),
        'affiliate_product_name' => array(
            'required' => false,
            'validate_callback' => function($param, $request, $key) {
              return is_string($param);
            }
        ),
      ),
    )
  );
}

// Creating the function for the API endpoint
function affiliaters_create_post( WP_REST_Request $request ) {
  $post_type = sanitize_text_field( $request->get_param('post_type') );
  $title = sanitize_text_field( $request->get_param('title') );
  $content = $request->get_param('content');
  $featured_img_url = sanitize_text_field( $request->get_param('featured_img_url') );
  $custom_meta = $request->get_param('custom_meta');
  $external_url = $request->get_param('external_url');
  $affiliate_url = $request->get_param('affiliate_url');
  $affiliate_product_name = $request->get_param('affiliate_product_name');

  $post_id = '';
  
  if( $post_type == 'post' ) {
    // Create the post
    $post_id = wp_insert_post( array(
      'post_title' => $title,
      'post_content' => $content,
      'post_status' => 'publish'
    ) );

    // Set the featured image
    if( $featured_img_url ) {
      // Generate attachment metadata
      $image_id = media_sideload_image( $featured_img_url, $post_id, '', 'id');
      // Update attachment metadata
      $metadata = wp_generate_attachment_metadata($image_id, get_attached_file($image_id));
      // Update attachment metadata
      wp_update_attachment_metadata($image_id, $metadata);
      // Set the image as the post's featured image
      set_post_thumbnail($post_id, $image_id);
    }
    // Set the custom meta
    if( $custom_meta ) {
      foreach( $custom_meta as $meta_key => $meta_value ) {
        update_post_meta( $post_id, $meta_key, $meta_value );
      }
    }
  }
  // Return the response
  $response = new WP_REST_Response( array(
    'post_id' => $post_id,
  ) );
  return $response;
}

// Basic authentication for API
add_filter( 'rest_authentication_errors', function( $result ) {
  if ( ! empty( $result ) ) {
    return $result;
  }
  if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
    return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to do this.' ), array( 'status' => rest_authorization_required_code() ) );
  }
  $user = wp_authenticate( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
  if ( is_wp_error( $user ) ) {
    return $user;
  }
  if ( ! user_can( $user, 'edit_posts' ) ) {
    return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to do this.' ), array( 'status' => rest_authorization_required_code() ) );
  }
  wp_set_current_user( $user->ID );
  return true;
});
