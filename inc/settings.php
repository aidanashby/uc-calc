<?php
/**
 * Settings → UC Calculator admin page.
 *
 * @package UC_Calc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to access this page.', 'uc-calc' ) );
}

$defaults = uc_calc_defaults();
$notice   = '';

if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
	check_admin_referer( 'uc_calc_settings_save', 'uc_calc_nonce' );

	if ( isset( $_POST['uc_calc_reset'] ) ) {
		delete_option( 'uc_calc_settings' );
		$notice = __( 'All values reset to the plugin defaults.', 'uc-calc' );
	} else {
		// Store only values that differ from the defaults, so rates and costs
		// updated in a new plugin release reach every field left unchanged.
		$new = [];
		foreach ( $defaults as $group => $keys ) {
			$posted = isset( $_POST[ $group ] ) && is_array( $_POST[ $group ] ) ? wp_unslash( $_POST[ $group ] ) : [];
			foreach ( $keys as $key => $default ) {
				if ( ! isset( $posted[ $key ] ) || ! is_numeric( $posted[ $key ] ) ) {
					continue;
				}
				$value = max( 0.0, (float) $posted[ $key ] );
				if ( abs( $value - (float) $default ) > 0.0001 ) {
					$new[ $group ][ $key ] = $value;
				}
			}
		}

		if ( $new ) {
			update_option( 'uc_calc_settings', $new );
		} else {
			delete_option( 'uc_calc_settings' );
		}
		$notice = __( 'Settings saved.', 'uc-calc' );
	}
}

$s = uc_calc_get_settings();

/**
 * Renders one row of the settings table.
 *
 * @param string $label Human-readable label for the field.
 * @param string $group Group name (`rates` or `costs`).
 * @param string $key   Field key within the group.
 * @param float  $value Current value.
 * @param string $hint  Optional descriptive hint shown beneath the input.
 */
