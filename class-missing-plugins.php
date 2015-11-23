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
		 * The URL to the WordPress Plugins Repository
		 *
		 * @var string
		 * @since      1.0
		 */
		private $wp_org_plugins_url = 'http://plugins.svn.wordpress.org';

		/**
		 * Plugins that are missing, that aren't in the plugin repo.
		 *
		 * @var array
		 * @since      1.0
		 */
		private $wp_non_org_plugins = array();

		/**
		 * The nonce for this instance.
		 *
		 * @var boolean
		 * @since      1.0
		 */
		private $wp_nonce_action = false;

		/**
		 * Construct.
		 *
		 * @param array $args Arguments
		 * @since 1.0
		 */
		public function __construct( $args = array() ) {
			add_action( 'init', array( $this, 'bootup' ) );
		}

		/**
		 * The very first thing that happens.
		 *
		 * @return void
		 */
		public function bootup() {
			$this->wp_nonce_action = substr( str_shuffle( wp_salt( 'nonce' ) ), 0, 10 ); // Generate a random nonce action name to be used by the form.

			$this->set_active_plugins(); // Store the active plugins into an array from DB.
			$this->set_missing_plugins(); // Go through the active plugins and find out which one's don't have local files.
			$this->filter_out_non_wp_org_plugins(); // We can only install/activate wp.org plugins, so filter out non-wp.org plugins.

			// If we're not in the process of installing plugins and we actually have missing plugins.
			if ( ! $this->is_installing() && $this->is_missing_plugins() ) {
				$this->the_wp_die_template();
			} else if ( $this->is_secure_submit() ) {
				$this->set_plugins_to_activate();
			}
		}

		private function is_secure_submit() {
			//
		}

		private function set_plugins_to_activate() {
			// Todo set the plugins to activate from form submission.
		}

		/**
		 * Are we in the process of installing missing plugins?
		 *
		 * @return boolean True for yes, false for no.
		 * @since  1.0
		 */
		private function is_installing() {

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
		 * Filter our plugins that aren't on the WordPress.org repo.
		 *
		 * @return  void
		 * @since   1.0
		 */
		private function filter_out_non_wp_org_plugins() {
			foreach ( $this->missing_plugins as $key => $plugin_path ) {
				$plugin_name = basename( dirname( $plugin_path ) );
				$plugin_file = basename( $plugin_path ); // Get just the filename, which should be in the trunk of the plugin.
				$url = trailingslashit( $this->wp_org_plugins_url ) . trailingslashit( $plugin_name ) . "trunk/$plugin_file";

				$response = wp_remote_get( $url ); // See if the file exists in trunk.

				// 404 responses mean the plugin isn't able to install/activate because we don't know where the plugin is.
				if ( $response && '404' == wp_remote_retrieve_response_code( $response ) ) {
					unset ( $this->missing_plugins[ $key ] ); // Remove from the list of missing plugins that we can install/activate.
					$this->wp_non_org_plugins[] = $plugin_path; // Note that this is a non-wordpress plugin (will be used later).
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
		 * Ask the users which plugins (missing files) they want to install.
		 *
		 * @return void
		 * @since 1.0
		 */
		private function the_wp_die_template() {

			$title = __( 'You have missing plugin files.', 'missing-plugins' );

			ob_start(); ?>

				<style>
					.list {
						border: 1px solid #eee;
						border-spacing: 0;
						margin: 20px 0;
					}

					.list-item td {
						border-bottom: 1px solid #eee;
						border-spacing: 0;
						border-right: 1px solid #eee;
						padding: 10px;
					}

					.list-item td:last-child {
						border-right: 0;
					}

					.list-item:last-child td {
						border-bottom: 0;
					}
				</style>

				<form action="" method="post">

					<h2><?php echo $title; ?></h2>

					<p><?php echo sprintf( __( 'The following plugins were active in the database, but their files are missing in your <code>%s</code> folder. If you would like us to install and activate them, please select the ones you need and continue.', 'missing-plugins' ), wp_unslash( basename( WP_PLUGIN_DIR ) ) ); ?></p>

					<table class="list">
						<?php foreach ( $this->missing_plugins as $plugin_file ) : ?>
							<tr class="list-item">
								<td><input type="checkbox" name="plugins_to_activate[]" value="<?php echo esc_attr( $plugin_file ); ?>" /></td>
								<td><?php echo esc_html( $plugin_file ); ?></td>
							</tr>
						<?php endforeach; ?>
					</table>

					<p><input type="submit" value="<?php _e( 'Continue', 'missing-plugins' ); ?>" /></p>

					<?php wp_nonce_field( $this->wp_nonce_action ); ?>

				</form>

			<?php $output = ob_get_clean();

			// Die
			wp_die( $output, $title, array() );
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
