<?php
/**
 * Plugin Name:       UC Calculator
 * Plugin URI:        https://github.com/aidanashby/uc-calc
 * Description:       Universal Credit vs essentials calculator for campaign use. Client-side only, no data collected.
 * Version:           1.3.0
 * Author:            Aidan Ashby
 * Author URI:        https://lucidrhino.design
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       uc-calc
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Update URI:        https://github.com/aidanashby/uc-calc
 *
 * @package UC_Calc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UC_CALC_VERSION', '1.3.0' );
define( 'UC_CALC_FILE', __FILE__ );
define( 'UC_CALC_DIR', plugin_dir_path( __FILE__ ) );
define( 'UC_CALC_URL', plugin_dir_url( __FILE__ ) );

// GitHub repo for self-updates. Set to '' to disable.
if ( ! defined( 'UC_CALC_GITHUB_REPO' ) ) {
	define( 'UC_CALC_GITHUB_REPO', 'aidanashby/uc-calc' );
}

// ─── Defaults ─────────────────────────────────────────────────────────────────

/**
 * Returns the canonical default rates and costs.
 *
 * Stored values are merged on top of these via array_replace_recursive() so a
 * new key added here is automatically available to existing installations
 * without requiring a settings save.
 *
 * @return array<string, array<string, float>>
 */
function uc_calc_defaults() {
	return [
		'rates' => [
			'singleUnder25'          => 338.58,
			'single25Plus'           => 424.90,
			'coupleBothUnder25'      => 528.34,
			'coupleAny25Plus'        => 666.97,
			'childElement'           => 303.94,
			'workAllowanceNoHousing'      => 710.00,
			'taperRate'                   => 0.55,
			'benefitCapFamily'            => 1835.00,
			'benefitCapSingle'            => 1229.42,
			'benefitCapEarningsThreshold' => 881.00,
			'childBenefitEldest'          => 27.05,
			'childBenefitAdditional'      => 17.90,
		],
		'costs' => [
			'food_firstAdult'        => 35,
			'food_additionalAdult'   => 15,
			'food_childUnder5'       => 15,
			'food_child5to15'        => 20,
			'energy_1'               => 25,
			'energy_2'               => 30,
			'energy_3'               => 38,
			'energy_4'               => 38,
			'energy_5'               => 45,
			'energy_6'               => 45,
			'energy_7'               => 45,
			'energy_8'               => 45,
			'water_1'                => 6,
			'water_2'                => 9,
			'water_3'                => 11,
			'water_4'                => 13,
			'water_5'                => 15,
			'water_6'                => 15,
			'water_7'                => 15,
			'water_8'                => 15,
			'mobile_perAdult'        => 4,
			'broadband_flat'         => 5,
			'travel_working'         => 28,
			'travel_nonWorking'      => 10,
			'travel_child5to15'      => 4,
			'toiletries_perPerson'   => 5,
			'toiletries_base'        => 2,
			'cleaning_flat'          => 4,
			'clothes_perAdult'       => 5,
			'clothes_childUnder5'    => 6,
			'clothes_child5to15'     => 4,
			'schoolUniform_perChild' => 6,
			'tvLicence_flat'         => 3.46,
			'sundries_single'        => 13,
			'sundries_household'     => 20,
		],
	];
}

/**
 * Returns the merged settings array (saved values overlaid on defaults).
 *
 * @return array
 */
function uc_calc_get_settings() {
	$saved = get_option( 'uc_calc_settings', [] );
	if ( ! is_array( $saved ) ) {
		$saved = [];
	}
	return array_replace_recursive( uc_calc_defaults(), $saved );
}

// ─── Build JS-ready data structure ───────────────────────────────────────────

/**
 * Converts the flat settings array into the nested structure consumed by the JS bundle.
 *
 * @param array $settings Settings array from uc_calc_get_settings().
 * @return array
 */
