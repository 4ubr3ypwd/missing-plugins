<?php

if ( ! class_exists( 'Missing_Plugins ' ) ) :
	class Missing_Plugins {

		/**
		 * The active plugins when this plugin loads.
		 *
		 * @var array
		 * @since 1.0
		 */
		private $active_plugins = array();

		/**
		 * The plugins that are activated, but don't have files.
		 *
		 * @var array
		 * @since 1.0
		 */
		private $missing_plugins = array();

		/**
		 * The WP_Error's
		 *
		 * @var object WP_Error
		 * @since      1.0
		 */
		private $error_handler;

		/**
		 * Construct.
		 *
		 * @param array $args Arguments
		 * @since 1.0
		 */
		public function __construct( $args = array() ) {
			$this->set_active_plugins();
			$this->set_missing_plugins();
			$this->the_wp_die_template();
		}

		/**
		 * Returns true if there are missing plugins.
		 *
		 * @return boolean True if there are missing plugin, false if none were found.
		 * @since  1.0
		 */
		private function is_missing_plugins() {
			if ( sizeof( $this->missing_plugins ) > 0 ) {
				return true;
			}

			return false;
		}

		/**
		 * Check for missing plugins.
		 *
		 * @return void
		 * @since  1.0
		 */
		private function set_missing_plugins() {
			foreach ( $this->active_plugins as $plugin_file ) {
				if ( ! file_exists( $plugin_file ) ) {
					$this->missing_plugins[] = $plugin_file;
				}
			}
		}

		/**
		 * Set the active plugins when the plugin loads.
		 *
		 * @since 1.0
		 */
		private function set_active_plugins() {
			$active_plugins = get_option( 'active_plugins' ); // The active plugins in the DB

			// Add each one with their direct path included.
			foreach ( $active_plugins as $key => $plugin_file ) {
				$this->active_plugins[ $plugin_file ] = trailingslashit( WP_PLUGIN_DIR ) . $plugin_file;
			}
		}

		/**
		 * The output when there are missing plugin files.
		 *
		 * @return void
		 * @since 1.0
		 */
		private function the_wp_die_template() {
			if ( ! $this->is_missing_plugins() ) {
				return false; // No output needed.
			}

			ob_start(); ?>

				<h2><?php _e( 'You have missing plugin files.', 'missing-plugins' ); ?></h2>

				<p><?php echo sprintf( __( 'The following plugins were active in the database, but their files are missing in your <code>%s</code> folder. If you would like us to install and activate them, please select the ones you need and continue.', 'missing-plugins' ), wp_unslash( basename( WP_PLUGIN_DIR ) ) ); ?></p>

			<?php $output = ob_get_clean();

			// Die
			wp_die( $output, __( 'Missing Plugins', 'missing-plugins' ), array() );
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
