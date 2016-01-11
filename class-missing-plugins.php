<?php

if ( ! class_exists( 'Missing_Plugins' ) ) :
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
		 * The name for the nonce input.
		 *
		 * @var boolean
		 * @since      1.0
		 */
		private $form_nonce_name = false;

		/**
		 * A list of plugins to activate.
		 *
		 * @var array
		 * @since  1.0
		 */
		private $plugins_to_activate = array();

		/**
		 * Construct.
		 *
		 * @param array $args Arguments
		 * @since 1.0
		 */
		public function __construct( $args = array() ) {
			add_action( 'admin_init', array( $this, 'bootup' ) );
		}

		/**
		 * The very first thing that happens.
		 *
		 * @return void
		 */
		public function bootup() {
			/**
			 * Set define( 'SKIP_MISSING_PLUGINS', true ) to skip loading this plugin.
			 */
			if ( defined( 'SKIP_MISSING_PLUGINS' ) && true === SKIP_MISSING_PLUGINS ) {
				return; // Don't load this plugin, skip loading it.
			}

			$this->set_nonce_vars(); // Set the nonce name and action.
			$this->set_active_plugins(); // Store the active plugins into an array from DB.
			$this->set_missing_plugins(); // Go through the active plugins and find out which one's don't have local files.
			$this->filter_out_non_wp_org_plugins(); // We can only install/activate wp.org plugins, so filter out non-wp.org plugins.

			// If we're not in the process of installing plugins and we actually have missing plugins.
			if ( ! $this->is_installing() && $this->is_missing_plugins() ) {
				$this->the_wp_die_template(); // Show the form!
			} else if ( $this->is_secure_submit() && $this->is_installing() ) {
				$this->set_plugins_to_activate(); // Set the plugins the user wanted to have activated.
				$this->install_and_activate_missing_plugins(); // Install and activate the plugins requested by the user.
			} else if ( $this->is_missing_plugins() ) {
				wp_die( __( 'Something went wrong, please go back and try again.', 'missing-plugins' ) );
			}
		}

		private function install_and_activate_missing_plugins() {
			require_once( ABSPATH . 'wp-admin/update.php' );
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			require_once( ABSPATH . 'wp-admin/includes/admin.php' );

			// Sum down the plugin to their slug.
			foreach ( $this->plugins_to_activate as $plugin_file ) {
				$plugin_slug = basename( dirname( $plugin_file ) ); // Get the plugin slug for wp.org.

				// Tap the API.
				$api = plugins_api( 'plugin_information', array(
					'slug' => $plugin_slug,
					'fields' => array(
						'sections' => false
					)
				) );

				require_once( ABSPATH . 'wp-admin/admin-header.php' );

				// Install the plugin.
				$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( array(
					'title'  => __( 'Installing Plugin...', 'missing-plugins' ),
					'plugin' => $plugin_slug,
					'api'    => $api,
				) ) );

				$upgrader->install( $api->download_link ); // Download and install the plugin.
				activate_plugin( $plugin_slug, false, false, true ); // Activate the plugin.

				require_once( ABSPATH . 'wp-admin/admin-footer.php' );
			}
		}

		private function set_nonce_vars() {
			$this->form_nonce_name = 'wp_missing_plugins_nonce';
			$this->wp_nonce_action = 'wp_missing_plugins_nonce_action';
		}

		/**
		 * Checks all the things when submitting the form that it's secure
		 *
		 * Checks the user and the form nonce.
		 *
		 * @return boolean False if not, true if all the things check out
		 * @since  1.0
		 */
		private function is_secure_submit() {
			if ( ! current_user_can( 'administrator' ) && ( ! isset( $_REQUEST['username'] ) || ! isset( $_REQUEST['password'] ) ) ) {
				return false; // We need at least your username and password if you're not logged in as an administrator.
			}

			// Check the user.
			if ( ! current_user_can( 'administrator' ) ) {
				$username = $_REQUEST['username'];
				$password = $_REQUEST['password'];
				$user = get_user_by( 'login', $username );

				if ( ! $user ) {
					return false; // If the user is not in the db.
				}

				$user_okay = wp_check_password( $password, $user->data->user_pass, $user->ID );
			} else {
				$user_okay = true; // The current user is an administrator.
			}

			if ( ! $user_okay ) {
				return false; // If password does not check out.
			}

			// Check the nonce.
			if ( ! isset( $_REQUEST[ $this->form_nonce_name ] ) ) {
				return false; // The nonce was not even set!
			}

			$nonce = wp_verify_nonce( $_REQUEST[ $this->form_nonce_name ], $this->wp_nonce_action );

			if ( $nonce ) {
				return true; // If the user is an administrator and the nonce checks out.
			}

			return false;
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
		 * @since  1.0
		 */
		private function cross_check_with_active_plugins( $plugins_to_activate ) {
			foreach( $plugins_to_activate as $plugin_to_activate ) {
				if ( ! in_array( $plugin_to_activate, $this->active_plugins ) ) {
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
		 */
		private function set_plugins_to_activate() {
			if ( isset( $_REQUEST['plugins_to_activate'] ) ) {
				$this->plugins_to_activate = $this->cross_check_with_active_plugins( $_REQUEST['plugins_to_activate'] ); // These are the plugins the user chose to install and activate.
			}
		}

		/**
		 * Are we in the process of installing missing plugins?
		 *
		 * @return boolean True for yes, false for no.
		 * @since  1.0
		 */
		private function is_installing() {
			$form_submit = isset( $_REQUEST[ $this->form_nonce_name ] );

			if ( $form_submit ) {
				return true;
			}

			return false;
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

		private function is_admin_user() {
			if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
				return true;
			}

			return false;
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
		 * @since  1.0
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

					<h2><?php echo $title; ?></h2>

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

					<?php if ( ! $this->is_admin_user() ) : ?>
						<p><?php _e( 'You must provide login details for an administrative user to continue:', 'missing-plugins' ); ?></p>

						<div class="login-form">
							<p><label for="username"><?php _e( 'Username:', 'missing-plugins' ); ?></label> <input type="text" name="username"></p>
							<p><label for="password"><?php _e( 'Password:', 'missing-plugins' ); ?></label> <input type="password" name="password"></p>
						</div>
					<?php endif; ?>

					<p><input type="submit" value="<?php _e( 'Continue', 'missing-plugins' ); ?>" /></p>

					<?php wp_nonce_field( $this->wp_nonce_action, $this->form_nonce_name ); ?>

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
	function missing_plugins_class_exists() {
		?>
			<div class="error">
				<p><?php __e( 'Sorry, but the <strong>Missing Plugins</strong> plugin seems to be conflicting with another plugin. Please contact <a href="https://twitter.com/aubreypwd">Aubrey Portwood</a> about this.', 'missing-plugins' ); ?></p>
			</div>
		<?php
	}
	add_action( 'admin_notices', 'missing_plugins_class_exists' );
endif;
