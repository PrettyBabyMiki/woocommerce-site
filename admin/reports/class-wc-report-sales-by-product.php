<?php
/**
 * WC_Report_Sales_By_Product class
 */
class WC_Report_Sales_By_Product extends WC_Admin_Report {

	public $product_ids = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( isset( $_GET['product_ids'] ) && is_array( $_GET['product_ids'] ) )
			$this->product_ids = array_map( 'absint', $_GET['product_ids'] );
		elseif ( isset( $_GET['product_ids'] ) )
			$this->product_ids = array( absint( $_GET['product_ids'] ) );
	}

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		if ( ! $this->product_ids )
			return array();

		$legend   = array();

		$total_sales 	= $this->get_order_report_data( array(
			'data' => array(
				'_line_total' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function' => 'SUM',
					'name'     => 'order_item_amount'
				)
			),
			'where_meta' => array(
				array(
					'type'       => 'order_item_meta',
					'meta_key'   => '_product_id',
					'meta_value' => $this->product_ids,
					'operator'   => 'IN'
				)
			),
			'query_type'   => 'get_var',
			'filter_range' => true
		) );
		$total_items    = absint( $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_count'
				)
			),
			'where_meta' => array(
				array(
					'type'       => 'order_item_meta',
					'meta_key'   => '_product_id',
					'meta_value' => $this->product_ids,
					'operator'   => 'IN'
				)
			),
			'query_type'   => 'get_var',
			'filter_range' => true
		) ) );

		$legend[] = array(
			'title' => sprintf( __( '%s sales for the selected items', 'woocommerce' ), '<strong>' . woocommerce_price( $total_sales ) . '</strong>' ),
			'color' => $this->chart_colours['sales_amount'],
			'highlight_series' => 1
		);
		$legend[] = array(
			'title' => sprintf( __( '%s purchases for the selected items', 'woocommerce' ), '<strong>' . $total_items . '</strong>' ),
			'color' => $this->chart_colours['item_count'],
			'highlight_series' => 0
		);

		return $legend;
	}

	/**
	 * Output the report
	 */
	public function output_report() {
		global $woocommerce, $wpdb, $wp_locale;

		$ranges = array(
			'year'         => __( 'Year', 'woocommerce' ),
			'last_month'   => __( 'Last Month', 'woocommerce' ),
			'month'        => __( 'This Month', 'woocommerce' ),
			'7day'         => __( 'Last 7 Days', 'woocommerce' )
		);

		$this->chart_colours = array(
			'sales_amount' => '#3498db',
			'item_count'   => '#d4d9dc',
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

		switch ( $current_range ) {
			case 'custom' :
				$this->start_date = strtotime( sanitize_text_field( $_GET['start_date'] ) );
				$this->end_date   = strtotime( '12am + 1 day', strtotime( sanitize_text_field( $_GET['end_date'] ) ) );

				if ( ! $this->end_date )
					$this->end_date = current_time('timestamp');

				$interval = 0;
				$min_date = $this->start_date;
				while ( ( $min_date = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
				    $interval ++;
				}

				// 3 months max for day view
				if ( $interval > 3 )
					$this->chart_groupby         = 'month';
				else
					$this->chart_groupby         = 'day';
			break;
			case 'year' :
				$this->start_date = strtotime( 'first day of january', current_time('timestamp') );
				$this->end_date   = strtotime( '12am + 1 day', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'month';
			break;
			case 'last_month' :
				$this->start_date = strtotime( 'first day of last month', current_time('timestamp') );
				$this->end_date   = strtotime( 'last day of last month', current_time('timestamp') );
				$this->chart_groupby         = 'day';
			break;
			case 'month' :
				$this->start_date = strtotime( 'first day of this month', current_time('timestamp') );
				$this->end_date   = strtotime( '12am + 1 day', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'day';
			break;
			case '7day' :
			default :
				$this->start_date = strtotime( '-6 days', current_time( 'timestamp' ) );
				$this->end_date   = strtotime( '12am + 1 day', current_time( 'timestamp' ) );
				$this->chart_groupby         = 'day';
			break;
		}

		// Group by
		switch ( $this->chart_groupby ) {
			case 'day' :
				$this->group_by_query       = 'YEAR(post_date), MONTH(post_date), DAY(post_date)';
				$this->chart_interval = max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) );
				$this->barwidth             = 60 * 60 * 24 * 1000;
			break;
			case 'month' :
				$this->group_by_query       = 'YEAR(post_date), MONTH(post_date)';
				$this->chart_interval = 0;
				$min_date             = $this->start_date;
				while ( ( $min_date   = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
					$this->chart_interval ++;
				}
				$this->barwidth             = 60 * 60 * 24 * 7 * 4 * 1000;
			break;
		}

		include( WC()->plugin_path() . '/admin/views/html-report-by-date.php');
	}

	/**
	 * [get_chart_widgets description]
	 * @return array
	 */
	public function get_chart_widgets() {

		$widgets = array();

		if ( ! empty( $this->product_ids ) ) {
			$widgets[] = array(
				'title'    => __( 'Showing reports for:', 'woocommerce' ),
				'callback' => array( $this, 'current_filters' )
			);
		}

		$widgets[] = array(
			'title'    => '',
			'callback' => array( $this, 'products_widget' )
		);

		return $widgets;
	}

	/**
	 * Show current filters
	 * @return void
	 */
	public function current_filters() {
		$this->product_ids_titles = array();

		foreach ( $this->product_ids as $product_id ) {
			$this->product_ids_titles[] = get_the_title( $product_id );
		}

		echo '<p>' . ' <strong>' . implode( ', ', $this->product_ids_titles ) . '</strong></p>';
		echo '<p><a class="button" href="' . remove_query_arg( 'product_ids' ) . '">' . __( 'Reset', 'woocommerce' ) . '</a></p>';
	}

	/**
	 * Product selection
	 * @return void
	 */
	public function products_widget() {
		?>
		<h4 class="section_title"><span><?php _e( 'Product Search', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<form method="GET">
				<div>
					<select id="product_ids" name="product_ids[]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" style="width:203px;"></select>
					<input type="submit" class="submit button" value="<?php _e( 'Show', 'woocommerce' ); ?>" />
					<input type="hidden" name="range" value="<?php if ( ! empty( $_GET['range'] ) ) echo esc_attr( $_GET['range'] ) ?>" />
					<input type="hidden" name="start_date" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ) ?>" />
					<input type="hidden" name="end_date" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ) ?>" />
					<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
					<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
					<input type="hidden" name="report" value="<?php if ( ! empty( $_GET['report'] ) ) echo esc_attr( $_GET['report'] ) ?>" />
				</div>
				<script type="text/javascript">
					jQuery(function(){
						// Ajax Chosen Product Selectors
						jQuery("select.ajax_chosen_select_products").ajaxChosen({
						    method: 	'GET',
						    url: 		'<?php echo admin_url('admin-ajax.php'); ?>',
						    dataType: 	'json',
						    afterTypeDelay: 100,
						    data:		{
						    	action: 		'woocommerce_json_search_products',
								security: 		'<?php echo wp_create_nonce("search-products"); ?>'
						    }
						}, function (data) {
							var terms = {};

						    jQuery.each(data, function (i, val) {
						        terms[i] = val;
						    });
						    return terms;
						});
					});
				</script>
			</form>
		</div>
		<h4 class="section_title"><span><?php _e( 'Top Sellers', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php
				$top_sellers = $this->get_order_report_data( array(
					'data' => array(
						'_product_id' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => '',
							'name'            => 'product_id'
						),
						'_qty' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'order_item_qty'
						)
					),
					'order_by' => 'order_item_qty DESC',
					'group_by' => 'product_id',
					'limit'    => 12,
					'query_type'    => 'get_results',
					'filter_range' => true
				) );

				if ( $top_sellers ) {
					foreach ( $top_sellers as $product ) {
						echo '<tr class="' . ( in_array( $product->product_id, $this->product_ids ) ? 'active' : '' ) . '">
							<td class="count">' . $product->order_item_qty . '</td>
							<td class="name"><a href="' . add_query_arg( 'product_ids', $product->product_id ) . '">' . get_the_title( $product->product_id ) . '</a></td>
							<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 14, 'count' ) . '</td>
						</tr>';
					}
				} else {
					echo '<tr><td colspan="3">' . __( 'No products found in range', 'woocommerce' ) . '</td></tr>';
				}
				?>
			</table>
		</div>
		<h4 class="section_title"><span><?php _e( 'Top Earners', 'woocommerce' ); ?></span></h4>
		<div class="section">
			<table cellspacing="0">
				<?php
				$top_earners = $this->get_order_report_data( array(
					'data' => array(
						'_product_id' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => '',
							'name'            => 'product_id'
						),
						'_line_total' => array(
							'type'            => 'order_item_meta',
							'order_item_type' => 'line_item',
							'function'        => 'SUM',
							'name'            => 'order_item_total'
						)
					),
					'order_by' => 'order_item_total DESC',
					'group_by' => 'product_id',
					'limit'    => 12,
					'query_type'    => 'get_results',
					'filter_range' => true
				) );

				if ( $top_earners ) {
					foreach ( $top_earners as $product ) {
						echo '<tr class="' . ( in_array( $product->product_id, $this->product_ids ) ? 'active' : '' ) . '">
							<td class="count">' . woocommerce_price( $product->order_item_total ) . '</td>
							<td class="name"><a href="' . add_query_arg( 'product_ids', $product->product_id ) . '">' . get_the_title( $product->product_id ) . '</a></td>
							<td class="sparkline">' . $this->sales_sparkline( $product->product_id, 14, 'sales' ) . '</td>
						</tr>';
					}
				} else {
					echo '<tr><td colspan="3">' . __( 'No products found in range', 'woocommerce' ) . '</td></tr>';
				}
				?>
			</table>
		</div>
		<script type="text/javascript">
			jQuery('.section_title').click(function(){
				var next_section = jQuery(this).next('.section');

				if ( jQuery(next_section).is(':visible') )
					return false;

				jQuery('.section:visible').slideUp();
				jQuery('.section_title').removeClass('open');
				jQuery(this).addClass('open').next('.section').slideDown();

				return false;
			});
			jQuery('.section').slideUp( 100, function() {
				<?php if ( empty( $this->product_ids ) ) : ?>
					jQuery('.section_title:eq(1)').click();
				<?php endif; ?>
			});
		</script>
		<?php
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		if ( ! $this->product_ids ) {
			?>
			<div class="chart-container">
				<p class="chart-prompt"><?php _e( '&larr; Choose a product to view stats', 'woocommerce' ); ?></p>
			</div>
			<?php
		} else {
			// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
			$order_item_counts = $this->get_order_report_data( array(
				'data' => array(
					'_qty' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function'        => 'SUM',
						'name'            => 'order_item_count'
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date'
					),
				),
				'where_meta' => array(
					array(
						'type'       => 'order_item_meta',
						'meta_key'   => '_product_id',
						'meta_value' => $this->product_ids,
						'operator'   => 'IN'
					)
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true
			) );

			$order_item_amounts = $this->get_order_report_data( array(
				'data' => array(
					'_line_total' => array(
						'type'            => 'order_item_meta',
						'order_item_type' => 'line_item',
						'function' => 'SUM',
						'name'     => 'order_item_amount'
					),
					'post_date' => array(
						'type'     => 'post_data',
						'function' => '',
						'name'     => 'post_date'
					),
				),
				'where_meta' => array(
					array(
						'type'       => 'order_item_meta',
						'meta_key'   => '_product_id',
						'meta_value' => $this->product_ids,
						'operator'   => 'IN'
					)
				),
				'group_by'     => $this->group_by_query,
				'order_by'     => 'post_date ASC',
				'query_type'   => 'get_results',
				'filter_range' => true
			) );

			// Prepare data for report
			$order_item_counts  = $this->prepare_chart_data( $order_item_counts, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
			$order_item_amounts = $this->prepare_chart_data( $order_item_amounts, 'post_date', 'order_item_amount', $this->chart_interval, $this->start_date, $this->chart_groupby );

			// Encode in json format
			$chart_data = json_encode( array(
				'order_item_counts'  => array_values( $order_item_counts ),
				'order_item_amounts' => array_values( $order_item_amounts )
			) );
			?>
			<div class="chart-container">
				<div class="chart-placeholder main"></div>
			</div>
			<script type="text/javascript">
				jQuery(function(){
					var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

					var drawGraph = function( highlight ) {

						var series = [
							{
								label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
								data: order_data.order_item_counts,
								color: '<?php echo $this->chart_colours['item_count']; ?>',
								bars: { fillColor: '<?php echo $this->chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
								shadowSize: 0,
								hoverable: false
							},
							{
								label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
								data: order_data.order_item_amounts,
								yaxis: 2,
								color: '<?php echo $this->chart_colours['sales_amount']; ?>',
								points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
								lines: { show: true, lineWidth: 4, fill: false },
								shadowSize: 0,
								prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
							}
						];

						if ( highlight !== 'undefined' && series[ highlight ] ) {
							highlight_series = series[ highlight ];

							highlight_series.color = '#9c5d90';

							if ( highlight_series.bars )
								highlight_series.bars.fillColor = '#9c5d90';

							if ( highlight_series.lines ) {
								highlight_series.lines.lineWidth = 5;
							}
						}

						jQuery.plot(
							jQuery('.chart-placeholder.main'),
							series,
							{
								legend: {
									show: false
								},
							    grid: {
							        color: '#aaa',
							        borderColor: 'transparent',
							        borderWidth: 0,
							        hoverable: true
							    },
							    xaxes: [ {
							    	color: '#aaa',
							    	position: "bottom",
							    	tickColor: 'transparent',
									mode: "time",
									timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
									monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
									tickLength: 1,
									minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
									font: {
							    		color: "#aaa"
							    	}
								} ],
							    yaxes: [
							    	{
							    		min: 0,
							    		minTickSize: 1,
							    		tickDecimals: 0,
							    		color: '#ecf0f1',
							    		font: { color: "#aaa" }
							    	},
							    	{
							    		position: "right",
							    		min: 0,
							    		tickDecimals: 2,
							    		alignTicksWithAxis: 1,
							    		color: 'transparent',
							    		font: { color: "#aaa" }
							    	}
							    ],
					 		}
					 	);

					 	jQuery('.chart-placeholder').resize();
					 }

					drawGraph();

					jQuery('.highlight_series').hover(
						function() {
							drawGraph( jQuery(this).data('series') );
						},
						function() {
							drawGraph();
						}
					);
				});
			</script>
			<?php
		}
	}
}