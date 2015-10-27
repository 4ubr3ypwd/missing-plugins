<?php

if ( ! class_exists( 'Missing_Plugins ' ) ) :
	class Missing_Plugins {
		public function __construct( $args = array() ) {

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
