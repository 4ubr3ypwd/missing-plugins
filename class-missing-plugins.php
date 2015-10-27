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
			$this->setup_error_handler();
			$this->set_active_plugins();
			$this->check_for_missing_plugins();
			$this->handle_errors();
		}

		/**
		 * When errors are present, wp_die and show output.
		 *
		 * @return void
		 * @since  1.0
		 */
		private function handle_errors() {
			if ( sizeof( $this->error_handler->errors ) >= 1 ) {
				// wp_die( print_r( $this->error_handler ) );

				foreach( $this->error_handler->errors as $code => $error ) {

					// Add any filters for content (if using any)
					if ( isset( $this->error_handler->error_data[ $code ]['filter'] ) ) {
						add_filter( "missing_plugins_wp_die_{$code}", $this->error_handler->error_data[ $code ]['filter'] );
					}

					$filter = apply_filters( "missing_plugins_wp_die_{$code}", array(
						'code' => $code,
						'error' => $error
					) );

					// Die
					wp_die(

						// Use filtered content or one supplied in WP_Error.
						! is_array( $filter ) && false != $filter ? $filter : ( end( $error ) ? end( $error ) : __( 'Unknown Error', 'missing-plugins' ) ),

						// Title
						isset( $this->error_handler->error_data[ $code ]['title'] ) ? $this->error_handler->error_data[ $code ]['title'] : __( 'Unknown Error', 'missing-plugins' ),

						// Any args.
						isset( $this->error_handler->error_data[ $code ]['args'] ) ? $this->error_handler->error_data[ $code ]['args'] : array()
					);
				}
			}
		}

		/**
		 * Sets up an empty WP_Error object.
		 *
		 * @return object WP_Error Object
		 * @see    https://codex.wordpress.org/Class_Reference/WP_Error WP_Error
		 * @since  1.0
		 */
		private function setup_error_handler() {
			$this->error_handler = new WP_Error( 'init', __( 'This is just the init error, not used, ever', 'missing-plugins' ) );
			$this->error_handler->remove( 'init' );
		}

		/**
		 * Check for missing plugins, and if there are, add an error.
		 *
		 * @return void
		 * @since  1.0
		 */
		private function check_for_missing_plugins() {
			$this->error_handler->add( 'active_plugins_missing', false, array(
				'title'   => __( 'Missing Active Plugins' ),
				'args'    => array(),
				'filter'  => array( $this, 'content' ),
			) );
		}

		/**
		 * Content for WP_Error's
		 *
		 * Returns the content for the error message supplied in `$error`.
		 *
		 * @param  array $error  Array with `code` key and `error` value.
		 * @return string        The content for that error message.
		 * @since  1.0
		 */
		public function content( $error ) {
			$content = array(
				'active_plugins_missing' => __( 'You have active plugins missing locally, would you like to install them?', 'missing_plugins' ),
			);

			return isset( $content[ $error['code'] ] ) ? $content[ $error['code'] ] : false;
		}

		/**
		 * Set the active plugins when the plugin loads.
		 *
		 * @since 1.0
		 */
		private function set_active_plugins() {
			$this->active_plugins = get_option( 'active_plugins' );
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
				<p><?php _e( 'Sorry, but the <strong>Missing Plugins</strong> plugin seems to be conflicting with another plugin. Please contact <a href="https://twitter.com/aubreypwd">Aubrey Portwood</a> about this.', 'missing-plugins' ); ?></p>
			</div>
		<?php
	}
	add_action( 'admin_notices', '__missing_plugins_class_exists' );
endif;
