<?php
/**
 * Shortcode template for [uc_calculator]. All user-facing strings are
 * translatable via the `uc-calc` text domain.
 *
 * @package UC_Calc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$basket_items = [
	'food' => [
		'label' => __( 'Food', 'uc-calc' ),
		'info'  => __( 'Based on Aldi pricing for a realistic low-cost weekly shop in Bristol and South Gloucestershire. Figures scale with household size.', 'uc-calc' ),
	],
	'energy' => [
		'label' => __( 'Energy (gas and electric)', 'uc-calc' ),
		'info'  => __( 'Calculated from Ofgem’s April 2026 price cap, with a small addition to reflect the prepayment meter cost penalty common among UC claimants. Based on household size.', 'uc-calc' ),
	],
	'water' => [
		'label' => __( 'Water', 'uc-calc' ),
		'info'  => __( 'Bristol Water and Wessex Water (sewerage) charges on a meter, averaged for 2026/27, based on household size.', 'uc-calc' ),
	],
	'mobile' => [
		'label' => __( 'Mobile phones', 'uc-calc' ),
		'info'  => __( 'Based on a basic SIM-only plan at the lower end of the market, per adult in the household.', 'uc-calc' ),
	],
	'broadband' => [
		'label' => __( 'Home broadband', 'uc-calc' ),
		'info'  => __( 'Based on an average broadband contract. Some providers offer cheaper social tariffs for people on UC.', 'uc-calc' ),
	],
	'travel' => [
		'label' => __( 'Travel (bus)', 'uc-calc' ),
		'info'  => __( 'Based on First Bus weekly pass rates for Bristol. Working adults are shown the cost of getting to work most days. Non-working adults are shown a lower rate for ad-hoc essential trips.', 'uc-calc' ),
	],
	'toiletries' => [
		'label' => __( 'Toiletries and period products', 'uc-calc' ),
		'info'  => __( 'Covers personal hygiene, toiletries, and period products for each person in the household, plus shared items such as toilet roll.', 'uc-calc' ),
	],
	'cleaning' => [
		'label' => __( 'Cleaning products', 'uc-calc' ),
		'info'  => __( 'Covers basic household cleaning products for the whole household.', 'uc-calc' ),
	],
	'clothes' => [
		'label' => __( 'Everyday clothing', 'uc-calc' ),
		'info'  => __( 'Allows for replacing everyday clothing over time, based on retailer pricing in March 2026. Children under 5 have a higher allowance as they grow out of clothes faster.', 'uc-calc' ),
	],
	'schoolUniform' => [
		'label' => __( 'School uniform and shoes', 'uc-calc' ),
		'info'  => __( 'Based on typical school uniform costs averaged across primary and secondary schools in England, including the statutory cap on branded items. Shown only when you have children aged 5 to 15.', 'uc-calc' ),
	],
	'sundries' => [
		'label' => __( 'Sundries', 'uc-calc' ),
		'info'  => __( 'Covers everyday costs not already listed: haircuts, laundry, postage, non-prescription medicines, small household items, and bank or card charges.', 'uc-calc' ),
	],
	'tvLicence' => [
		'label' => __( 'TV licence', 'uc-calc' ),
		'info'  => __( 'The colour TV licence costs £180 a year from 1 April 2026.', 'uc-calc' ),
	],
];
?>
<div class="uc-calc" id="uc-calc">

	<noscript>
		<p class="uc-calc__noscript">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %1$s: emphasised UC weekly rate, %2$s: link to Trussell campaign. */
					__( 'JavaScript needs to be enabled to run this calculator. For a single adult on Universal Credit in April 2026, the standard rate is %1$s — often not enough to cover everyday essentials. Find out more at %2$s.', 'uc-calc' ),
					'<strong>£98.05 ' . esc_html__( 'a week', 'uc-calc' ) . '</strong>',
					'<a href="https://www.trusselltrust.org/get-involved/campaigns/guarantee-our-essentials/">' . esc_html__( 'Trussell’s Guarantee Our Essentials campaign', 'uc-calc' ) . '</a>'
				)
			);
			?>
		</p>
	</noscript>

	<p class="uc-calc__privacy">
		<?php esc_html_e( 'Everything you enter stays in your browser. No information is sent to us or anyone else, and nothing is saved when you leave this page.', 'uc-calc' ); ?>
	</p>

	<div class="uc-calc__layout">

		<div class="uc-calc__col uc-calc__col--household">
			<section class="uc-calc__section uc-calc__section--household" aria-labelledby="uc-calc-household-heading">
				<h2 class="uc-calc__section-heading" id="uc-calc-household-heading">
					<?php esc_html_e( 'Tell us about your household', 'uc-calc' ); ?>
				</h2>

				<form id="uc-calc-form" novalidate autocomplete="off">

					<div class="uc-calc__field-group">
						<span class="uc-calc__field-label" id="num-adults-label">
							<?php esc_html_e( 'Number of adults', 'uc-calc' ); ?>
						</span>
						<div class="uc-calc__stepper" role="group" aria-labelledby="num-adults-label">
							<button type="button" class="uc-calc__stepper-btn" id="adults-decrease"
								aria-label="<?php esc_attr_e( 'One fewer adult', 'uc-calc' ); ?>" disabled>-</button>
							<span class="uc-calc__stepper-value" role="spinbutton"
								aria-valuenow="1" aria-valuemin="1" aria-valuemax="6"
								aria-labelledby="num-adults-label"
								id="num-adults-display" tabindex="0">1</span>
							<button type="button" class="uc-calc__stepper-btn" id="adults-increase"
								aria-label="<?php esc_attr_e( 'One more adult', 'uc-calc' ); ?>">+</button>
						</div>
					</div>

					<div class="uc-calc__field-group" id="couple-group" hidden>
						<label class="uc-calc__checkbox-label">
							<input type="checkbox" name="inCouple" id="inCouple" class="uc-calc__checkbox">
							<span class="uc-calc__checkbox-text">
								<?php esc_html_e( 'Two of us are in a couple or civil partnership', 'uc-calc' ); ?>
							</span>
						</label>
					</div>

					<div id="adult-rows-container"></div>

					<div class="uc-calc__field-group">
						<span class="uc-calc__field-label" id="num-children-label">
							<?php esc_html_e( 'Dependent children under 16', 'uc-calc' ); ?>
						</span>
						<div class="uc-calc__stepper" role="group" aria-labelledby="num-children-label">
							<button type="button" class="uc-calc__stepper-btn" id="children-decrease"
								aria-label="<?php esc_attr_e( 'One fewer child', 'uc-calc' ); ?>" disabled>-</button>
							<span class="uc-calc__stepper-value" role="spinbutton"
								aria-valuenow="0" aria-valuemin="0" aria-valuemax="8"
								aria-labelledby="num-children-label"
								id="num-children-display" tabindex="0">0</span>
							<button type="button" class="uc-calc__stepper-btn" id="children-increase"
								aria-label="<?php esc_attr_e( 'One more child', 'uc-calc' ); ?>">+</button>
						</div>
					</div>

					<div id="child-ages-container" aria-live="polite" aria-atomic="false"></div>

				</form>

				<button type="button" class="uc-calc__reset" id="uc-calc-reset">
					<?php esc_html_e( 'Start again', 'uc-calc' ); ?>
				</button>

			</section>
		</div>

		<div class="uc-calc__col uc-calc__col--right">

			<section class="uc-calc__section uc-calc__section--basket" aria-labelledby="uc-calc-basket-heading">
				<h2 class="uc-calc__section-heading" id="uc-calc-basket-heading">
					<?php esc_html_e( 'Your weekly essentials', 'uc-calc' ); ?>
				</h2>

				<div class="uc-calc__basket" id="uc-calc-basket">

					<?php foreach ( $basket_items as $key => $item ) : ?>
						<div class="uc-calc__basket-row" id="basket-row-<?php echo esc_attr( $key ); ?>">
							<label class="uc-calc__basket-label">
								<input type="checkbox" class="uc-calc__basket-check uc-calc__checkbox"
									data-item="<?php echo esc_attr( $key ); ?>" checked
									aria-label="<?php echo esc_attr( $item['label'] ); ?>">
								<span class="uc-calc__basket-name"><?php echo esc_html( $item['label'] ); ?></span>
							</label>
							<span class="uc-calc__basket-cost" id="basket-cost-<?php echo esc_attr( $key ); ?>">£&#8203;</span>
							<button type="button" class="uc-calc__info-btn"
								aria-label="<?php
								echo esc_attr(
									sprintf(
										/* translators: %s: basket item name. */
										__( 'More about %s', 'uc-calc' ),
										$item['label']
									)
								);
								?>"
								aria-expanded="false"
								aria-controls="basket-info-<?php echo esc_attr( $key ); ?>">&#9432;</button>
							<div class="uc-calc__info-panel" id="basket-info-<?php echo esc_attr( $key ); ?>" role="tooltip" hidden>
								<?php echo esc_html( $item['info'] ); ?>
							</div>
						</div>
					<?php endforeach; ?>

				</div>
			</section>

			<section class="uc-calc__section uc-calc__result" id="uc-calc-result"
				aria-labelledby="uc-calc-result-heading" aria-live="polite" aria-atomic="true">

				<div class="uc-calc__result-header">
					<h2 class="uc-calc__result-heading" id="uc-calc-result-heading">
						<?php esc_html_e( 'The result', 'uc-calc' ); ?>
					</h2>
				</div>

				<div class="uc-calc__result-top">
					<div class="uc-calc__result-col">
						<span class="uc-calc__result-label">
							<?php esc_html_e( 'Income per week', 'uc-calc' ); ?>
						</span>
						<output class="uc-calc__result-figure" id="uc-calc-income">£98.05</output>
					</div>
					<div class="uc-calc__result-col">
						<span class="uc-calc__result-label">
							<?php esc_html_e( 'Essentials per week', 'uc-calc' ); ?>
						</span>
						<output class="uc-calc__result-figure" id="uc-calc-essentials">£117.46</output>
					</div>
				</div>

				<div class="uc-calc__result-diff">
					<output class="uc-calc__result-figure uc-calc__result-figure--large" id="uc-calc-difference">£19.41</output>
					<p class="uc-calc__result-state-label" id="uc-calc-state-label"></p>
					<p class="uc-calc__result-subtext" id="uc-calc-subtext"></p>
					<p class="uc-calc__result-cap-note" id="uc-calc-cap-note" hidden></p>
				</div>

			</section>

		</div>

	</div>

	<footer class="uc-calc__footer">
		<p>
			<?php esc_html_e( 'This calculator shows what the basic rate of Universal Credit has to stretch across, after rent and council tax. UC’s housing element helps with rent up to a capped amount called Local Housing Allowance, which in Bristol hasn’t risen since April 2024 even as rents have. Council tax is handled separately through Council Tax Reduction. Many people end up topping up rent or council tax from the same standard rate this calculator looks at. Almost half of UC households (46% in February 2026) have money taken off their payment to repay advance loans or other debts, usually up to 15% of the standard rate.', 'uc-calc' ); ?>
		</p>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: link to Citizens Advice. */
					__( 'It covers the standard allowance and child element only, and applies the benefit cap for outside London. It does not include the LCWRA addition, carer element, disabled child addition, Personal Independence Payment, DLA, Child Benefit, or the housing and childcare elements of UC. Child Benefit is not counted as income here, but it is counted towards the benefit cap. If anyone in your household gets a disability or carer benefit, the cap usually does not apply. If any of these apply, your actual UC and support costs may differ. Some households get other help that lowers their costs: free school meals for every child in a household on UC, the £150 Warm Home Discount on energy bills, and Healthy Start payments for some families with a child under 4. For a personal benefits check, contact %s or your local advice service. North Bristol & South Gloucestershire Foodbank is not a qualified benefits adviser. This tool is for awareness and campaigning only.', 'uc-calc' ),
					'<a href="https://www.citizensadvice.org.uk/">' . esc_html__( 'Citizens Advice', 'uc-calc' ) . '</a>'
				)
			);
			?>
		</p>
		<p class="uc-calc__footer-sources">
			<?php esc_html_e( 'Costs reflect April 2026 prices for Bristol and South Gloucestershire, at a realistic low-budget level. Data: Ofgem (April 2026 price cap), DWP UC rates and benefit cap April 2026, HMRC Child Benefit rates April 2026, First Bus fares January 2026, Bristol Water and Wessex Water 2026/27, retailer pricing March 2026, TV Licensing April 2026.', 'uc-calc' ); ?>
		</p>
	</footer>

</div>
