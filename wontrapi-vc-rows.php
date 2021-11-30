<?php 
/**
 * Plugin Name: Wontrapi VC Rows
 * Plugin URI:  https://github.com/oakwoodgates/wontrapi-vc-rows
 * Description: Hide Visual Composer rows based on a contact's tags in Ontraport
 * Version:     0.1.0
 * Author:      OakwoodGates
 * Author URI:  https://wpguru4u.com
 * Donate link: https://github.com/oakwoodgates/wontrapi-vc-rows
 * License:     GPLv2
 * Text Domain: wontrapi-vc-rows
 * Domain Path: /languages
 *
 * @link    https://github.com/oakwoodgates/wontrapi-vc-rows
 *
 * @package Wontrapi_VC_Rows
 * @version 0.1.0
 *
 */

/**
 * Copyright (c) 2019 OakwoodGates (email : wpguru4u@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


class Wontrapi_VC_Rows {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	const VERSION = '0.1.0';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages.
	 *
	 * @var    array
	 * @since  0.1.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    Wontrapi_VC_Rows
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   0.1.0
	 * @return  Wontrapi_VC_Rows A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Constructor
	 *
	 * @since  0.1.0
	 * @param  object $plugin Main plugin object.
	 * @return void
	 */
	public function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url 		= plugin_dir_url(  __FILE__ );
		$this->path 	= plugin_dir_path( __FILE__ );
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 */
	public function init() {

		// Load translated strings for plugin.
		load_plugin_textdomain( 'wontrapi-vc-rows', false, dirname( $this->basename ) . '/languages/' );

		$this->include_dependencies();

		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Initialize plugin classes.
		// $this->plugin_classes();
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.1.0
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements() {

		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @since  0.1.0
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {

		// Do checks for required classes / functions or similar.
		// Add detailed messages to $this->activation_errors array.
		if ( ! class_exists( 'Wontrapi') ) {
			return false;
		}

		return true;
	}

	public function include_dependencies() {
		if ( $this->is_parent_loaded() ) {
			$this->addon();
		} else if ( $this->is_parent_active() ) {
			add_action( 'wontrapi_fs_loaded', array( $this, 'addon' ) );
		} else {
			$this->addon();
		}
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 *
	 * @since  0.1.0
	 */
	public function requirements_not_met_notice() {

		// Compile default message.
		$default_message = sprintf( __( 'Wontrapi VC Rows is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'wontrapi-vc-rows' ), admin_url( 'plugins.php' ) );

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors && is_array( $this->activation_errors ) ) {
			$details = '<small>' . implode( '</small><br /><small>', $this->activation_errors ) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	public function is_parent_loaded() {
		if ( class_exists( 'Wontrapi' ) && ! empty( Wontrapi::$loaded ) ) {
			return true;
		}
		return false;
	}

	public function is_parent_active() {
		$active_plugins = get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins         = array_merge( $active_plugins, array_keys( $network_active_plugins ) );
		}

		foreach ( $active_plugins as $basename ) {
			if ( 0 === strpos( $basename, 'wontrapi/' ) ) {
				return true;
			}
		}

		return false;
	}

	public function addon() {

		global $wontrapi_vc_rows_fs;

		if ( ! isset( $wontrapi_vc_rows_fs ) ) {

			if ( file_exists( dirname( dirname( __FILE__ ) ) . '/wontrapi/vendor/freemius/start.php' ) ) {
				require_once dirname( dirname( __FILE__ ) ) . '/wontrapi/vendor/freemius/start.php';

				$wontrapi_vc_rows_fs = fs_dynamic_init( array(
					'id'                  => '4877',
					'slug'                => 'wontrapi-vc-rows',
					'type'                => 'plugin',
					'public_key'          => 'pk_a61d16974f99f0514c013fc6e8283',
					'is_premium'          => true,
					'is_premium_only'     => true,
					'has_paid_plans'      => true,
					'is_org_compliant'    => false,
					'parent'              => array(
						'id'         => '1284',
						'slug'       => 'wontrapi',
						'public_key' => 'pk_f3f99e224cd062ba9d7fda46ab973',
						'name'       => 'Wontrapi',
					),
					'menu'                => array(
					//	'slug'           => 'wontrapi-vc-rows',
						'first-path'     => 'plugins.php',
						'support'        => false,
					),
				) );
			}
		}

		do_action( 'wontrapi_vc_rows_fs_loaded' );
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  0.1.0
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Activate the plugin.
	 *
	 * @since  0.1.0
	 */
	public function _activate() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 *
	 * @since  0.1.0
	 */
	public function _deactivate() {
		// Add deactivation cleanup functionality here.
	}

}

/**
 * Grab the Wontrapi_VC_Rows object and return it.
 * Wrapper for Wontrapi_VC_Rows::get_instance().
 *
 * @since  0.1.0
 * @return Wontrapi_VC_Rows  Singleton instance of plugin class.
 */
function wontrapi_vc_rows() {
	return Wontrapi_VC_Rows::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( wontrapi_vc_rows(), 'hooks' ) );

// Activation and deactivation.
register_activation_hook( __FILE__, array( wontrapi_vc_rows(), '_activate' ) );
register_deactivation_hook( __FILE__, array( wontrapi_vc_rows(), '_deactivate' ) );


/*
function bt_vc_before_init() {

	// Set the parameters for options in the Element(s) settings
	$attributes = array(
		array(
			'type' => 'dropdown',
			'heading' => __( 'Show when', 'bt'),
			'param_name' => 'bt_op_if_tags',
			'value' => array(
				__( 'Always Show', 'btc' ) => 0,
				__( 'Show only if user has ALL chosen tags', 'bt' ) => 'all',
				__( 'Show if user has ANY chosen tags', 'bt' ) => 'any',
				__( 'Hide if user has ANY chosen tags', 'bt' ) => 'notany',
			),
			'group' => esc_html__( 'Ontraport', 'bt' ),
			'description' => __( 'Show or hide this element based on a Contact\'s tags', 'bt' ),
		),
		array(
			'type' => 'textfield',
			'heading' => __( 'Tags to determine element visibility', 'bt' ),
			'param_name' => 'bt_op_if_tags_data',
			'group' => esc_html__( 'Ontraport', 'bt' ),
			'description' => __( 'Use tag ID(s). Separate multiple tags with a comma and a space. ex: 123, 456', 'bt' ),
		),
	);

	// Elements this should be applied to
	$elements = array( 'vc_row', 'vc_row_inner' );

	// Add parameters to the VC element types we want to be able to use this with
	foreach ( $elements as $element ) {
		vc_add_params( $element, $attributes );
	}

}
add_action( 'vc_before_init', 'bt_vc_before_init', 40 );


function bt_vc_op_output_filter( $output, $class, $atts ) {

	if ( ! empty( $atts['bt_op_if_tags'] ) ) { 

		if ( ! class_exists( 'Wontrapi' ) ) 
			return $output;

		if ( empty( $atts['bt_op_if_tags'] ) )
			return $output;

		$type = $atts['bt_op_if_tags'];

		$tags = explode( ', ', $atts['bt_op_if_tags_data'] );
	
		if ( empty( $tags ) ) 
			return $output;

		$contact = Wontrapi_Cache::get_current(); 

		if ( 'all' === $type ) {

			if ( ! $contact ) 
				return '';

			foreach ( $tags as $tag ) {
				if ( ! WontrapiHelp::contact_has_tag( $contact, $tag ) ) {
					return '';
				}
			}
			return $output;

		} elseif ( 'any' === $type ) {

			if ( ! $contact ) 
				return '';

			foreach ( $tags as $tag ) {
				if ( WontrapiHelp::contact_has_tag( $contact, $tag ) ) {
					return $output;
				}
			}
			return '';

		} elseif ( 'notany' === $type ) {

			if ( ! $contact ) 
				return $output;

			foreach ( $tags as $tag ) {
				if ( ! WontrapiHelp::contact_has_tag( $contact, $tag ) ) {
					return '';
				}
			}
			return $output;

		}
	}
	return $output;
}
// Add filter to output with a high number so it runs last
add_filter( 'vc_shortcode_output', 'bt_vc_op_output_filter', 9999, 3 );
*/

