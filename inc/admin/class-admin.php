<?php

namespace WP_Ping_Sites_Updater\Inc\Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @author    Niroma
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The text domain of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_text_domain    The text domain of this plugin.
	 */
	private $plugin_text_domain;

	/**
	 * The default ping list of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $plugin_ping_list;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since       1.0.0
	 * @param       string $plugin_name        The name of this plugin.
	 * @param       string $version            The version of this plugin.
	 * @param       string $plugin_text_domain The text domain of this plugin.
	 */
	public function __construct( $plugin_name, $version, $plugin_text_domain, $plugin_ping_list ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->plugin_text_domain = $plugin_text_domain;
		$this->plugin_ping_list = $plugin_ping_list;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-ping-sites-updater-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/*
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-ping-sites-updater-admin.js', array( 'jquery' ), $this->version, false );
		wp_localize_script($this->plugin_name, 'ajaxurl', admin_url( 'admin-ajax.php' ) );

	}

	public function display_plugin_setup_page() {
		include_once( 'views/html-wp-ping-sites-updater-admin-display.php' );
	}
	
	public function add_plugin_admin_menu() {

    /*
     * Add a settings page for this plugin to the Settings menu.
     *
     * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
     *
     *        Administration Menus: http://codex.wordpress.org/Administration_Menus
     *
     */
		add_submenu_page( 'options-general.php', 'Ping Sites Updater', 'Ping Sites Updater', 'manage_categories', $this->plugin_name, array($this, 'display_plugin_setup_page') );
	}
	
	
	public function check_for_event_submissions(){
			if ( isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], $this->plugin_name.'submit-list-form') ){
				$admin_notice = '';
				$messageLog = '';
				$pingListUrl = $_POST[$this->plugin_name.'-url'] ?  sanitize_text_field($_POST[$this->plugin_name.'-url']) : $this->plugin_ping_list;
				
				update_option( $this->plugin_name.'-url', $pingListUrl );
				
				$admin_notice = "success";
				$messageLog .= 'Settings saved';

				$this->custom_redirect( $admin_notice, $messageLog);
				die();
			}  else {
				wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
						'response' 	=> 403,
						'back_link' => 'options-general.php?page=' . $this->plugin_name,
				) );
			}
	}
	
	public function custom_redirect( $admin_notice, $response ) {
		wp_redirect( esc_url_raw( add_query_arg( array(
									'wp_ping_sites_updater_admin_add_notice' => $admin_notice,
									'wp_ping_sites_updater_response' => $response,
									),
							admin_url('options-general.php?page='. $this->plugin_name ) 
					) ) );

	}

	public function print_plugin_admin_notices() {              
		  if ( isset( $_REQUEST['wp_ping_sites_updater_admin_add_notice'] ) ) {
			if( $_REQUEST['wp_ping_sites_updater_admin_add_notice'] === "success") {
				$html =	'<div class="notice notice-success is-dismissible"> 
							<p><strong>' . htmlspecialchars( print_r( $_REQUEST['wp_ping_sites_updater_response'], true) ) . '</strong></p></div>';
				echo $html;
			}
			if( $_REQUEST['wp_ping_sites_updater_admin_add_notice'] === "error") {
				$html =	'<div class="notice notice-error is-dismissible"> 
							<p><strong>' . htmlspecialchars( print_r( $_REQUEST['wp_ping_sites_updater_response'], true) ) . '</strong></p></div>';
				echo $html;
			}
		  } else {
			  return;
		  }
	}

	public function get_ping_list() {              
		$remoteUrl = get_option( $this->plugin_name.'-url' );
		$response = wp_remote_get($remoteUrl);
		if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			$body    = $response['body']; // use the content
			if ( !empty( $body ) ){
				$body = str_replace("#WEBSITE_URL#", get_site_url(), $body);
				$body = str_replace("#WEBSITE_NAME#", sanitize_title_with_dashes(get_bloginfo('name')), $body);
				update_option( 'ping_sites', $body );
			}
		}
		
	}
	
	

}