function uc_calc_build_js_data( $settings ) {
	$r = $settings['rates'];
	$c = $settings['costs'];

	return [
		'rates' => [
			'standardAllowance' => [
				'singleUnder25'     => (float) $r['singleUnder25'],
				'single25Plus'      => (float) $r['single25Plus'],
				'coupleBothUnder25' => (float) $r['coupleBothUnder25'],
				'coupleAny25Plus'   => (float) $r['coupleAny25Plus'],
			],
			'childElement'           => (float) $r['childElement'],
			'workAllowanceNoHousing' => (float) $r['workAllowanceNoHousing'],
			'taperRate'              => (float) $r['taperRate'],
			'benefitCap'             => [
				'family'            => (float) $r['benefitCapFamily'],
				'single'            => (float) $r['benefitCapSingle'],
				'earningsThreshold' => (float) $r['benefitCapEarningsThreshold'],
			],
			'childBenefit'           => [
				'eldest'     => (float) $r['childBenefitEldest'],
				'additional' => (float) $r['childBenefitAdditional'],
			],
		],
		'costs' => [
			'food' => [
				'firstAdult'      => (float) $c['food_firstAdult'],
				'additionalAdult' => (float) $c['food_additionalAdult'],
				'childUnder5'     => (float) $c['food_childUnder5'],
				'child5to15'      => (float) $c['food_child5to15'],
			],
			'energy' => [
				'bracket' => [
					1 => (float) $c['energy_1'],
					2 => (float) $c['energy_2'],
					3 => (float) $c['energy_3'],
					4 => (float) $c['energy_4'],
					5 => (float) $c['energy_5'],
					6 => (float) $c['energy_6'],
					7 => (float) $c['energy_7'],
					8 => (float) $c['energy_8'],
				],
			],
			'water' => [
				'bracket' => [
					1 => (float) $c['water_1'],
					2 => (float) $c['water_2'],
					3 => (float) $c['water_3'],
					4 => (float) $c['water_4'],
					5 => (float) $c['water_5'],
					6 => (float) $c['water_6'],
					7 => (float) $c['water_7'],
					8 => (float) $c['water_8'],
				],
			],
			'mobile'    => [ 'perAdult' => (float) $c['mobile_perAdult'] ],
			'broadband' => [ 'flat'     => (float) $c['broadband_flat'] ],
			'travel'    => [
				'workingAdult'    => (float) $c['travel_working'],
				'nonWorkingAdult' => (float) $c['travel_nonWorking'],
				'child5to15'      => (float) $c['travel_child5to15'],
			],
			'toiletries' => [
				'perPerson'     => (float) $c['toiletries_perPerson'],
				'householdBase' => (float) $c['toiletries_base'],
			],
			'cleaning'      => [ 'flat'         => (float) $c['cleaning_flat'] ],
			'clothes'       => [
				'perAdult'    => (float) $c['clothes_perAdult'],
				'childUnder5' => (float) $c['clothes_childUnder5'],
				'child5to15'  => (float) $c['clothes_child5to15'],
			],
			'schoolUniform' => [ 'perChild5to15' => (float) $c['schoolUniform_perChild'] ],
			'tvLicence'     => [ 'flat'          => (float) $c['tvLicence_flat'] ],
			'sundries'      => [
				'single'    => (float) $c['sundries_single'],
				'household' => (float) $c['sundries_household'],
			],
		],
		'i18n' => uc_calc_i18n_strings(),
	];
}

/**
 * Translatable strings consumed by the JS bundle at runtime.
 *
 * @return array
 */
function uc_calc_i18n_strings() {
	return [
		'adultHeadings' => [
			'',
			__( 'Second adult', 'uc-calc' ),
			__( 'Third adult', 'uc-calc' ),
			__( 'Fourth adult', 'uc-calc' ),
			__( 'Fifth adult', 'uc-calc' ),
			__( 'Sixth adult', 'uc-calc' ),
		],
		'ordinals' => [
			__( 'first', 'uc-calc' ),
			__( 'second', 'uc-calc' ),
			__( 'third', 'uc-calc' ),
			__( 'fourth', 'uc-calc' ),
			__( 'fifth', 'uc-calc' ),
			__( 'sixth', 'uc-calc' ),
			__( 'seventh', 'uc-calc' ),
			__( 'eighth', 'uc-calc' ),
		],
		'ageLegendSelf'      => __( 'How old are you?', 'uc-calc' ),
		'ageLegendOther'     => __( 'How old are they?', 'uc-calc' ),
		'ageUnder25'         => __( 'Under 25', 'uc-calc' ),
		'ageOver25'          => __( '25 or over', 'uc-calc' ),
		'workingSelf'        => __( 'I am currently in work', 'uc-calc' ),
		'workingOther'       => __( 'Currently in work', 'uc-calc' ),
		'earningsLabelSelf'  => __( 'Your monthly take-home pay', 'uc-calc' ),
		'earningsLabelOther' => __( 'Monthly take-home pay', 'uc-calc' ),
		'earningsHint'       => __( 'Net (take-home) pay after tax and National Insurance.', 'uc-calc' ),
		'childAgeLegend'     => __( 'How old is your %s child?', 'uc-calc' ),
		'childAgeUnder5'     => __( 'Under 5', 'uc-calc' ),
		'childAge5to15'      => __( '5 to 15', 'uc-calc' ),
		'basketAriaIncluded' => __( '%1$s, %2$s per week', 'uc-calc' ),
		'basketAriaExcluded' => __( '%1$s, %2$s per week, excluded', 'uc-calc' ),
		/* translators: %s: monthly earnings threshold, e.g. £881.00. */
		'benefitCapNote'     => __( 'The benefit cap has lowered the UC shown here. The cap doesn’t apply if your household takes home at least %s a month from work, or if someone gets certain disability or carer benefits.', 'uc-calc' ),
		'states' => [
			'shortfall' => [
				'label'   => __( 'Shortfall', 'uc-calc' ),
				'subtext' => __( 'This is what you’d need to find from somewhere else each week, on top of what UC provides. And that’s before rent and council tax.', 'uc-calc' ),
			],
			'even' => [
				'label'   => __( 'Exactly enough', 'uc-calc' ),
				'subtext' => __( 'Nothing left for anything unexpected, and that’s before rent and council tax. A washing machine breakdown, a school trip, a winter coat, a funeral, a delayed payment.', 'uc-calc' ),
			],
			'surplus' => [
				'label'   => __( 'Left before rent and council tax', 'uc-calc' ),
				'subtext' => __( 'This is before rent and council tax. Many households on UC have to pay part of their rent from this money. Anything left has to cover the unexpected, like dental costs, a school trip or a winter coat.', 'uc-calc' ),
			],
			'empty' => [
				'label'   => __( 'Money left to save or for emergencies', 'uc-calc' ),
				'subtext' => __( 'You haven’t selected any essentials. Try ticking the items you actually need each week.', 'uc-calc' ),
			],
		],
	];
}

