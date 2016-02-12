<?php

if ( ! class_exists( 'Missing_Plugins' ) ) {
	/**
	 * Missing Plugins functionality.
	 *
	 * @since  1.0.0
	 * @author Aubrey Portwood <aubreypwd@gmail.com>
	 */
	class Missing_Plugins {
		/**
		 * The active plugins when this plugin loads.
		 *
		 * @var array
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $active_plugins_at_runtime = array();

		/**
		 * The plugins that are activated, but don't have files.
		 *
		 * @var array
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $missing_plugins = array();

		/**
		 * The WP_Error's
		 *
		 * @var object WP_Error
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $error_handler;

		/**
		 * The URL to the WordPress Plugins Repository.
		 *
		 * @var string
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $wp_org_plugins_url = 'http://plugins.svn.wordpress.org';

		/**
		 * Plugins that are missing, that aren't in the plugin repo.
		 *
		 * TODO: We will, at a later time, find a way to install these some other way.
		 *
		 * @var array
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $wp_non_org_plugins = array();

		/**
		 * The nonce name for this instance.
		 *
		 * @var boolean
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $wp_nonce_action = 'wp_missing_plugins_nonce_action';

		/**
		 * The name for the nonce input.
		 *
		 * @var boolean
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $form_nonce_name = 'wp_missing_plugins_nonce';

		/**
		 * The list of plugins to eventually install (checked for injected plugins).
		 *
		 * @var array
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $safe_plugins_to_install = array();

		/**
		 * The title to show for wp_die.
		 *
		 * @var string
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private $title = '';

		/**
		 * New Instance.
		 *
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		public function __construct() {
			/**
			 * Set define( 'SKIP_MISSING_PLUGINS', true ) to skip loading this plugin.
			 */
			if ( defined( 'SKIP_MISSING_PLUGINS' ) && true === SKIP_MISSING_PLUGINS ) {
				return; // Don't load this plugin, skip loading it.
			}

			$this->title = __( 'You have missing plugin files.', 'missing-plugins' ); // Set the title for frontend and backend notices.
			add_action( 'admin_init', array( $this, 'backend' ) ); // Load this when the backend (wp-admin) is loaded and plugins are missing.
			add_action( 'template_redirect', array( $this, 'frontend' ) ); // Re-direct the frontend to the backend (wp-admin) when plugins are missing.
		}

		/**
		 * Discover what plugins we need to activate.
		 *
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function discover_missing_plugins() {
			$this->set_active_plugins_at_runtime(); // Store the active plugins into an array from DB.
			$this->set_missing_plugins_at_runtime(); // Go through the active plugins and find out which one's don't have local files for.
		}

		/**
		 * Get the plugins we should ignore.
		 *
		 * @return array The plugins to ignore.
		 */
		private function forgotten_plugins() {
			$forgotten_plugins = get_option( 'missing_plugins_skip', true );
			return is_array( $forgotten_plugins ) ? $forgotten_plugins : array();
		}

		/**
		 * Save the plugins that were skipped in the plugin chooser.
		 */
		private function forget_plugins_on_submit() {
			if ( sizeof( $this->form_submitted_plugins() ) === 0 ) {
				return;
			}

			$plugins_to_forget = array_diff( $this->missing_plugins, $this->form_submitted_plugins() );
			update_option( 'missing_plugins_skip', array_merge( $this->forgotten_plugins(), $plugins_to_forget ) );
		}

		/**
		 * Load the plugin.
		 *
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		public function backend() {
			if ( $this->is_login_page() ) {
				return false; // Don't load this stuff on the login page.
			}

			$this->discover_missing_plugins(); // What plugins should we activate?

			// Plugin chooser.
			if ( ! $this->is_installing() && $this->is_missing_plugins() ) {
				$this->the_plugin_chooser(); // Show the form!

			// Install Plugins.
			} else if ( $this->is_installing() && $this->is_secure() ) {
				$this->forget_plugins_on_submit(); // The plugins the user chose not to activate, we need to save them so we don't try and install them again.
				$this->set_safe_missing_plugins(); // Crosscheck plugins for any security issues.
				$this->filter_out_non_wp_org_plugins(); // We can only install/activate wp.org plugins, so filter out non-wp.org plugins (soon to enable zip upload for these).
				$this->the_plugin_installer(); // Install and activate the plugins requested by the user.

			// Everything else.
			} else if ( $this->is_missing_plugins() ) {
				wp_die( __( 'Something went wrong, please go back and try again.', 'missing-plugins' ) );
			}
		}

		/**
		 * Redirect to wp-admin if there are plugins missing.
		 *
		 * Also forces login of admin user.
		 *
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		public function frontend() {
			if ( $this->is_login_page() ) {
				return false; // Don't load this stuff on the login page.
			}

			$this->discover_missing_plugins(); // Discover missing plugins.

			if ( is_admin() || $this->is_login_page() ) {
				return; // No need to re-direct, probably login page or the chooser.
			} elseif ( current_user_can( 'administrator' ) ) {
				wp_redirect( admin_url() ); // Goto wp-admin if the user is an administrator (just go directly to the chooser).
			} elseif ( $this->is_missing_plugins() ) {
				$this->frontend_notice(); // If not an administrator, show the frontend notice, will ask for login when going to wp-admin.
			}
		}

		/**
		 * The notice we show on the frontend.
		 *
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function frontend_notice() {
			ob_start(); ?>
			<h2><?php echo $this->title; ?></h2>
				<p><?php echo sprintf( __( 'You have active plugins in the database that have files missing, please <a href="%s">login</a> as an administrator so we can show you what plugins those are and give you an opportunity to install them.', 'missing-plugins' ), admin_url() ); ?></p>
			<?php $output = ob_get_clean();

			wp_die( $output, $this->title, array() ); // Die.
		}

		/**
		 * Sanitizes and checks form submit before returning form results.
		 *
		 * @return array The plugins to activate submitted by the plugin chooser.
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function form_submitted_plugins() {
			if ( ! isset( $_REQUEST['plugins_to_activate'] ) ) {
				return array();
			}

			$form_results = $_REQUEST['plugins_to_activate'];

			if ( ! is_array( $form_results ) ) {
				wp_die( __( 'Invalid data from form, please go back and try again.', 'missing-plugins' ) );
			}

			return $form_results;
		}

		/**
		 * Determine if we are on the login pages.
		 *
		 * Used mainly to get the user to the wp-admin so we can install plugins.
		 * If the user is on the login page, let's give them an opportunity to login.
		 *
		 * @return boolean True if we are, false if not.
		 */
		private function is_login_page() {
			return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', ) );
		}

		/**
		 * Hide the activate plugin links when installing the plugins.
		 *
		 * Added via admin_head filter in `self::the_plugin_installer()`.
		 *
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		public function the_plugin_installing_styles() {
			?>
				<style>
					a[href*="action=activate"] {
						/*We're hiding the activate plugin link, because missing-plugins will automatically activate it for you.*/
						margin-left: -99999em;
					}
				</style>
			<?php
		}

		/**
		 * Load the plugin installer and install the plugins.
		 *
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function the_plugin_installer() {
			if ( ! current_user_can( 'administrator' ) ) {
				return; // Never install plugins unless the user is an administrator.
			}

			// We need these files.
			require_once( ABSPATH . 'wp-admin/update.php' );
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			require_once( ABSPATH . 'wp-admin/includes/admin.php' );

			// The theme header.
			add_action( 'admin_head', array( $this, 'the_plugin_installing_styles' ) );
			require_once( ABSPATH . 'wp-admin/admin-header.php' );

			// Sum down the plugin to their slug.
			foreach ( $this->safe_plugins_to_install as $plugin_file ) {
				$plugin_slug = basename( dirname( $plugin_file ) ); // Get the plugin slug for wp.org.

				// Tap the API.
				$api = plugins_api( 'plugin_information', array(
					'slug' => $plugin_slug,
					'fields' => array(
						'sections' => false
					)
				) );

				// Install the plugin.
				$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( array(
					'title'  => __( 'Installing Plugin...', 'missing-plugins' ),
					'plugin' => $plugin_slug,
					'api'    => $api,
				) ) );

				$upgrader->install( $api->download_link ); // Download and install the plugin.
				activate_plugin( $plugin_slug, false, false, true ); // Activate the plugin.
			}

			// The theme footer.
			require_once( ABSPATH . 'wp-admin/admin-footer.php' );
		}

		/**
		 * Checks all the things when submitting the form that it's secure.
		 *
		 * Checks the user and the form nonce.
		 *
		 * @return boolean False if not, true if all the things check out.
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function is_secure() {
			// Check the user.
			if ( ! current_user_can( 'administrator' ) ) {
				return false; // The user has to be an administrator.
			}

			// Check the nonce was sent.
			if ( ! isset( $_REQUEST[ $this->form_nonce_name ] ) ) {
				return false; // The nonce was not even set!
			}

			// Verify nonce.
			$nonce = wp_verify_nonce( $_REQUEST[ $this->form_nonce_name ], $this->wp_nonce_action );

			// Nonce is good.
			if ( $nonce ) {
				return true; // The nonce was good.
			}

			return false; // The nonce was bad.
		}

		/**
		 * Cross checks the submitted plugins to activate with the plugins active, initially, in the database.
		 *
		 * If a plugin submitted via the form is not in the database, it's possible
		 * a plugin was added to the form in hopes of activating it from the outside.
		 *
		 * This checks with the database active plugins to ensure everything submitted
		 * via the form are plugins originally in the database, and dies if one is
		 * not found.
		 *
		 * @param  array $plugins_to_activate Plugins submitted by the user to activate.
		 * @return boolean                    False if an injected plugin is found, true if all the requested plugins are in the DB currently.
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function cross_check_with_active_plugins( $plugins_to_activate ) {
			foreach( $plugins_to_activate as $plugin_to_activate ) {
				if ( ! in_array( $plugin_to_activate, $this->active_plugins_at_runtime ) ) {
					// Die if a plugin is not in the database! Possible hijack!
					wp_die( sprintf( __( "Sorry, but %s is not currently in the database and cannot be installed. It's possible that a hijacking script added this plugin to your form when you submitted it, in an attempt to add an outside plugin.", 'missing-plugins' ), "<code>$plugin_to_activate</code>" ) );
				}
			}

			return $plugins_to_activate;
		}

		/**
		 * Add the plugins the user chose to activate from the form.
		 *
		 * Sets to default array() if nothing submitted.
		 *
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function set_safe_missing_plugins() {
			if ( $this->form_submitted_plugins() ) {
				$this->safe_plugins_to_install = $this->cross_check_with_active_plugins( $this->form_submitted_plugins() ); // These are the plugins the user chose to install and activate.
			}
		}

		/**
		 * Are we in the process of installing missing plugins?
		 *
		 * @return boolean True for yes, false for no.
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function is_installing() {
			$form_submit = isset( $_REQUEST[ $this->form_nonce_name ] );

			if ( $form_submit ) {
				return true;
			}

			return false;
		}

		/**
		 * Find out if we even have missing plugins to activate.
		 *
		 * @return boolean True if there are missing plugin, false if none were found.
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function is_missing_plugins() {
			if ( sizeof( $this->missing_plugins ) > 0 ) {
				return true;
			}

			return false;
		}

		/**
		 * Is the plugin file a plugin the user has chosen to skip?
		 *
		 * @param  string  $plugin_file The plugin file.
		 * @return boolean              True if it was skipped previously, false if not.
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function is_forgotten_plugin( $plugin_file ) {
			if ( in_array( $plugin_file, $this->forgotten_plugins() ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Check for missing plugins.
		 *
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function set_missing_plugins_at_runtime() {
			foreach ( $this->active_plugins_at_runtime as $plugin_file ) {
				if ( ! file_exists( $plugin_file ) && ! $this->is_forgotten_plugin( $plugin_file ) ) {
					$this->missing_plugins[] = $plugin_file;
				}
			}
		}

		/**
		 * Filter our plugins that aren't on the WordPress.org repo.
		 *
		 * @since   1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
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
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function set_active_plugins_at_runtime() {
			$active_plugins = get_option( 'active_plugins' ); // The active plugins in the DB

			// Add each one with their direct path included.
			foreach ( $active_plugins as $key => $plugin_file ) {
				$this->active_plugins_at_runtime[ $plugin_file ] = trailingslashit( WP_PLUGIN_DIR ) . $plugin_file;
			}
		}

		/**
		 * Checks if MISSING_PLUGINS_HIJACK is set in wp-config.php
		 *
		 * This is an easy way to add a "hijecked" plugin to the list of
		 * plugins submitted by the user.
		 *
		 * This simulates adding a plugin that isn't even in the database.
		 *
		 * @return boolean True if it is and set to true itself, false if not.
		 * @since  1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function is_hijack_mode() {
			// Add define( 'MISSING_PLUGINS_HIJACK', true ) to your wp-config to set this mode.
			if ( defined( 'MISSING_PLUGINS_HIJACK' ) && true === MISSING_PLUGINS_HIJACK ) {
				return true;
			}
		}

		/**
		 * Ask the users which plugins (missing files) they want to install.
		 *
		 * @since 1.0.0
		 * @author Aubrey Portwood <aubreypwd@gmail.com>
		 */
		private function the_plugin_chooser() {
			if ( ! current_user_can( 'administrator' ) ) {
				return; // Don't show the chooser unless the user is an administrator.
			}

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

					.login-form {
						padding: 20px;
						border: 1px solid #eee;
					}

					.login-form label,
					.login-form input {
						display: inline-block;
					}

					.login-form label {
						width: 25%;
					}

					.login-form input {
						width: 60%;
					}
				</style>

				<form action="" method="post">

					<h2><?php echo $this->title; ?></h2>

					<p><?php echo sprintf( __( 'The following plugins were active in the database, but their files are missing in your <code>%s</code> folder. If you would like us to install and activate them, please select the ones you need and continue.', 'missing-plugins' ), wp_unslash( basename( WP_PLUGIN_DIR ) ) ); ?></p>

					<table class="list">
						<?php foreach ( $this->missing_plugins as $plugin_file ) : ?>
							<tr class="list-item">
								<td><input type="checkbox" name="plugins_to_activate[]" value="<?php echo esc_attr( $plugin_file ); ?>" /></td>
								<td><?php echo esc_html( $plugin_file ); ?></td>
							</tr>
						<?php endforeach; ?>

						<?php if ( $this->is_hijack_mode() ) : ?>
							<tr class="list-item">
								<td><input type="checkbox" name="plugins_to_activate[]" checked value="hijacked-plugin.php" /></td>
								<td>hijacked-plugin.php</td>
							</tr>
						<?php endif; ?>
					</table>

					<p><input type="submit" value="<?php _e( 'Continue', 'missing-plugins' ); ?>" /></p>

					<?php wp_nonce_field( $this->wp_nonce_action, $this->form_nonce_name ); ?>

				</form>

			<?php $output = ob_get_clean();

			// Die
			wp_die( $output, $this->title, array() );
		}
	} // class Missing_Plugins

	/*
	 * Launch Missing Plugins.
	 */
	if ( ! isset( $missing_plugins ) ) {
		$missing_plugins = new Missing_Plugins();
	}

// class Missing_Plugins exists already.
} else {
	/**
	 * Shows an error in the Admin when there is some kind of conflict.
	 *
	 * @since  1.0.0
	 * @author Aubrey Portwood <aubreypwd@gmail.com>
	 */
	function missing_plugins_class_exists() {
		?>
			<div class="error">
				<p><?php __e( 'Sorry, but the <strong>Missing Plugins</strong> plugin seems to be conflicting with another plugin. Please contact <a href="https://twitter.com/aubreypwd">Aubrey Portwood</a> about this.', 'missing-plugins' ); ?></p>
			</div>
		<?php
	}
	add_action( 'admin_notices', 'missing_plugins_class_exists' );
}
