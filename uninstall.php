<?php
/**
 * Removes everything the plugin stores when it is deleted from the Plugins
 * screen: the settings option and the cached GitHub release. On multisite,
 * cleans every site in the network.
 *
 * @package UC_Calc
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes the plugin's data for the current site.
 */
function uc_calc_uninstall_site() {
	delete_option( 'uc_calc_settings' );
	delete_transient( 'uc_calc_github_release' );
}

if ( is_multisite() ) {
	$uc_calc_site_ids = get_sites(
		[
			'fields' => 'ids',
			'number' => 0,
		]
	);
	foreach ( $uc_calc_site_ids as $uc_calc_site_id ) {
		switch_to_blog( $uc_calc_site_id );
		uc_calc_uninstall_site();
		restore_current_blog();
	}
} else {
	uc_calc_uninstall_site();
}