// ─── Asset registration & conditional enqueue ────────────────────────────────

/**
 * Registers (but does not enqueue) the front-end assets.
 */
function uc_calc_register_assets() {
	wp_register_style(
		'uc-calc',
		UC_CALC_URL . 'assets/uc-calc.css',
		[],
		UC_CALC_VERSION
	);
	wp_register_script(
		'uc-calc',
		UC_CALC_URL . 'assets/uc-calc.js',
		[],
		UC_CALC_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'uc_calc_register_assets' );

/**
 * Detects shortcode use on the current singular request and enqueues early
 * (so styles end up in the head). The shortcode handler also calls the same
 * enqueue function as a fallback for use in widgets, blocks, and templates.
 */
function uc_calc_maybe_enqueue() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	if ( ! has_shortcode( $post->post_content, 'uc_calculator' ) ) {
		return;
	}
	uc_calc_enqueue_assets();
}
add_action( 'wp_enqueue_scripts', 'uc_calc_maybe_enqueue', 20 );

/**
 * Enqueues the registered assets and attaches localised data. Idempotent.
 */
function uc_calc_enqueue_assets() {
	if ( wp_script_is( 'uc-calc', 'enqueued' ) ) {
		return;
	}
	wp_enqueue_style( 'uc-calc' );
	wp_enqueue_script( 'uc-calc' );
	wp_localize_script(
		'uc-calc',
		'ucCalcData',
		uc_calc_build_js_data( uc_calc_get_settings() )
	);
}

// ─── Shortcode ────────────────────────────────────────────────────────────────

/**
 * [uc_calculator] shortcode handler.
 */
function uc_calc_shortcode() {
	uc_calc_enqueue_assets();
	ob_start();
	include UC_CALC_DIR . 'inc/shortcode.php';
	return ob_get_clean();
}
add_shortcode( 'uc_calculator', 'uc_calc_shortcode' );

// ─── Text domain ──────────────────────────────────────────────────────────────

/**
 * Loads the plugin's translations.
 */
function uc_calc_load_textdomain() {
	load_plugin_textdomain(
		'uc-calc',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'uc_calc_load_textdomain' );

// ─── Admin: settings page + plugin row links ─────────────────────────────────

/**
 * Adds the Settings → UC Calculator submenu.
 */
function uc_calc_admin_menu() {
	add_options_page(
		__( 'UC Calculator Settings', 'uc-calc' ),
		__( 'UC Calculator', 'uc-calc' ),
		'manage_options',
		'uc-calc-settings',
		'uc_calc_render_settings_page'
	);
}
add_action( 'admin_menu', 'uc_calc_admin_menu' );

/**
 * Renders the settings page.
 */
function uc_calc_render_settings_page() {
	include UC_CALC_DIR . 'inc/settings.php';
}

/**
 * Adds a "Settings" link to the plugin's row on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function uc_calc_action_links( $links ) {
	$url           = admin_url( 'options-general.php?page=uc-calc-settings' );
	$settings_link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'uc-calc' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'uc_calc_action_links' );

// ─── GitHub updater ──────────────────────────────────────────────────────────

/**
 * Wires the GitHub-based updater. Loaded in every context, not just admin, so
 * update checks run by WP-Cron (including automatic updates) and WP-CLI see
 * new releases. The GitHub API is only called when core checks for updates.
 */
function uc_calc_init_updater() {
	if ( ! defined( 'UC_CALC_GITHUB_REPO' ) || '' === UC_CALC_GITHUB_REPO ) {
		return;
	}
	require_once UC_CALC_DIR . 'inc/updater.php';
	new UC_Calc_Updater( UC_CALC_FILE, UC_CALC_GITHUB_REPO, UC_CALC_VERSION );
}
add_action( 'init', 'uc_calc_init_updater' );

/**
 * Clears the cached GitHub release on deactivation so a reactivated plugin
 * checks afresh.
 */
function uc_calc_deactivate() {
	delete_transient( 'uc_calc_github_release' );
}
register_deactivation_hook( __FILE__, 'uc_calc_deactivate' );
