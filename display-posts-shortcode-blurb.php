<?php
/**
 * Plugin Name: Blurbs (Display Posts Shortcode Extension)
 * Plugin URI: https://www.fastbizmarketing.com/display-posts-shortcode-blurb/
 * Description: Overrides the default output of the [display-posts] shortcode to display a post's blurb.
 * Version: 1.0.0
 * Author: Jitesh Patil (FastBiz Marketing)
 * Author URI: https://www.fastbizmarketing.com
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package Display Posts Blurb
 * @version 1.0.0
 * @author Jitesh Patil <hello@fastbizmarketing.net>
 * @copyright Copyright (c) 2016, FastBiz Marketing
 * @link https://www.fastbizmarketing.com/display-posts-shortcode-blurb/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

class FBM_Blurbs {
	
	/**
	 *	Static property to hold the singleton instance
	 */
	static $instance = false;
	
	/**
	 *	Display posts shortcode blurb class constructor
	 *
	 *	@return void
	 */
	private function __construct() {
		add_action( 'plugins_loaded',					array( $this, 'load_textdomain' ) );
		add_action( 'add_meta_boxes',					array( $this, 'add_blurb_meta_box' ) );
		add_action( 'save_post',						array( $this, 'save_blurb' ), 1 );
		add_filter( 'display_posts_shortcode_output',	array( $this, 'display_blurb' ), 10, 9 );
	}
	
	/**
	 *	Returns the singleton instance of this class
	 *
	 *	@return FBM_Display_Posts_Shortcode_Blurb
	 */
	public static function get_instance() {
		if( ! self::$instance ) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
	
	/**
	 *	Load the plugin's text domain
	 *
	 *	@return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 
			'display-posts-shortcode-blurb', 
			false, 
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' 
		);
	}
	
	/**
	 *	Add the blurb meta box to post & page edit screens
	 *
	 *	@return void
	 */
	public function add_blurb_meta_box() {
		$screens = apply_filters( 'fbm_blurb_screens', array( 'post', 'page' ) );
		
		foreach( $screens as $screen ) {
			add_meta_box( 
				'fbm_blurb_meta_box', 
				__( 'Blurb', 'display-posts-shortcode-blurb' ), 
				array( $this, 'blurb_meta_box_html' ), 
				$screen
			);
		}
	}
	
	/**
	 *	Output the blurb meta box form html
	 *
	 *	@param $post the post edit screen on which the meta box html is output
	 *
	 *	@return void
	 */
	public function blurb_meta_box_html( $post ) {
		wp_nonce_field( basename( __FILE__ ), 'fbm_nonce' );
		
		$blurb = get_post_meta( $post->ID, 'fbm_blurb', true );
		
		wp_editor( htmlspecialchars_decode( $blurb ), 'fbm_blurb', array(
			'textarea_rows'	=> 5,
		) );
	}
	
	/**
	 *	Save the blurb
	 *
	 *	@param $post_id the post Id for which to save the blurb
	 *
	 *	@return void
	 */
	public function save_blurb( $post_id ) {
		// Check save status
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );
		
		$is_valid_nonce = false;
		if( isset( $_POST['fbm_nonce'] ) && wp_verify_nonce( $_POST['fbm_nonce'], basename( __FILE__ ) ) ) {
			$is_valid_nonce = true;
		}
		
		// Return if save status is not valid
		if( $is_autosave || $is_revision || ! $is_valid_nonce ) {
			return;
		}
		
		// Check & save sanitized input
		if( isset( $_POST['fbm_blurb'] ) ) {
			update_post_meta( $post_id, 'fbm_blurb', wp_kses_post( $_POST['fbm_blurb'] ) );
		}
	}
	
	/**
	 *	Display the blurb, if available
	 *
	 * 	@param $output string, the original markup for an individual post
	 * 	@param $atts array, all the attributes passed to the shortcode
	 * 	@param $image string, the image part of the output
	 * 	@param $title string, the title part of the output
	 * 	@param $date string, the date part of the output
	 * 	@param $excerpt string, the excerpt part of the output
	 * 	@param $inner_wrapper string, what html element to wrap each post in (default is li)
	 * 	@param $content string, post content
	 * 	@param $class array, post classes	 
	 *
	 * 	@return $output string, the modified markup for an individual post
	 */
	public function display_blurb( $output, $atts, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class ) {
		// Check if the blurb should be shown?
		if( ! isset( $atts['show_blurb'] ) || 'true' != $atts['show_blurb'] ) {
			return $output;
		}
		
		// Get the post blurb
		global $post;
		$blurb = get_post_meta( $post->ID, 'fbm_blurb', true );
		
		// Set the output to the blurb if available
		if( $blurb ) {
			$output = '<' . $inner_wrapper . ' class="' . implode( ' ', $class ) . '">' . wp_kses_post( $blurb ) . '</' . $inner_wrapper . '>';
		}
		
		// Return the output
		return $output;
	}
}

/**
 *	Instantiate our class
 */
$fbm_blurbs = FBM_Blurbs::get_instance();