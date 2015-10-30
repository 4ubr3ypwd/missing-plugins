<?php

if ( ! class_exists( 'Missing_Plugins ' ) ) :
	class Missing_Plugins {

		/**
		 * The active plugins when this plugin loads.
		 *
		 * @var array
		 * @since 1.0
		 */
		private $active_plugins;

		/**
		 * The WP_Error's
		 *
		 * @var object WP_Error
		 * @since      1.0
		 */
		private $error_handler;

		/**
		 *  __  __ _       _             ___ _           _
		 * |  \/  (_)_____(_)_ _  __ _  | _ \ |_  _ __ _(_)_ _  ___
		 * | |\/| | (_-<_-< | ' \/ _` | |  _/ | || / _` | | ' \(_-<
		 * |_|  |_|_/__/__/_|_||_\__, | |_| |_|\_,_\__, |_|_||_/__/
		 *                       |___/             |___/
		 *
		 * @param array $args Arguments
		 * @since 1.0
		 */
		public function __construct( $args = array() ) {
			$this->set_active_plugins();
			$this->check_for_missing_plugins();
		}

		/**
		 * Check for missing plugins, and if there are, add an error.
		 *
		 * @return void
		 * @since  1.0
		 */
		private function check_for_missing_plugins() {
			foreach ( $this->active_plugins as $plugin_file ) {
				if ( ! file_exists( $plugin_file ) && ! isset( $_GET['missing-plugins-disabled'] ) ) {
					wp_die( "$plugin_file is active, but is not here. Activate? <a href='update.php?action=install-plugin&plugin=theme-check&_wpnonce=f4d0f3c340&missing-plugins-disabled=true'>Yes</a>" );
				}
			}
		}

		/**
		 * Set the active plugins when the plugin loads.
		 *
		 * @since 1.0
		 */
		private function set_active_plugins() {
			$active_plugins = get_option( 'active_plugins' );
			foreach ( $active_plugins as $key => $plugin_file ) {
				$this->active_plugins[ $plugin_file ] = trailingslashit( WP_PLUGIN_DIR ) . $plugin_file;
			}
		}
	}
else:

	/**
	 * Shows an error in the Admin when there is some kind of conflict.
	 *
	 * @return void
	 * @since  1.0
	 */
	function __missing_plugins_class_exists() {
		?>
			<div class="error">
				<p><?php __e( 'Sorry, but the <strong>Missing Plugins</strong> plugin seems to be conflicting with another plugin. Please contact <a href="https://twitter.com/aubreypwd">Aubrey Portwood</a> about this.', 'missing-plugins' ); ?></p>
			</div>
		<?php
	}
	add_action( 'admin_notices', '__missing_plugins_class_exists' );
endif;
