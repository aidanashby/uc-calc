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
		'info'  => __( 'Based on First Bus fares in Bristol. Each working adult is shown a weekly ticket for the Bristol zone (£28 since January 2026). Adults not in work are shown a lower amount for occasional essential trips.', 'uc-calc' ),
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
		'info'  => __( 'Based on typical school uniform costs averaged across primary and secondary schools in England. Since September 2026, schools can ask for no more than 3 branded items, or 4 at secondary school if one is a tie. Shown only when you have children aged 5 to 15.', 'uc-calc' ),
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
							<span class="uc-calc__stepper-value" id="num-adults-display" aria-live="polite">1</span>
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
							<span class="uc-calc__stepper-value" id="num-children-display" aria-live="polite">0</span>
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

		<div class="uc-calc__col uc-calc__col--basket">

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
							<div class="uc-calc__info-panel" id="basket-info-<?php echo esc_attr( $key ); ?>" hidden>
								<?php echo esc_html( $item['info'] ); ?>
							</div>
						</div>
					<?php endforeach; ?>

				</div>
			</section>

		</div>

		<div class="uc-calc__col uc-calc__col--result">

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
			<?php esc_html_e( 'This shows how far the basic amount of Universal Credit has to stretch each week, leaving out rent and council tax. UC and Council Tax Reduction help with these, but often don’t cover the full cost. In Bristol, the most UC pays towards rent hasn’t gone up since April 2024. That means many households pay part of their rent or council tax from the money shown here.', 'uc-calc' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Almost half of households on UC (47% in May 2026) also have money taken off their payment to repay debts, usually up to 15% of the basic amount.', 'uc-calc' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'It includes the basic amount and the child element only. It leaves out other parts of UC, disability and carer benefits such as PIP and DLA, and Child Benefit. It applies the benefit cap for outside London, counting Child Benefit but not help with rent, so for renters the cap may apply sooner. The cap usually doesn’t apply if someone gets a disability or carer benefit.', 'uc-calc' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Every child in a family on UC can get free school meals, and some households can get the £150 Warm Home Discount or Healthy Start.', 'uc-calc' ); ?>
		</p>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: link to Citizens Advice. */
					__( 'For advice about your own situation, contact %s or your local advice service. North Bristol & South Gloucestershire Foodbank isn’t a benefits adviser. This is an illustration to raise awareness, not a benefits calculator.', 'uc-calc' ),
					'<a href="https://www.citizensadvice.org.uk/">' . esc_html__( 'Citizens Advice', 'uc-calc' ) . '</a>'
				)
			);
			?>
		</p>
		<p class="uc-calc__footer-sources">
			<?php esc_html_e( 'Costs are April 2026 prices for Bristol and South Gloucestershire, at a realistic low-budget level. Sources: DWP UC rates and benefit cap, HMRC Child Benefit and TV Licensing (April 2026), Ofgem price cap (April 2026), Bristol Water and Wessex Water (2026/27), First Bus fares (January 2026), retailer prices (March 2026).', 'uc-calc' ); ?>
		</p>
	</footer>

</div>