function uc_calc_setting_row( $label, $group, $key, $value, $hint = '' ) {
	$field_id = sanitize_html_class( "uc_{$group}_{$key}" );
	$name     = "{$group}[{$key}]";
	$default  = uc_calc_defaults()[ $group ][ $key ];
	?>
	<tr>
		<th scope="row"><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label></th>
		<td>
			<input type="number" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				step="0.01" min="0" class="small-text">
			<?php if ( $hint ) : ?>
				<p class="description"><?php echo esc_html( $hint ); ?></p>
			<?php endif; ?>
			<?php if ( abs( (float) $value - (float) $default ) > 0.0001 ) : ?>
				<p class="description"><strong>
					<?php
					/* translators: %s: the plugin's default value for this field. */
					echo esc_html( sprintf( __( 'Changed from the default of %s.', 'uc-calc' ), $default ) );
					?>
				</strong></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'UC Calculator Settings', 'uc-calc' ); ?></h1>

	<?php if ( $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
	<?php endif; ?>

	<p><?php esc_html_e( 'All monetary values are in pounds per week unless stated otherwise. Only values you change are saved. Every other field follows the plugin defaults, which are kept up to date with each plugin update.', 'uc-calc' ); ?></p>

	<form method="post" action="">
		<?php wp_nonce_field( 'uc_calc_settings_save', 'uc_calc_nonce' ); ?>

		<h2>
			<?php esc_html_e( 'UC Rates', 'uc-calc' ); ?>
			<span style="font-weight:400;font-size:0.9em;"><?php esc_html_e( '(government figures — update when DWP rates change)', 'uc-calc' ); ?></span>
		</h2>
		<table class="form-table" role="presentation">
			<?php
			uc_calc_setting_row( __( 'Single, under 25 (monthly)', 'uc-calc' ),       'rates', 'singleUnder25',          $s['rates']['singleUnder25'],          __( 'Standard allowance per month.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Single, 25 or over (monthly)', 'uc-calc' ),     'rates', 'single25Plus',           $s['rates']['single25Plus'],           __( 'Standard allowance per month.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Couple, both under 25 (monthly)', 'uc-calc' ),  'rates', 'coupleBothUnder25',      $s['rates']['coupleBothUnder25'],      __( 'Standard allowance per month.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Couple, one or both 25+ (monthly)', 'uc-calc' ),'rates', 'coupleAny25Plus',        $s['rates']['coupleAny25Plus'],        __( 'Standard allowance per month.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Child element (monthly)', 'uc-calc' ),          'rates', 'childElement',           $s['rates']['childElement'],           __( 'Added per dependent child.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Work allowance — no housing (monthly)', 'uc-calc' ), 'rates', 'workAllowanceNoHousing', $s['rates']['workAllowanceNoHousing'], __( 'Applies to households with children.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Taper rate (decimal)', 'uc-calc' ),             'rates', 'taperRate',              $s['rates']['taperRate'],              __( 'E.g. 0.55 = 55p in every £1 earned reduces UC.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Benefit cap: couple or single parent (monthly)', 'uc-calc' ), 'rates', 'benefitCapFamily',            $s['rates']['benefitCapFamily'],            __( 'Outside London.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Benefit cap: single, no children (monthly)', 'uc-calc' ),     'rates', 'benefitCapSingle',            $s['rates']['benefitCapSingle'],            __( 'Outside London.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Benefit cap earnings threshold (monthly)', 'uc-calc' ),       'rates', 'benefitCapEarningsThreshold', $s['rates']['benefitCapEarningsThreshold'], __( 'Households taking home at least this much from work are exempt from the cap.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Child Benefit: eldest child (weekly)', 'uc-calc' ),           'rates', 'childBenefitEldest',          $s['rates']['childBenefitEldest'],          __( 'Not counted as income. Used only to work out the benefit cap.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Child Benefit: each other child (weekly)', 'uc-calc' ),       'rates', 'childBenefitAdditional',      $s['rates']['childBenefitAdditional'],      __( 'Not counted as income. Used only to work out the benefit cap.', 'uc-calc' ) );
			?>
		</table>

		<h2>
			<?php esc_html_e( 'Food', 'uc-calc' ); ?>
			<span style="font-weight:400;font-size:0.9em;"><?php esc_html_e( '(weekly, per person)', 'uc-calc' ); ?></span>
		</h2>
		<table class="form-table" role="presentation">
			<?php
			uc_calc_setting_row( __( 'First adult', 'uc-calc' ),       'costs', 'food_firstAdult',      $s['costs']['food_firstAdult'] );
			uc_calc_setting_row( __( 'Each extra adult', 'uc-calc' ),  'costs', 'food_additionalAdult', $s['costs']['food_additionalAdult'] );
			uc_calc_setting_row( __( 'Child under 5', 'uc-calc' ),     'costs', 'food_childUnder5',     $s['costs']['food_childUnder5'] );
			uc_calc_setting_row( __( 'Child 5 to 15', 'uc-calc' ),     'costs', 'food_child5to15',      $s['costs']['food_child5to15'] );
			?>
		</table>

		<h2>
			<?php esc_html_e( 'Energy (gas and electric)', 'uc-calc' ); ?>
			<span style="font-weight:400;font-size:0.9em;"><?php esc_html_e( '(weekly, by household size)', 'uc-calc' ); ?></span>
		</h2>
		<table class="form-table" role="presentation">
			<?php
			foreach ( range( 1, 8 ) as $n ) {
				$label = ( 8 === $n )
					/* translators: %d: household size (always 8 for the max bracket). */
					? sprintf( __( '%d people (max)', 'uc-calc' ), $n )
					/* translators: %d: household size (1–7). */
					: sprintf( _n( '%d person', '%d people', $n, 'uc-calc' ), $n );
				uc_calc_setting_row( $label, 'costs', "energy_{$n}", $s['costs'][ "energy_{$n}" ] );
			}
			?>
		</table>

		<h2>
			<?php esc_html_e( 'Water', 'uc-calc' ); ?>
			<span style="font-weight:400;font-size:0.9em;"><?php esc_html_e( '(weekly, by household size)', 'uc-calc' ); ?></span>
		</h2>
		<table class="form-table" role="presentation">
			<?php
			foreach ( range( 1, 8 ) as $n ) {
				$label = ( 8 === $n )
					? sprintf( __( '%d people (max)', 'uc-calc' ), $n )
					: sprintf( _n( '%d person', '%d people', $n, 'uc-calc' ), $n );
				uc_calc_setting_row( $label, 'costs', "water_{$n}", $s['costs'][ "water_{$n}" ] );
			}
			?>
		</table>

		<h2><?php esc_html_e( 'Mobile, broadband, TV licence', 'uc-calc' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			uc_calc_setting_row( __( 'Mobile — per adult per week', 'uc-calc' ),    'costs', 'mobile_perAdult', $s['costs']['mobile_perAdult'] );
			uc_calc_setting_row( __( 'Broadband — flat weekly rate', 'uc-calc' ),   'costs', 'broadband_flat',  $s['costs']['broadband_flat'] );
			uc_calc_setting_row( __( 'TV licence — flat weekly rate', 'uc-calc' ),  'costs', 'tvLicence_flat',  $s['costs']['tvLicence_flat'], __( '£180/year ÷ 52 = £3.46.', 'uc-calc' ) );
			?>
		</table>

		<h2><?php esc_html_e( 'Travel', 'uc-calc' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			uc_calc_setting_row( __( 'Working adult (weekly bus pass)', 'uc-calc' ),  'costs', 'travel_working',    $s['costs']['travel_working'] );
			uc_calc_setting_row( __( 'Non-working adult (ad-hoc trips)', 'uc-calc' ), 'costs', 'travel_nonWorking', $s['costs']['travel_nonWorking'] );
			uc_calc_setting_row( __( 'Child 5–15 per week', 'uc-calc' ),              'costs', 'travel_child5to15', $s['costs']['travel_child5to15'], __( 'Children under 5 travel free.', 'uc-calc' ) );
			?>
		</table>

		<h2><?php esc_html_e( 'Toiletries, cleaning, clothing', 'uc-calc' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			uc_calc_setting_row( __( 'Toiletries — per person per week', 'uc-calc' ),     'costs', 'toiletries_perPerson',  $s['costs']['toiletries_perPerson'] );
			uc_calc_setting_row( __( 'Toiletries — household base per week', 'uc-calc' ), 'costs', 'toiletries_base',       $s['costs']['toiletries_base'], __( 'Shared items (toilet roll etc.).', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Cleaning — flat weekly rate', 'uc-calc' ),          'costs', 'cleaning_flat',         $s['costs']['cleaning_flat'] );
			uc_calc_setting_row( __( 'Clothing — per adult per week', 'uc-calc' ),        'costs', 'clothes_perAdult',      $s['costs']['clothes_perAdult'] );
			uc_calc_setting_row( __( 'Clothing — child under 5 per week', 'uc-calc' ),    'costs', 'clothes_childUnder5',   $s['costs']['clothes_childUnder5'], __( 'Higher rate as young children outgrow clothes faster.', 'uc-calc' ) );
			uc_calc_setting_row( __( 'Clothing — child 5–15 per week', 'uc-calc' ),       'costs', 'clothes_child5to15',    $s['costs']['clothes_child5to15'] );
			uc_calc_setting_row( __( 'School uniform — per child 5–15 per week', 'uc-calc' ), 'costs', 'schoolUniform_perChild', $s['costs']['schoolUniform_perChild'] );
			?>
		</table>

		<h2><?php esc_html_e( 'Sundries', 'uc-calc' ); ?></h2>
		<table class="form-table" role="presentation">
			<?php
			uc_calc_setting_row( __( 'Sundries — single-person household per week', 'uc-calc' ), 'costs', 'sundries_single',    $s['costs']['sundries_single'] );
			uc_calc_setting_row( __( 'Sundries — multi-person household per week', 'uc-calc' ),  'costs', 'sundries_household', $s['costs']['sundries_household'] );
			?>
		</table>

		<p class="submit">
			<?php submit_button( __( 'Save Settings', 'uc-calc' ), 'primary', 'submit', false ); ?>
			<?php
			submit_button(
				__( 'Reset to defaults', 'uc-calc' ),
				'secondary',
				'uc_calc_reset',
				false,
				[ 'onclick' => 'return confirm(' . wp_json_encode( __( 'Reset every value to the plugin default?', 'uc-calc' ) ) . ');' ]
			);
			?>
		</p>
	</form>
</div>
