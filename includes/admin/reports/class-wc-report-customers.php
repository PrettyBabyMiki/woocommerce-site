<?php
/**
 * WC_Report_Customers
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Reports
 * @version     2.1.0
 */
class WC_Report_Customers extends WC_Admin_Report {

	public $chart_colours = array();
	
	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		$legend   = array();

		$legend[] = array(
			'title' => sprintf( __( '%s signups in this period', 'woocommerce' ), '<strong>' . sizeof( $this->customers ) . '</strong>' ),
			'color' => $this->chart_colours['signups'],
			'highlight_series' => 2
		);

		return $legend;
	}

	/**
	 * [get_chart_widgets description]
	 * @return array
	 */
	public function get_chart_widgets() {
		$widgets = array();

		$widgets[] = array(
			'title'    => '',
			'callback' => array( $this, 'customers_vs_guests' )
		);

		return $widgets;
	}

	/**
	 * customers_vs_guests
	 * @return void
	 */
	public function customers_vs_guests() {

		$customer_order_totals = $this->get_order_report_data( array(
			'data' => array(
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				)
			),
			'where_meta' => array(
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => '0',
					'operator'   => '>'
				)
			),
			'filter_range' => true
		) );

		$guest_order_totals = $this->get_order_report_data( array(
			'data' => array(
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				)
			),
			'where_meta' => array(
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => '0',
					'operator'   => '='
				)
			),
			'filter_range' => true
		) );
		?>
		<div class="chart-container">
			<div class="chart-placeholder customers_vs_guests pie-chart" style="height:200px"></div>
			<ul class="pie-chart-legend">
				<li style="border-color: <?php echo $this->chart_colours['customers']; ?>"><?php _e( 'Customer Sales', 'woocommerce' ); ?></li>
				<li style="border-color: <?php echo $this->chart_colours['guests']; ?>"><?php _e( 'Guest Sales', 'woocommerce' ); ?></li>
			</ul>
		</div>
		<script type="text/javascript">
			jQuery(function(){
 				jQuery.plot(
					jQuery('.chart-placeholder.customers_vs_guests'),
					[
						{
							label: '<?php _e( 'Customer Orders', 'woocommerce' ); ?>',
							data:  "<?php echo $customer_order_totals->total_orders ?>",
							color: '<?php echo $this->chart_colours['customers']; ?>'
						},
						{
							label: '<?php _e( 'Guest Orders', 'woocommerce' ); ?>',
							data:  "<?php echo $guest_order_totals->total_orders ?>",
							color: '<?php echo $this->chart_colours['guests']; ?>'
						}
					],
					{
						grid: {
				            hoverable: true
				        },
						series: {
					        pie: {
					            show: true,
					            radius: 1,
					            innerRadius: 0.6,
					            label: {
					                show: false
					            }
					        },
					        enable_tooltip: true,
					        append_tooltip: "<?php echo ' ' . __( 'orders', 'woocommerce' ); ?>",
					    },
					    legend: {
					        show: false
					    }
			 		}
			 	);

			 	jQuery('.chart-placeholder.customers_vs_guests').resize();
			});
		</script>
		<?php
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
			'signups'   => '#3498db',
			'customers' => '#1abc9c',
			'guests'    => '#8fdece'
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) )
			$current_range = '7day';

		$this->calculate_current_range( $current_range );

		$admin_users = new WP_User_Query(
			array(
				'role'   => 'administrator',
				'fields' => 'ID'
			)
		);

		$manager_users = new WP_User_Query(
			array(
				'role'   => 'shop_manager',
				'fields' => 'ID'
			)
		);

		$users_query = new WP_User_Query(
			array(
				'fields'  => array( 'user_registered' ),
				'exclude' => array_merge( $admin_users->get_results(), $manager_users->get_results() )
			)
		);

		$this->customers = $users_query->get_results();

		foreach ( $this->customers as $key => $customer ) {
			if ( strtotime( $customer->user_registered ) < $this->start_date || strtotime( $customer->user_registered ) > $this->end_date )
				unset( $this->customers[ $key ] );
		}

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php' );
	}

	/**
	 * Output an export link
	 */
	public function get_export_button() {
		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';
		?>
		<a
			href="#"
			download="report-<?php echo $current_range; ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php _e( 'Date', 'woocommerce' ); ?>"
			data-groupby="<?php echo $this->chart_groupby; ?>"
		>
			<?php _e( 'Export CSV', 'woocommerce' ); ?>
		</a>
		<?php
	}

	/**
	 * Get the main chart
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale;

		$customer_orders = $this->get_order_report_data( array(
			'data' => array(
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where_meta' => array(
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => '0',
					'operator'   => '>'
				)
			),
			'group_by'     => $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );

		$guest_orders = $this->get_order_report_data( array(
			'data' => array(
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where_meta' => array(
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => '0',
					'operator'   => '='
				)
			),
			'group_by'     => $this->group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );

		$signups         = $this->prepare_chart_data( $this->customers, 'user_registered', '', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$customer_orders = $this->prepare_chart_data( $customer_orders, 'post_date', 'total_orders', $this->chart_interval, $this->start_date, $this->chart_groupby );
		$guest_orders    = $this->prepare_chart_data( $guest_orders, 'post_date', 'total_orders', $this->chart_interval, $this->start_date, $this->chart_groupby );

		// Encode in json format
		$chart_data = json_encode( array(
			'signups'         => array_values( $signups ),
			'customer_orders' => array_values( $customer_orders ),
			'guest_orders'    => array_values( $guest_orders )
		) );
		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">
			var main_chart;

			jQuery(function(){
				var chart_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

				var drawGraph = function( highlight ) {
					var series = [
							{
								label: "<?php echo esc_js( __( 'Customer Orders', 'woocommerce' ) ) ?>",
								data: chart_data.customer_orders,
								color: '<?php echo $this->chart_colours['customers']; ?>',
								bars: { fillColor: '<?php echo $this->chart_colours['customers']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
								shadowSize: 0,
								enable_tooltip: true,
								append_tooltip: "<?php echo ' ' . __( 'customer orders', 'woocommerce' ); ?>",
								stack: true,
							},
							{
								label: "<?php echo esc_js( __( 'Guest Orders', 'woocommerce' ) ) ?>",
								data: chart_data.guest_orders,
								color: '<?php echo $this->chart_colours['guests']; ?>',
								bars: { fillColor: '<?php echo $this->chart_colours['guests']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
								shadowSize: 0,
								enable_tooltip: true,
								append_tooltip: "<?php echo ' ' . __( 'guest orders', 'woocommerce' ); ?>",
								stack: true,
							},
							{
								label: "<?php echo esc_js( __( 'Signups', 'woocommerce' ) ) ?>",
								data: chart_data.signups,
								color: '<?php echo $this->chart_colours['signups']; ?>',
								points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
								lines: { show: true, lineWidth: 4, fill: false },
								shadowSize: 0,
								enable_tooltip: true,
								append_tooltip: "<?php echo ' ' . __( 'new users', 'woocommerce' ); ?>",
								stack: false
							},
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

					main_chart = jQuery.plot(
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
								tickSize: [1, "<?php echo $this->chart_groupby; ?>"],
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