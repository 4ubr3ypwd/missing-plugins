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
		 * @since 1.0
		 */
		private $errors;

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
			$this->error_init();
			$this->set_active_plugins();
			$this->freeze();
		}

		/**
		 * Sets up an empty WP_Error object.
		 *
		 * @return object WP_Error Object
		 * @see    https://codex.wordpress.org/Class_Reference/WP_Error WP_Error
		 * @since  1.0
		 */
		private function error_init() {
			$this->errors = new WP_Error( 'init', __( 'This is just the init error, not used, ever', 'missing-plugins' ) );
			$this->errors->remove( 'init' );
		}

		private function freeze() {

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
