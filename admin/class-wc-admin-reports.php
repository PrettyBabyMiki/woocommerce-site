<?php
/**
 * Admin Reports
 *
 * Functions used for displaying sales and customer reports in admin.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Reports
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Admin_Reports Class
 */
class WC_Admin_Reports {

	private $start_date;
	private $end_date;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'admin_menu', array( $this, 'add_menu_item' ), 20 );
		add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_id' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
	}

	/**
	 * Add menu item
	 */
	public function add_menu_item() {
		add_submenu_page( 'woocommerce', __( 'Reports', 'woocommerce' ),  __( 'Reports', 'woocommerce' ) , 'view_woocommerce_reports', 'wc_reports', array( $this, 'admin_page' ) );
	}

	/**
	 * Add screen ID
	 * @param array $ids
	 */
	public function add_screen_id( $ids ) {
		$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );
		$ids[]        = $wc_screen_id . '_page_wc_reports';
		return $ids;
	}

	/**
	 * Script and styles
	 */
	public function scripts_and_styles() {
		$screen       = get_current_screen();
		$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );
		$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( in_array( $screen->id, apply_filters( 'woocommerce_reports_screen_ids', array( $wc_screen_id . '_page_wc_reports' ) ) ) ) {
			wp_enqueue_script( 'wc-reports', WC()->plugin_url() . '/assets/js/admin/reports' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), '1.0' );
			wp_enqueue_script( 'flot', WC()->plugin_url() . '/assets/js/admin/jquery.flot' . $suffix . '.js', array( 'jquery' ), '1.0' );
			wp_enqueue_script( 'flot-resize', WC()->plugin_url() . '/assets/js/admin/jquery.flot.resize' . $suffix . '.js', array('jquery', 'flot'), '1.0' );
			wp_enqueue_script( 'flot-time', WC()->plugin_url() . '/assets/js/admin/jquery.flot.time' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
			wp_enqueue_script( 'flot-pie', WC()->plugin_url() . '/assets/js/admin/jquery.flot.pie' . $suffix . '.js', array( 'jquery', 'flot' ), '1.0' );
		}
	}

	/**
	 * Returns the definitions for the reports to show in admin.
	 *
	 * @return array
	 */
	public function get_reports() {
		$reports = array(
			'sales'     => array(
				'title'  => __( 'Sales', 'woocommerce' ),
				'reports' => array(
					"sales"          => array(
						'title'       => __( 'Sales by date', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( $this, 'sales_report' )
					),
					"product_sales"     => array(
						'title'       => __( 'Product Sales', 'woocommerce' ),
						'description' => '',
						'callback'    => 'woocommerce_product_sales'
					),
					"sales_by_category" => array(
						'title'       => __( 'Sales by category', 'woocommerce' ),
						'description' => '',
						'callback'    => 'woocommerce_category_sales'
					) )
			),
			'discounts'   => array(
				'title'  => __( 'Discounts', 'woocommerce' ),
				'reports' => array(
					"overview"            => array(
						'title'       => __( 'Overview', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => 'woocommerce_coupons_overview'
					),
					"discounts_by_coupon" => array(
						'title'       => __( 'Discounts by coupon', 'woocommerce' ),
						'description' => '',
						'callback'    => 'woocommerce_coupon_discounts'
					)
				)
			),
			'customers' => array(
				'title'  => __( 'Customers', 'woocommerce' ),
				'reports' => array(
					"overview" => array(
						'title'       => __( 'Overview', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => 'woocommerce_customer_overview'
					),
				)
			),
			'stock'     => array(
				'title'  => __( 'Stock', 'woocommerce' ),
				'reports' => array(
					"overview" => array(
						'title'       => __( 'Overview', 'woocommerce' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => 'woocommerce_stock_overview'
					),
				)
			)
		);

		if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {
			$reports['sales']['reports']["taxes_by_month"] = array(
				'title'       => __( 'Taxes by month', 'woocommerce' ),
				'description' => '',
				'callback'    => 'woocommerce_monthly_taxes'
			);
		}

		$reports = apply_filters( 'woocommerce_admin_reports', $reports );

		// Backwards compat
		$reports = apply_filters( 'woocommerce_reports_charts', $reports );

		foreach ( $reports as $key => $report_group ) {
			if ( isset( $reports[ $key ]['charts'] ) )
					$reports[ $key ]['charts'] = $reports[ $key ]['reports'];

			foreach ( $report_group['reports'] as $report_key => $report ) {
				if ( isset( $reports[ $key ][ $report_key ]['function'] ) )
					$reports[ $key ][ $report_key ]['callback'] = $reports[ $key ][ $report_key ]['function'];
			}
		}

		return $reports;
	}

	/**
	 * Handles output of the reports page in admin.
	 */
	public function admin_page() {
		$reports        = $this->get_reports();
		$first_tab      = array_keys( $reports );
		$current_tab    = ! empty( $_GET['tab'] ) ? sanitize_title( urldecode( $_GET['tab'] ) ) : $first_tab[0];
		$current_report = isset( $_GET['report'] ) ? sanitize_title( urldecode( $_GET['report'] ) ) : current( array_keys( $reports[ $current_tab ]['reports'] ) );

		include( 'views/html-admin-page-reports.php' );
	}

	/**
	 * Get report totals such as order totals and discount amounts.
	 *
	 * Data example:
	 *
	 * '_order_total' => array(
	 * 		'type'     => 'meta',
	 *    	'function' => 'SUM',
	 *      'name'     => 'total_sales'
	 * )
	 *
	 * @param  array $args
	 * @return array of results
	 */
	public function get_order_report_data( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'data'         => array(),
			'where'        => array(),
			'where_meta'   => array(),
 			'query_type'   => 'get_row',
			'group_by'     => '',
			'order_by'     => '',
			'limit'        => '',
			'filter_range' => false
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		if ( empty( $data ) )
			return false;

		$select = array();

		foreach ( $data as $key => $value ) {
			if ( $value['type'] == 'meta' )
				$select[] = "{$value['function']}(meta_{$key}.meta_value) as {$value['name']}";
			elseif( $value['type'] == 'post_data' )
				$select[] = "{$value['function']}(posts.{$key}) as {$value['name']}";
			elseif( $value['type'] == 'order_item_meta' )
				$select[] = "{$value['function']}(order_item_meta_{$key}.meta_value) as {$value['name']}";
		}

		$query['select'] = "SELECT " . implode( ',', $select );
		$query['from']   = "FROM {$wpdb->posts} AS posts";
		$query['join']   = "
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )";

		foreach ( $data as $key => $value ) {
			if ( $value['type'] == 'meta' ) {

				$query['join'] .= " LEFT JOIN {$wpdb->postmeta} AS meta_{$key} ON posts.ID = meta_{$key}.post_id";

			} elseif ( $value['type'] == 'order_item_meta' ) {

				$query['join'] .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS order_items_{$key} ON posts.ID = order_items_{$key}.order_id";
				$query['join'] .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_{$key} ON order_items_{$key}.order_item_id = order_item_meta_{$key}.order_item_id";

			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				// If we have a where clause for meta, join the postmeta table
				$query['join'] .= " LEFT JOIN {$wpdb->postmeta} AS meta_{$value['meta_key']} ON posts.ID = meta_{$value['meta_key']}.post_id";
			}
		}

		$query['where']  = "
			WHERE 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			";

		if ( $filter_range ) {
			$query['where'] .= "
				AND 	post_date > '" . date('Y-m-d', $this->start_date ) . "'
				AND 	post_date < '" . date('Y-m-d', $this->end_date ) . "'
			";
		}

		foreach ( $data as $key => $value ) {
			if ( $value['type'] == 'meta' ) {

				$query['where'] .= " AND meta_{$key}.meta_key = '{$key}'";

			} elseif ( $value['type'] == 'order_item_meta' ) {

				$query['where'] .= " AND order_items_{$key}.order_item_type = '{$value['order_item_type']}'";
				$query['where'] .= " AND order_item_meta_{$key}.meta_key = '{$key}'";

			}
		}

		if ( ! empty( $where_meta ) ) {
			foreach ( $where_meta as $value ) {
				$query['where'] .= " AND meta_{$value['meta_key']}.meta_key   = '{$value['meta_key']}'";
				$query['where'] .= " AND meta_{$value['meta_key']}.meta_value {$value['operator']} '{$value['meta_value']}'";
			}
		}

		if ( ! empty( $where ) ) {
			foreach ( $where as $value ) {
				$query['where'] .= " AND {$value['key']} {$value['operator']} '{$value['value']}'";
			}
		}

		if ( $group_by ) {
			$query['group_by'] = "GROUP BY {$group_by}";
		}

		if ( $order_by ) {
			$query['order_by'] = "ORDER BY {$order_by}";
		}

		if ( $limit ) {
			$query['limit'] = "LIMIT {$limit}";
		}

		return apply_filters( 'woocommerce_reports_get_order_report_data', $wpdb->$query_type( implode( ' ', $query ) ), $data );
	}

	/**
	 * Put data with post_date's into an array of times
	 *
	 * @param  array $data array of your data
	 * @param  string $date_key key for the 'date' field. e.g. 'post_date'
	 * @param  string $data_key key for the data you are charting
	 * @param  int $interval
	 * @param  string $start_date
	 * @param  string $group_by
	 * @return string
	 */
	public function prepare_chart_data( $data, $date_key, $data_key, $interval, $start_date, $group_by ) {
		$prepared_data = array();

		// Ensure all days (or months) have values first in this range
		for ( $i = 0; $i <= $interval; $i ++ ) {
			switch ( $group_by ) {
				case 'day' :
					$time = strtotime( date( 'Ymd', strtotime( "+{$i} DAY", $start_date ) ) ) * 1000;
				break;
				case 'month' :
					$time = strtotime( date( 'Ym', strtotime( "+{$i} MONTH", $start_date ) ) . '01' ) * 1000;
				break;
			}

			if ( ! isset( $prepared_data[ $time ] ) )
				$prepared_data[ $time ] = array( esc_js( $time ), 0 );
		}

		foreach ( $data as $d ) {
			switch ( $group_by ) {
				case 'day' :
					$time = strtotime( date( 'Ymd', strtotime( $d->$date_key ) ) ) * 1000;
				break;
				case 'month' :
					$time = strtotime( date( 'Ym', strtotime( $d->$date_key ) ) . '01' ) * 1000;
				break;
			}

			if ( ! isset( $prepared_data[ $time ] ) )
				continue;

			$prepared_data[ $time ][1] += $d->$data_key;
		}

		return $prepared_data;
	}

	/**
	 * Prepares a sparkline to show sales in the last X days
	 *
	 * @param  int $id
	 * @param  int $days
	 */
	public function sales_sparkline( $id, $days, $type ) {
		$meta_key = $type == 'sales' ? '_line_total' : '_qty';

		$data = $this->get_order_report_data( array(
			'data' => array(
				'_product_id' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => '',
					'name'            => 'product_id'
				),
				$meta_key => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_value'
				),
				'post_date' => array(
					'type'     => 'post_data',
					'function' => '',
					'name'     => 'post_date'
				),
			),
			'where' => array(
				array(
					'key'      => 'post_date',
					'value'    => date( 'Y-m-d', strtotime( 'midnight -7 days', current_time( 'timestamp' ) ) ),
					'operator' => '>'
				),
				array(
					'key'      => 'order_item_meta__product_id.meta_value',
					'value'    => $id,
					'operator' => '='
				)
			),
			'group_by'     => 'YEAR(post_date), MONTH(post_date), DAY(post_date)',
			'query_type'   => 'get_results',
			'filter_range' => false
		) );

		$total = 0;
		foreach ( $data as $d )
			$total += $d->order_item_value;

		if ( $type == 'sales' ) {
			$tooltip = sprintf( __( 'Sold %s worth in the last %d days', 'woocommerce' ), strip_tags( woocommerce_price( $total ) ), $days );
		} else {
			$tooltip = sprintf( _n( 'Sold 1 time in the last %d days', 'Sold %d times in the last %d days', $total, 'woocommerce' ), $total, $days );
		}

		$sparkline_data = array_values( $this->prepare_chart_data( $data, 'post_date', 'order_item_value', $days - 1, strtotime( 'midnight -' . $days . ' days', current_time( 'timestamp' ) ), 'day' ) );

		return '<span class="wc_sparkline tips" data-color="#777" data-tip="' . $tooltip . '" data-barwidth="' . 60*60*16*1000 . '" data-sparkline="' . esc_attr( json_encode( $sparkline_data ) ) . '"></span>';
	}

	/**
	 * Main Sales report
	 */
	public function sales_report() {
		global $woocommerce, $wpdb, $wp_locale;

		$ranges = array(
			'year'         => __( 'Year', 'woocommerce' ),
			'last_3months' => __( 'Last 3 Months', 'woocommerce' ),
			'last_month'   => __( 'Last Month', 'woocommerce' ),
			'month'        => __( 'This Month', 'woocommerce' ),
			'7day'         => __( 'Last 7 Days', 'woocommerce' )
		);

		$chart_colours = array(
			'sales_amount' => '#3498db',
			'average'      => '#9bcced',
			'order_count'  => '#d4d9dc',
			'item_count'   => '#ecf0f1',
		);

		$current_range = ! empty( $_GET['range'] ) ? $_GET['range'] : '7day';

		switch ( $current_range ) {
			case 'custom' :
				$this->start_date = strtotime( sanitize_text_field( $_GET['start_date'] ) );
				$this->end_date   = strtotime( sanitize_text_field( $_GET['end_date'] ) );

				if ( ! $this->end_date )
					$this->end_date = current_time('timestamp');

				$interval = 0;
				$min_date = $this->start_date;
				while ( ( $min_date = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
				    $interval ++;
				}

				// 3 months max for day view
				if ( $interval > 3 )
					$group_by         = 'month';
				else
					$group_by         = 'day';
			break;
			case 'year' :
				$this->start_date = strtotime( 'first day of january', current_time('timestamp') );
				$this->end_date   = strtotime( 'midnight', current_time( 'timestamp' ) );
				$group_by         = 'month';
			break;
			case 'last_month' :
				$this->start_date = strtotime( 'first day of last month', current_time('timestamp') );
				$this->end_date   = strtotime( 'last day of last month', current_time('timestamp') );
				$group_by         = 'day';
			break;
			case 'last_3months' :
				$this->start_date = strtotime( 'first day of ' . date( 'F', strtotime( '-2 months', current_time( 'timestamp' ) ) ) );
				$this->end_date   = strtotime( 'midnight', current_time( 'timestamp' ) );
				$group_by         = 'month';
			break;
			case 'month' :
				$this->start_date = strtotime( 'first day of this month', current_time('timestamp') );
				$this->end_date   = strtotime( 'midnight', current_time( 'timestamp' ) );
				$group_by         = 'day';
			break;
			case '7day' :
			default :
				$this->start_date = strtotime( 'midnight -6 days', current_time( 'timestamp' ) );
				$this->end_date   = strtotime( 'midnight', current_time( 'timestamp' ) );
				$group_by         = 'day';
			break;
		}

		$order_totals = $this->get_order_report_data( array(
			'data' => array(
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_sales'
				),
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				),
				'_order_shipping' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_shipping'
				)
			),
			'filter_range' => true
		) );
		$total_sales 	= $order_totals->total_sales;
		$total_orders 	= absint( $order_totals->total_orders );
		$total_shipping = $order_totals->total_shipping;
		$order_items    = absint( $this->get_order_report_data( array(
			'data' => array(
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_qty'
				)
			),
			'query_type' => 'get_var',
			'filter_range' => true
		) ) );
		?>
		<div id="poststuff" class="woocommerce-reports-wide">
			<div class="postbox">
				<h3 class="stats_range">
					<ul>
						<?php
							foreach ( $ranges as $range => $name )
								echo '<li class="' . ( $current_range == $range ? 'active' : '' ) . '"><a href="' . remove_query_arg( array( 'start_date', 'end_date' ), add_query_arg( 'range', $range ) ) . '">' . $name . '</a></li>';
						?>
						<li class="custom <?php echo $current_range == 'custom' ? 'active' : ''; ?>">
							<?php _e( 'Custom:', 'woocommerce' ); ?>
							<form method="GET">
								<div>
									<input type="text" size="9" placeholder="yyyy-mm-dd" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ); ?>" name="start_date" class="range_datepicker from" />
									<input type="text" size="9" placeholder="yyyy-mm-dd" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ); ?>" name="end_date" class="range_datepicker to" />
									<input type="hidden" name="range" value="custom" />
									<input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
									<input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
									<input type="submit" class="button" value="<?php _e( 'Go', 'woocommerce' ); ?>" />
								</div>
							</form>
						</li>
					</ul>
				</h3>
				<div class="inside split">
					<div class="side">
						<ul class="chart-stats">
							<li class="stats">
								<h4><?php _e( 'Top Sellers', 'woocommerce' ); ?></h4>
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
										'limit'    => 6,
										'query_type'    => 'get_results',
										'filter_range' => true
									) );

									if ( $top_sellers ) {
										foreach ( $top_sellers as $top_seller ) {
											echo '<tr>
												<td class="count">' . $top_seller->order_item_qty . '</td>
												<td class="name">' . get_the_title( $top_seller->product_id ) . '</td>
												<td class="sparkline">' . $this->sales_sparkline( $top_seller->product_id, 7, 'count' ) . '</td>
											</tr>';
										}
									}
									?>
								</table>
							</li>
							<li class="stats">
								<h4><?php _e( 'Top Earners', 'woocommerce' ); ?></h4>
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
										'limit'    => 6,
										'query_type'    => 'get_results',
										'filter_range' => true
									) );

									if ( $top_earners ) {
										foreach ( $top_earners as $top_earner ) {
											echo '<tr>
												<td class="count">' . woocommerce_price( round( $top_earner->order_item_total ) ) . '</td>
												<td class="name">' . get_the_title( $top_earner->product_id ) . '</td>
												<td class="sparkline">' . $this->sales_sparkline( $top_earner->product_id, 7, 'sales' ) . '</td>
											</tr>';
										}
									}
									?>
								</table>
							</li>
						</ul>
					</div>
					<div class="main">
						<div class="chart-container">
							<div class="chart-placeholder main" style="height:568px;"></div>
						</div>
						<ul class="chart-legend">
							<li style="border-color: <?php echo $chart_colours['sales_amount']; ?>">
								<?php printf( __( '%s sales in this period', 'woocommerce' ), '<strong>' . woocommerce_price( $total_sales ) . '</strong>' ); ?>
							</li>
							<li style="border-color: <?php echo $chart_colours['average']; ?>">
								<?php printf( __( '%s average order amount', 'woocommerce' ), '<strong>' . woocommerce_price( $total_orders > 0 ? $total_sales / $total_orders : 0 ) . '</strong>' ); ?>
							</li>
							<li style="border-color: <?php echo $chart_colours['order_count']; ?>">
								<?php printf( __( '%s orders placed', 'woocommerce' ), '<strong>' . $total_orders . '</strong>' ); ?>
							</li>
							<li style="border-color: <?php echo $chart_colours['item_count']; ?>">
								<?php printf( __( '%s items purchased', 'woocommerce' ), '<strong>' . $order_items . '</strong>' ); ?>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php

		// Group by
		switch ( $group_by ) {
			case 'day' :
				$group_by_query = 'YEAR(post_date), MONTH(post_date), DAY(post_date)';
				$interval       = max( 1, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) );
				$barwidth       = 60 * 60 * 24 * 1000;
			break;
			case 'month' :
				$group_by_query = 'YEAR(post_date), MONTH(post_date)';
				$interval = 0;
				$min_date = $this->start_date;
				while ( ( $min_date = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
				    $interval ++;
				}
				$barwidth       = 60 * 60 * 24 * 7 * 4 * 1000;
			break;
		}

		// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
		$orders = $this->get_order_report_data( array(
			'data' => array(
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_sales'
				),
				'_qty' => array(
					'type'            => 'order_item_meta',
					'order_item_type' => 'line_item',
					'function'        => 'SUM',
					'name'            => 'order_item_count'
				),
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
			'group_by'     => $group_by_query,
			'order_by'     => 'post_date ASC',
			'query_type'   => 'get_results',
			'filter_range' => true
		) );

		// Prepare data for report
		$order_counts      = $this->prepare_chart_data( $orders, 'post_date', 'total_orders', $interval, $this->start_date, $group_by );
		$order_item_counts = $this->prepare_chart_data( $orders, 'post_date', 'order_item_count', $interval, $this->start_date, $group_by );
		$order_amounts     = $this->prepare_chart_data( $orders, 'post_date', 'total_sales', $interval, $this->start_date, $group_by );

		// Encode in json format
		$chart_data = json_encode( array(
			'order_counts'      => array_values( $order_counts ),
			'order_item_counts' => array_values( $order_item_counts ),
			'order_amounts'     => array_values( $order_amounts )
		) );
		?>
		<script type="text/javascript">
			jQuery(function(){
				var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

				jQuery.plot(
					jQuery('.chart-placeholder.main'),
					[
						{
							label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
							data: order_data.order_item_counts,
							color: '<?php echo $chart_colours['item_count']; ?>',
							bars: { fillColor: '<?php echo $chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Number of orders', 'woocommerce' ) ) ?>",
							data: order_data.order_counts,
							color: '<?php echo $chart_colours['order_count']; ?>',
							bars: { fillColor: '<?php echo $chart_colours['order_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Average sales amount', 'woocommerce' ) ) ?>",
							data: [ [ <?php echo min( array_keys( $order_amounts ) ); ?>, <?php echo $total_orders > 0 ? $total_sales / $total_orders : 0; ?> ], [ <?php echo max( array_keys( $order_amounts ) ); ?>, <?php echo $total_orders > 0 ? $total_sales / $total_orders : 0; ?> ] ],
							yaxis: 2,
							color: '<?php echo $chart_colours['average']; ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 1, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
							data: order_data.order_amounts,
							yaxis: 2,
							color: '<?php echo $chart_colours['sales_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 4, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
						}
					],
					{
						legend: {
							show: false
						},
				   		series: {
				   			stack: true
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
							timeformat: "<?php if ( $group_by == 'day' ) echo '%d %b'; else echo '%b'; ?>",
							monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
							tickLength: 1,
							minTickSize: [1, "<?php echo $group_by; ?>"],
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
					    		font: {
					    			color: "#aaa"
					    		}
					    	},
					    	{
					    		position: "right",
					    		min: 0,
					    		tickDecimals: 2,
					    		alignTicksWithAxis: 1,
					    		color: 'transparent',
					    		font: {
					    			color: "#aaa"
					    		}
					    	}
					    ],
			 		}
			 	);

			 	jQuery('.chart-placeholder').resize();
			});
		</script>
		<?php
	}
}

new WC_Admin_Reports();




/*
$customer_orders = $this->get_order_report_data( array(
			'data' => array(
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_sales'
				),
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				),
			),
			'where_meta' => array(
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => '0',
					'operator'   => '>'
				)
			)
		) );

		$guest_orders = $this->get_order_report_data( array(
			'data' => array(
				'_order_total' => array(
					'type'     => 'meta',
					'function' => 'SUM',
					'name'     => 'total_sales'
				),
				'ID' => array(
					'type'     => 'post_data',
					'function' => 'COUNT',
					'name'     => 'total_orders'
				),
			),
			'where_meta' => array(
				array(
					'meta_key'   => '_customer_user',
					'meta_value' => '0',
					'operator'   => '='
				)
			)
		) );

// Get order ids and dates in range
		$orders = apply_filters('woocommerce_reports_sales_overview_orders', $wpdb->get_results( "
			SELECT posts.ID, posts.post_date, COUNT( order_items.order_item_id ) as order_item_count FROM {$wpdb->posts} AS posts

			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			LEFT JOIN {$wpdb->prefix}woocommerce_order_items as order_items ON posts.ID = order_items.order_id

			WHERE 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND 	post_date > '" . date('Y-m-d', $this->start_date ) . "'
			AND 	post_date < '" . date('Y-m-d', $this->end_date ) . "'
			GROUP BY posts.ID
			ORDER BY post_date ASC
		" ) );

		if ( $orders ) {
			foreach ( $orders as $order ) {

				$order_total = get_post_meta( $order->ID, '_order_total', true );
				$time = strtotime( date( 'Y-m-d', strtotime( $order->post_date ) ) ) . '000';

				if ( isset( $order_counts[ $time ] ) )
					$order_counts[ $time ]++;
				else
					$order_counts[ $time ] = 1;

				if ( isset( $order_item_counts[ $time ] ) )
					$order_item_counts[ $time ] += $order->order_item_count;
				else
					$order_item_counts[ $time ] = $order->order_item_count;

				if ( isset( $order_amounts[ $time ] ) )
					$order_amounts[ $time ] = $order_amounts[ $time ] + $order_total;
				else
					$order_amounts[ $time ] = floatval( $order_total );
			}
		}

		$order_counts_array = $order_amounts_array = $order_item_counts_array = array();

		foreach ( $order_counts as $key => $count )
			$order_counts_array[] = array( esc_js( $key ), esc_js( $count ) );

		foreach ( $order_item_counts as $key => $count )
			$order_item_counts_array[] = array( esc_js( $key ), esc_js( $count ) );

		foreach ( $order_amounts as $key => $amount )
			$order_amounts_array[] = array( esc_js( $key ), esc_js( $amount ) );

		$chart_data = json_encode( array( 'order_counts' => $order_counts_array, 'order_item_counts' => $order_item_counts_array, 'order_amounts' => $order_amounts_array, 'guest_total_orders' => $guest_orders->total_orders, 'customer_total_orders' => $customer_orders->total_orders ) );

 jQuery.plot(
					jQuery('.chart-placeholder.customers_vs_guests'),
					[
						{
							label: "Customer",
							data: order_data.customer_total_orders,
							color: '#3498db',
						},
						{
							label: "Guest",
							data: order_data.guest_total_orders,
							color: '#2ecc71',
						}
					],
					{
						series: {
					        pie: {
					            show: true,
					            radius: 1,
					            innerRadius: 0.6,
					            label: {
					                show: true
					            }
					        }
					    },
					    legend: {
					        show: false
					    }
			 		}
			 	);
			 	*/





/**
 * Output the product sales chart for single products.
 *
 * @access public
 * @return void
 */
function woocommerce_product_sales() {

	global $wpdb, $woocommerce;

	$chosen_product_ids = ( isset( $_POST['product_ids'] ) ) ? array_map( 'absint', (array) $_POST['product_ids'] ) : '';

	if ( $chosen_product_ids && is_array( $chosen_product_ids ) ) {

		$start_date = date( 'Ym', strtotime( '-12 MONTHS', current_time('timestamp') ) ) . '01';
		$end_date 	= date( 'Ymd', current_time( 'timestamp' ) );

		$max_sales = $max_totals = 0;
		$product_sales = $product_totals = array();

		// Get titles and ID's related to product
		$chosen_product_titles = array();
		$children_ids = array();

		foreach ( $chosen_product_ids as $product_id ) {
			$children = (array) get_posts( 'post_parent=' . $product_id . '&fields=ids&post_status=any&numberposts=-1' );
			$children_ids = $children_ids + $children;
			$chosen_product_titles[] = get_the_title( $product_id );
		}

		// Get order items
		$order_items = apply_filters( 'woocommerce_reports_product_sales_order_items', $wpdb->get_results( "
			SELECT order_item_meta_2.meta_value as product_id, posts.post_date, SUM( order_item_meta.meta_value ) as item_quantity, SUM( order_item_meta_3.meta_value ) as line_total
			FROM {$wpdb->prefix}woocommerce_order_items as order_items

			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_3 ON order_items.order_item_id = order_item_meta_3.order_item_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )

			WHERE 	posts.post_type 	= 'shop_order'
			AND 	order_item_meta_2.meta_value IN ('" . implode( "','", array_merge( $chosen_product_ids, $children_ids ) ) . "')
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND 	order_items.order_item_type = 'line_item'
			AND 	order_item_meta.meta_key = '_qty'
			AND 	order_item_meta_2.meta_key = '_product_id'
			AND 	order_item_meta_3.meta_key = '_line_total'
			GROUP BY order_items.order_id
			ORDER BY posts.post_date ASC
		" ), array_merge( $chosen_product_ids, $children_ids ) );

		$found_products = array();

		if ( $order_items ) {
			foreach ( $order_items as $order_item ) {

				if ( $order_item->line_total == 0 && $order_item->item_quantity == 0 )
					continue;

				// Get date
				$date 	= date( 'Ym', strtotime( $order_item->post_date ) );

				// Set values
				$product_sales[ $date ] 	= isset( $product_sales[ $date ] ) ? $product_sales[ $date ] + $order_item->item_quantity : $order_item->item_quantity;
				$product_totals[ $date ] 	= isset( $product_totals[ $date ] ) ? $product_totals[ $date ] + $order_item->line_total : $order_item->line_total;

				if ( $product_sales[ $date ] > $max_sales )
					$max_sales = $product_sales[ $date ];

				if ( $product_totals[ $date ] > $max_totals )
					$max_totals = $product_totals[ $date ];
			}
		}
		?>
		<h4><?php printf( __( 'Sales for %s:', 'woocommerce' ), implode( ', ', $chosen_product_titles ) ); ?></h4>
		<table class="bar_chart">
			<thead>
				<tr>
					<th><?php _e( 'Month', 'woocommerce' ); ?></th>
					<th colspan="2"><?php _e( 'Sales', 'woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					if ( sizeof( $product_sales ) > 0 ) {
						foreach ( $product_sales as $date => $sales ) {
							$width = ($sales>0) ? (round($sales) / round($max_sales)) * 100 : 0;
							$width2 = ($product_totals[$date]>0) ? (round($product_totals[$date]) / round($max_totals)) * 100 : 0;

							$orders_link = admin_url( 'edit.php?s&post_status=all&post_type=shop_order&action=-1&s=' . urlencode( implode( ' ', $chosen_product_titles ) ) . '&m=' . date( 'Ym', strtotime( $date . '01' ) ) . '&shop_order_status=' . implode( ",", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) );
							$orders_link = apply_filters( 'woocommerce_reports_order_link', $orders_link, $chosen_product_ids, $chosen_product_titles );

							echo '<tr><th><a href="' . esc_url( $orders_link ) . '">' . date_i18n( 'F', strtotime( $date . '01' ) ) . '</a></th>
							<td width="1%"><span>' . esc_html( $sales ) . '</span><span class="alt">' . woocommerce_price( $product_totals[ $date ] ) . '</span></td>
							<td class="bars">
								<span style="width:' . esc_attr( $width ) . '%">&nbsp;</span>
								<span class="alt" style="width:' . esc_attr( $width2 ) . '%">&nbsp;</span>
							</td></tr>';
						}
					} else {
						echo '<tr><td colspan="3">' . __( 'No sales :(', 'woocommerce' ) . '</td></tr>';
					}
				?>
			</tbody>
		</table>
		<?php

	} else {
		?>
		<form method="post" action="">
			<p><select id="product_ids" name="product_ids[]" class="ajax_chosen_select_products" multiple="multiple" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" style="width: 400px;"></select> <input type="submit" style="vertical-align: top;" class="button" value="<?php _e( 'Show', 'woocommerce' ); ?>" /></p>
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
		<?php
	}
}


/**
 * Output the coupons overview stats.
 *
 * @access public
 * @return void
 */
function woocommerce_coupons_overview() {
	global $start_date, $end_date, $woocommerce, $wpdb;

	$start_date = isset( $_POST['start_date'] ) ? $_POST['start_date'] : '';
	$end_date	= isset( $_POST['end_date'] ) ? $_POST['end_date'] : '';

	if ( ! $start_date )
		$start_date = date( 'Ymd', strtotime( date('Ym', current_time( 'timestamp' ) ) . '01' ) );
	if ( ! $end_date )
		$end_date = date( 'Ymd', current_time( 'timestamp' ) );

	$start_date = strtotime( $start_date );
	$end_date = strtotime( $end_date );

	$total_order_count = apply_filters( 'woocommerce_reports_coupons_overview_total_order_count', absint( $wpdb->get_var( "
		SELECT COUNT( DISTINCT posts.ID ) as order_count
		FROM {$wpdb->posts} AS posts
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		WHERE 	term.slug IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND 	post_date > '" . date('Y-m-d', $start_date ) . "'
		AND 	post_date < '" . date('Y-m-d', strtotime('+1 day', $end_date ) ) . "'
	" ) ) );

	$coupon_totals = apply_filters( 'woocommerce_reports_coupons_overview_totals', $wpdb->get_row( "
		SELECT COUNT( DISTINCT posts.ID ) as order_count, SUM( order_item_meta.meta_value ) as total_discount
		FROM {$wpdb->prefix}woocommerce_order_items as order_items
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		WHERE 	term.slug IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND 	order_items.order_item_type = 'coupon'
		AND 	order_item_meta.meta_key = 'discount_amount'
		AND 	post_date > '" . date('Y-m-d', $start_date ) . "'
		AND 	post_date < '" . date('Y-m-d', strtotime('+1 day', $end_date ) ) . "'
	" ) );

	$coupons_by_count = apply_filters( 'woocommerce_reports_coupons_overview_coupons_by_count', $wpdb->get_results( "
		SELECT COUNT( order_items.order_id ) as count, order_items.*
		FROM {$wpdb->prefix}woocommerce_order_items as order_items
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		WHERE 	term.slug IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND 	order_items.order_item_type = 'coupon'
		AND 	order_item_meta.meta_key = 'discount_amount'
		AND 	order_items.order_item_name != ''
		AND 	post_date > '" . date('Y-m-d', $start_date ) . "'
		AND 	post_date < '" . date('Y-m-d', strtotime('+1 day', $end_date ) ) . "'
		GROUP BY order_items.order_item_name
		ORDER BY count DESC
		LIMIT 15
	" ) );

	$coupons_by_amount = apply_filters( 'woocommerce_reports_coupons_overview_coupons_by_count', $wpdb->get_results( "
		SELECT SUM( order_item_meta.meta_value ) as amount, order_items.*
		FROM {$wpdb->prefix}woocommerce_order_items as order_items
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		WHERE 	term.slug IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND 	order_items.order_item_type = 'coupon'
		AND 	order_item_meta.meta_key = 'discount_amount'
		AND 	order_items.order_item_name != ''
		AND 	post_date > '" . date('Y-m-d', $start_date ) . "'
		AND 	post_date < '" . date('Y-m-d', strtotime('+1 day', $end_date ) ) . "'
		GROUP BY order_items.order_item_name
		ORDER BY amount DESC
		LIMIT 15
	" ) );

	?>
	<form method="post" action="">
		<p><label for="from"><?php _e( 'From:', 'woocommerce' ); ?></label> <input type="text" name="start_date" id="from" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $start_date) ); ?>" /> <label for="to"><?php _e( 'To:', 'woocommerce' ); ?></label> <input type="text" name="end_date" id="to" readonly="readonly" value="<?php echo esc_attr( date('Y-m-d', $end_date) ); ?>" /> <input type="submit" class="button" value="<?php _e( 'Show', 'woocommerce' ); ?>" /></p>
	</form>

	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e( 'Total orders containing coupons', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ( $coupon_totals->order_count > 0 ) echo absint( $coupon_totals->order_count ); else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Percent of orders containing coupons', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ( $coupon_totals->order_count > 0 ) echo round( $coupon_totals->order_count / $total_order_count * 100, 2 ) . '%'; else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total coupon discount', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ( $coupon_totals->total_discount > 0 ) echo woocommerce_price( $coupon_totals->total_discount ); else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="woocommerce-reports-left">
				<div class="postbox">
					<h3><span><?php _e( 'Most popular coupons', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<ul class="wc_coupon_list wc_coupon_list_block">
							<?php
								if ( $coupons_by_count ) {
									foreach ( $coupons_by_count as $coupon ) {
										$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $coupon->order_item_name ) );

										$link = $post_id ? admin_url( 'post.php?post=' . $post_id . '&action=edit' ) : admin_url( 'edit.php?s=' . esc_url( $coupon->order_item_name ) . '&post_status=all&post_type=shop_coupon' );

										echo '<li><a href="' . $link . '" class="code"><span><span>' . esc_html( $coupon->order_item_name ). '</span></span></a> - ' . sprintf( _n( 'Used 1 time', 'Used %d times', $coupon->count, 'woocommerce' ), absint( $coupon->count ) ) . '</li>';
									}
								} else {
									echo '<li>' . __( 'No coupons found', 'woocommerce' ) . '</li>';
								}
							?>
						</ul>
					</div>
				</div>
			</div>
			<div class="woocommerce-reports-right">
				<div class="postbox">
					<h3><span><?php _e( 'Greatest discount amount', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<ul class="wc_coupon_list wc_coupon_list_block">
							<?php
								if ( $coupons_by_amount ) {
									foreach ( $coupons_by_amount as $coupon ) {
										$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $coupon->order_item_name ) );

										$link = $post_id ? admin_url( 'post.php?post=' . $post_id . '&action=edit' ) : admin_url( 'edit.php?s=' . esc_url( $coupon->order_item_name ) . '&post_status=all&post_type=shop_coupon' );

										echo '<li><a href="' . $link . '" class="code"><span><span>' . esc_html( $coupon->order_item_name ). '</span></span></a> - ' . sprintf( __( 'Discounted %s', 'woocommerce' ), woocommerce_price( $coupon->amount ) ) . '</li>';
									}
								} else {
									echo '<li>' . __( 'No coupons found', 'woocommerce' ) . '</li>';
								}
							?>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}


/**
 * woocommerce_coupon_discounts function.
 *
 * @access public
 * @return void
 */
function woocommerce_coupon_discounts() {
	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$first_year = $wpdb->get_var( "SELECT post_date FROM $wpdb->posts WHERE post_date != 0 AND post_type='shop_order' ORDER BY post_date ASC LIMIT 1;" );
	$first_year = ( $first_year ) ? date( 'Y', strtotime( $first_year ) ) : date( 'Y' );

	$current_year 	= isset( $_POST['show_year'] ) 	? absint( $_POST['show_year'] ) : date( 'Y', current_time( 'timestamp' ) );
	$start_date 	= strtotime( $current_year . '0101' );

	$order_statuses = implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) );

	$used_coupons = apply_filters( 'woocommerce_reports_coupons_sales_used_coupons', $wpdb->get_col( "
		SELECT order_items.order_item_name
		FROM {$wpdb->prefix}woocommerce_order_items as order_items
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )
		WHERE 	term.slug IN ('{$order_statuses}')
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND 	order_items.order_item_type = 'coupon'
		AND 	order_item_meta.meta_key = 'discount_amount'
		AND 	order_items.order_item_name != ''
		GROUP BY order_items.order_item_name
		ORDER BY order_items.order_item_name ASC
	" ) );
	?>

	<form method="post" action="" class="report_filters">
		<p>
			<label for="show_year"><?php _e( 'Show:', 'woocommerce' ); ?></label>
			<select name="show_year" id="show_year">
				<?php
					for ( $i = $first_year; $i <= date( 'Y' ); $i++ )
						printf( '<option value="%s" %s>%s</option>', $i, selected( $current_year, $i, false ), $i );
				?>
			</select>

			<select multiple="multiple" class="chosen_select" id="show_coupons" name="show_coupons[]" style="width: 300px;">
				<?php
					foreach ( $used_coupons as $coupon )
						echo '<option value="' . $coupon . '" ' . selected( ! empty( $_POST['show_coupons'] ) && in_array( $coupon, $_POST['show_coupons'] ), true ) . '>' . $coupon . '</option>';
				?>
			</select>

			<input type="submit" class="button" value="<?php _e( 'Show', 'woocommerce' ); ?>" />
		</p>
	</form>

	<?php

	if ( ! empty( $_POST['show_coupons'] ) && count( $_POST['show_coupons'] ) > 0 ) {

		$coupons = $_POST['show_coupons'];

		$coupon_sales = $monthly_totals = array();

		foreach( $coupons as $coupon ) {

			$coupon_amounts = apply_filters( 'woocommerce_reports_coupon_sales_order_totals', $wpdb->get_results( $wpdb->prepare( "
				SELECT order_items.order_item_name, date_format(posts.post_date, '%%Y%%m') as month, SUM( order_item_meta.meta_value ) as discount_total
				FROM {$wpdb->prefix}woocommerce_order_items as order_items
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
				LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
				LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
				LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
				LEFT JOIN {$wpdb->terms} AS term USING( term_id )
				WHERE 	term.slug IN ('{$order_statuses}')
				AND 	posts.post_status 	= 'publish'
				AND 	tax.taxonomy		= 'shop_order_status'
				AND 	order_items.order_item_type = 'coupon'
				AND 	order_item_meta.meta_key = 'discount_amount'
				AND		'{$current_year}'	= date_format(posts.post_date,'%%Y')
				AND 	order_items.order_item_name = %s
				GROUP BY month
			", $coupon ) ), $order_statuses, $current_year, $coupon );

			foreach( $coupon_amounts as $sales ) {
				$month = $sales->month;
				$coupon_sales[ $coupon ][ $month ] = $sales->discount_total;
			}
		}
		?>
		<div class="woocommerce-wide-reports-wrap">
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e( 'Coupon', 'woocommerce' ); ?></th>
						<?php
							$column_count = 0;
							for ( $count = 0; $count < 12; $count++ ) :
								if ( $count >= date ( 'm' ) && $current_year == date( 'Y' ) )
									continue;
								$month = date( 'Ym', strtotime( date( 'Ym', strtotime( '+ '. $count . ' MONTH', $start_date ) ) . '01' ) );

								// set elements before += them below
								$monthly_totals[$month] = 0;

								$column_count++;
								?>
								<th><?php echo date( 'F', strtotime( '2012-' . ( $count + 1 ) . '-01' ) ); ?></th>
						<?php endfor; ?>
						<th><strong><?php _e( 'Total', 'woocommerce' ); ?></strong></th>
					</tr>
				</thead>

				<tbody><?php

					// save data for chart while outputting
					$chart_data = $coupon_totals = array();

					foreach( $coupon_sales as $coupon_code => $sales ) {

						echo '<tr><th>' . esc_html( $coupon_code ) . '</th>';

						for ( $count = 0; $count < 12; $count ++ ) {

							if ( $count >= date ( 'm' ) && $current_year == date( 'Y' ) )
									continue;

							$month = date( 'Ym', strtotime( date( 'Ym', strtotime( '+ '. $count . ' MONTH', $start_date ) ) . '01' ) );

							$amount = isset( $sales[$month] ) ? $sales[$month] : 0;
							echo '<td>' . woocommerce_price( $amount ) . '</td>';

							$monthly_totals[$month] += $amount;

							$chart_data[$coupon_code][] = array( strtotime( date( 'Ymd', strtotime( $month . '01' ) ) ) . '000', $amount );

						}

						echo '<td><strong>' . woocommerce_price( array_sum( $sales ) ) . '</strong></td>';

						// total sales across all months
						$coupon_totals[$coupon_code] = array_sum( $sales );

						echo '</tr>';

					}

					if ( $coupon_totals ) {

						$top_coupon_name = current( array_keys( $coupon_totals, max( $coupon_totals ) ) );
						$top_coupon_sales = $coupon_totals[$top_coupon_name];

						$worst_coupon_name = current( array_keys( $coupon_totals, min( $coupon_totals ) ) );
						$worst_coupon_sales = $coupon_totals[$worst_coupon_name];

						$median_coupon_sales = array_values( $coupon_totals );
						sort($median_coupon_sales);

					} else {
						$top_coupon_name = $top_coupon_sales = $worst_coupon_name = $worst_coupon_sales = $median_coupon_sales = '';
					}

					echo '<tr><th><strong>' . __( 'Total', 'woocommerce' ) . '</strong></th>';

					foreach( $monthly_totals as $month => $totals )
						echo '<td><strong>' . woocommerce_price( $totals ) . '</strong></td>';

					echo '<td><strong>' .  woocommerce_price( array_sum( $monthly_totals ) ) . '</strong></td></tr>';

				?></tbody>
			</table>
		</div>

		<?php if ( sizeof( $coupon_totals ) > 1 ) : ?>
		<div id="poststuff" class="woocommerce-reports-wrap">
			<div class="woocommerce-reports-sidebar">
				<div class="postbox">
					<h3><span><?php _e( 'Top coupon', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
							echo $top_coupon_name . ' (' . woocommerce_price( $top_coupon_sales ) . ')';
						?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e( 'Worst coupon', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
							echo $worst_coupon_name . ' (' . woocommerce_price( $worst_coupon_sales ) . ')';
						?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e( 'Discount average', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
								echo woocommerce_price( array_sum( $coupon_totals ) / count( $coupon_totals ) );
						?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e( 'Discount median', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
							if ( count( $median_coupon_sales ) == 2 )
								echo __( 'N/A', 'woocommerce' );
							elseif ( count( $median_coupon_sales ) % 2 )
								echo woocommerce_price(
									(
										$median_coupon_sales[ floor( count( $median_coupon_sales ) / 2 ) ] + $median_coupon_sales[ ceil( count( $median_coupon_sales ) / 2 ) ]
									) / 2
								);
							else

								echo woocommerce_price( $median_coupon_sales[ count( $median_coupon_sales ) / 2 ] );
						?></p>
					</div>
				</div>
			</div>
			<div class="woocommerce-reports-main">
				<div class="postbox">
					<h3><span><?php _e( 'Monthly discounts by coupon', 'woocommerce' ); ?></span></h3>
					<div class="inside chart">
						<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
						<div id="chart-legend"></div>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(function(){

				<?php
					// Variables
					foreach ( $chart_data as $name => $data ) {
						$varname = 'coupon_' . str_replace( '-', '_', sanitize_title( $name ) ) . '_data';
						echo 'var ' . $varname . ' = jQuery.parseJSON( \'' . json_encode( $data ) . '\' );';
					}
				?>

				var placeholder = jQuery("#placeholder");

				var plot = jQuery.plot(placeholder, [
					<?php
					$labels = array();

					foreach ( $chart_data as $name => $data ) {
						$labels[] = '{ label: "' . esc_js( $name ) . '", data: ' . 'coupon_' . str_replace( '-', '_', sanitize_title( $name ) ) . '_data }';
					}

					echo implode( ',', $labels );
					?>
				], {
					legend: {
						container: jQuery('#chart-legend'),
						noColumns: 2
					},
					series: {
						lines: { show: true, fill: true },
						points: { show: true, align: "left" }
					},
					grid: {
						show: true,
						aboveData: false,
						color: '#aaa',
						backgroundColor: '#fff',
						borderWidth: 2,
						borderColor: '#aaa',
						clickable: false,
						hoverable: true
					},
					xaxis: {
						mode: "time",
						timeformat: "%b %y",
						monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
						tickLength: 1,
						minTickSize: [1, "month"]
					},
					yaxes: [ { min: 0, tickDecimals: 2 } ]
			 	});

			 	placeholder.resize();
			});
		</script>
		<?php endif; ?>
		<?php
	} // end POST check
	?>
	<script type="text/javascript">
		jQuery(function(){
			jQuery("select.chosen_select").chosen();
		});
	</script>
	<?php
}


/**
 * Output the customer overview stats.
 *
 * @access public
 * @return void
 */
function woocommerce_customer_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$total_customers = 0;
	$total_customer_sales = 0;
	$total_guest_sales = 0;
	$total_customer_orders = 0;
	$total_guest_orders = 0;

	$users_query = new WP_User_Query( array(
		'fields' => array('user_registered'),
		'role' => 'customer'
		) );
	$customers = $users_query->get_results();
	$total_customers = (int) sizeof($customers);

	$customer_orders = apply_filters( 'woocommerce_reports_customer_overview_customer_orders', $wpdb->get_row( "
		SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_total'
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND		posts.ID			IN (
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE 	meta_key 		= '_customer_user'
			AND		meta_value		> 0
		)
	" ) );

	$total_customer_sales	= $customer_orders->total_sales;
	$total_customer_orders	= absint( $customer_orders->total_orders );

	$guest_orders = apply_filters( 'woocommerce_reports_customer_overview_guest_orders', $wpdb->get_row( "
		SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts

		LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	meta.meta_key 		= '_order_total'
		AND 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND		posts.ID			IN (
			SELECT post_id FROM {$wpdb->postmeta}
			WHERE 	meta_key 		= '_customer_user'
			AND		meta_value		= 0
		)
	" ) );

	$total_guest_sales	= $guest_orders->total_sales;
	$total_guest_orders	= absint( $guest_orders->total_orders );
	?>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e( 'Total customers', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customers>0) echo $total_customers; else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total customer sales', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_sales>0) echo woocommerce_price($total_customer_sales); else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total guest sales', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_guest_sales>0) echo woocommerce_price($total_guest_sales); else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total customer orders', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_orders>0) echo $total_customer_orders; else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total guest orders', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_guest_orders>0) echo $total_guest_orders; else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Average orders per customer', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php if ($total_customer_orders>0 && $total_customers>0) echo number_format($total_customer_orders/$total_customers, 2); else _e( 'n/a', 'woocommerce' ); ?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<div class="postbox">
				<h3><span><?php _e( 'Signups per day', 'woocommerce' ); ?></span></h3>
				<div class="inside chart">
					<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
					<div id="chart-legend"></div>
				</div>
			</div>
		</div>
	</div>
	<?php

	$start_date = strtotime('-30 days', current_time('timestamp'));
	$end_date = current_time('timestamp');
	$signups = array();

	// Blank date ranges to begin
	$count = 0;
	$days = ($end_date - $start_date) / (60 * 60 * 24);
	if ($days==0) $days = 1;

	while ($count < $days) :
		$time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $start_date))).'000';

		$signups[ $time ] = 0;

		$count++;
	endwhile;

	foreach ($customers as $customer) :
		if (strtotime($customer->user_registered) > $start_date) :
			$time = strtotime(date('Ymd', strtotime($customer->user_registered))).'000';

			if (isset($signups[ $time ])) :
				$signups[ $time ]++;
			else :
				$signups[ $time ] = 1;
			endif;
		endif;
	endforeach;

	$signups_array = array();
	foreach ($signups as $key => $count) :
		$signups_array[] = array( esc_js( $key ), esc_js( $count ) );
	endforeach;

	$chart_data = json_encode($signups_array);
	?>
	<script type="text/javascript">
		jQuery(function(){
			var d = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

			for (var i = 0; i < d.length; ++i) d[i][0] += 60 * 60 * 1000;

			var placeholder = jQuery("#placeholder");

			var plot = jQuery.plot(placeholder, [ { data: d } ], {
				legend: {
					container: jQuery('#chart-legend'),
					noColumns: 2
				},
				series: {
					bars: {
						barWidth: 60 * 60 * 24 * 1000,
						align: "center",
						show: true
					}
				},
				grid: {
					show: true,
					aboveData: false,
					color: '#aaa',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#aaa',
					clickable: false,
					hoverable: true,
					markings: weekendAreas
				},
				xaxis: {
					mode: "time",
					timeformat: "%d %b",
					monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
					tickLength: 1,
					minTickSize: [1, "day"]
				},
				yaxes: [ { position: "right", min: 0, tickSize: 1, tickDecimals: 0 } ],
		   		colors: ["#8a4b75"]
		 	});

		 	placeholder.resize();

			<?php woocommerce_weekend_area_js(); ?>
		});
	</script>
	<?php
}


/**
 * Output the stock overview stats.
 *
 * @access public
 * @return void
 */
function woocommerce_stock_overview() {

	global $start_date, $end_date, $woocommerce, $wpdb;

	// Low/No stock lists
	$lowstockamount = get_option('woocommerce_notify_low_stock_amount');
	if (!is_numeric($lowstockamount)) $lowstockamount = 1;

	$nostockamount = get_option('woocommerce_notify_no_stock_amount');
	if (!is_numeric($nostockamount)) $nostockamount = 0;

	// Get low in stock simple/downloadable/virtual products. Grouped don't have stock. Variations need a separate query.
	$args = array(
		'post_type'			=> 'product',
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
		'meta_query' => array(
			array(
				'key' 		=> '_manage_stock',
				'value' 	=> 'yes'
			),
			array(
				'key' 		=> '_stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			)
		),
		'tax_query' => array(
			array(
				'taxonomy' 	=> 'product_type',
				'field' 	=> 'name',
				'terms' 	=> array('simple'),
				'operator' 	=> 'IN'
			)
		),
		'fields' => 'id=>parent'
	);

	$low_stock_products = (array) get_posts($args);

	// Get low stock product variations
	$args = array(
		'post_type'			=> 'product_variation',
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
		'meta_query' => array(
			array(
				'key' 		=> '_stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			),
			array(
				'key' 		=> '_stock',
				'value' 	=> array( '', false, null ),
				'compare' 	=> 'NOT IN'
			)
		),
		'fields' => 'id=>parent'
	);

	$low_stock_variations = (array) get_posts($args);

	// Get low stock variable products (where stock is set for the parent)
	$args = array(
		'post_type'			=> array('product'),
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' 		=> '_manage_stock',
				'value' 	=> 'yes'
			),
			array(
				'key' 		=> '_stock',
				'value' 	=> $lowstockamount,
				'compare' 	=> '<=',
				'type' 		=> 'NUMERIC'
			)
		),
		'tax_query' => array(
			array(
				'taxonomy' 	=> 'product_type',
				'field' 	=> 'name',
				'terms' 	=> array('variable'),
				'operator' 	=> 'IN'
			)
		),
		'fields' => 'id=>parent'
	);

	$low_stock_variable_products = (array) get_posts($args);

	// Get products marked out of stock
	$args = array(
		'post_type'			=> array( 'product' ),
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
		'meta_query' => array(
			'relation' => 'AND',
			array(
				'key' 		=> '_stock_status',
				'value' 	=> 'outofstock'
			)
		),
		'fields' => 'id=>parent'
	);

	$out_of_stock_status_products = (array) get_posts($args);

	// Merge results
	$low_in_stock = apply_filters( 'woocommerce_reports_stock_overview_products', $low_stock_products + $low_stock_variations + $low_stock_variable_products + $out_of_stock_status_products );
	?>
	<div id="poststuff" class="woocommerce-reports-wrap halved">
		<div class="woocommerce-reports-left">
			<div class="postbox">
				<h3><span><?php _e( 'Low stock', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<?php
					if ( $low_in_stock ) {
						echo '<ul class="stock_list">';
						foreach ( $low_in_stock as $product_id => $parent ) {

							$stock 	= (int) get_post_meta( $product_id, '_stock', true );
							$sku	= get_post_meta( $product_id, '_sku', true );

							if ( $stock <= $nostockamount || in_array( $product_id, array_keys( $out_of_stock_status_products ) ) )
								continue;

							$title = esc_html__( get_the_title( $product_id ) );

							if ( $sku )
								$title .= ' (' . __( 'SKU', 'woocommerce' ) . ': ' . esc_html( $sku ) . ')';

							if ( get_post_type( $product_id ) == 'product' )
								$product_url = admin_url( 'post.php?post=' . $product_id . '&action=edit' );
							else
								$product_url = admin_url( 'post.php?post=' . $parent . '&action=edit' );

							printf( '<li><a href="%s"><small>' .  _n('%d in stock', '%d in stock', $stock, 'woocommerce') . '</small> %s</a></li>', $product_url, $stock, $title );

						}
						echo '</ul>';
					} else {
						echo '<p>'.__( 'No products are low in stock.', 'woocommerce' ).'</p>';
					}
					?>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-right">
			<div class="postbox">
				<h3><span><?php _e( 'Out of stock', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<?php
					if ( $low_in_stock ) {
						echo '<ul class="stock_list">';
						foreach ( $low_in_stock as $product_id => $parent ) {

							$stock 	= get_post_meta( $product_id, '_stock', true );
							$sku	= get_post_meta( $product_id, '_sku', true );

							if ( $stock > $nostockamount && ! in_array( $product_id, array_keys( $out_of_stock_status_products ) ) )
								continue;

							$title = esc_html__( get_the_title( $product_id ) );

							if ( $sku )
								$title .= ' (' . __( 'SKU', 'woocommerce' ) . ': ' . esc_html( $sku ) . ')';

							if ( get_post_type( $product_id ) == 'product' )
								$product_url = admin_url( 'post.php?post=' . $product_id . '&action=edit' );
							else
								$product_url = admin_url( 'post.php?post=' . $parent . '&action=edit' );

							if ( $stock == '' )
								printf( '<li><a href="%s"><small>' .  __('Marked out of stock', 'woocommerce') . '</small> %s</a></li>', $product_url, $title );
							else
								printf( '<li><a href="%s"><small>' .  _n('%d in stock', '%d in stock', $stock, 'woocommerce') . '</small> %s</a></li>', $product_url, $stock, $title );

						}
						echo '</ul>';
					} else {
						echo '<p>'.__( 'No products are out in stock.', 'woocommerce' ).'</p>';
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}


/**
 * Output the monthly tax stats.
 *
 * @access public
 * @return void
 */
function woocommerce_monthly_taxes() {
	global $start_date, $end_date, $woocommerce, $wpdb;

	$first_year = $wpdb->get_var( "SELECT post_date FROM $wpdb->posts WHERE post_date != 0 ORDER BY post_date ASC LIMIT 1;" );

	if ( $first_year )
		$first_year = date( 'Y', strtotime( $first_year ) );
	else
		$first_year = date( 'Y' );

	$current_year 	= isset( $_POST['show_year'] ) 	? $_POST['show_year'] 	: date( 'Y', current_time( 'timestamp' ) );
	$start_date 	= strtotime( $current_year . '0101' );

	$total_tax = $total_sales_tax = $total_shipping_tax = $count = 0;
	$taxes = $tax_rows = $tax_row_labels = array();

	for ( $count = 0; $count < 12; $count++ ) {

		$time = strtotime( date('Ym', strtotime( '+ ' . $count . ' MONTH', $start_date ) ) . '01' );

		if ( $time > current_time( 'timestamp' ) )
			continue;

		$month = date( 'Ym', strtotime( date( 'Ym', strtotime( '+ ' . $count . ' MONTH', $start_date ) ) . '01' ) );

		$gross = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_total'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s					= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$shipping = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_shipping'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s		 			= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$order_tax = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_tax'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s		 			= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$shipping_tax = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( meta.meta_value ) AS order_tax
			FROM {$wpdb->posts} AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
			LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN {$wpdb->terms} AS term USING( term_id )
			WHERE 	meta.meta_key 		= '_order_shipping_tax'
			AND 	posts.post_type 	= 'shop_order'
			AND 	posts.post_status 	= 'publish'
			AND 	tax.taxonomy		= 'shop_order_status'
			AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND		%s		 			= date_format(posts.post_date,'%%Y%%m')
		", $month ) );

		$tax_rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT
				order_items.order_item_name as name,
				SUM( order_item_meta.meta_value ) as tax_amount,
				SUM( order_item_meta_2.meta_value ) as shipping_tax_amount,
				SUM( order_item_meta.meta_value + order_item_meta_2.meta_value ) as total_tax_amount

			FROM 		{$wpdb->prefix}woocommerce_order_items as order_items

			LEFT JOIN 	{$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN 	{$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id

			LEFT JOIN 	{$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			LEFT JOIN 	{$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
			LEFT JOIN 	{$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
			LEFT JOIN 	{$wpdb->terms} AS term USING( term_id )

			WHERE 		order_items.order_item_type = 'tax'
			AND 		posts.post_type 	= 'shop_order'
			AND 		posts.post_status 	= 'publish'
			AND 		tax.taxonomy		= 'shop_order_status'
			AND			term.slug IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
			AND			%s = date_format( posts.post_date,'%%Y%%m' )
			AND 		order_item_meta.meta_key = 'tax_amount'
			AND 		order_item_meta_2.meta_key = 'shipping_tax_amount'

			GROUP BY 	order_items.order_item_name
		", $month ) );

		if ( $tax_rows ) {
			foreach ( $tax_rows as $tax_row ) {
				if ( $tax_row->total_tax_amount > 0 )
					$tax_row_labels[] = $tax_row->name;
			}
		}

		$taxes[ date( 'M', strtotime( $month . '01' ) ) ] = array(
			'gross'			=> $gross,
			'shipping'		=> $shipping,
			'order_tax' 	=> $order_tax,
			'shipping_tax' 	=> $shipping_tax,
			'total_tax' 	=> $shipping_tax + $order_tax,
			'tax_rows'		=> $tax_rows
		);

		$total_sales_tax += $order_tax;
		$total_shipping_tax += $shipping_tax;
	}
	$total_tax = $total_sales_tax + $total_shipping_tax;
	?>
	<form method="post" action="">
		<p><label for="show_year"><?php _e( 'Year:', 'woocommerce' ); ?></label>
		<select name="show_year" id="show_year">
			<?php
				for ( $i = $first_year; $i <= date('Y'); $i++ )
					printf( '<option value="%s" %s>%s</option>', $i, selected( $current_year, $i, false ), $i );
			?>
		</select> <input type="submit" class="button" value="<?php _e( 'Show', 'woocommerce' ); ?>" /></p>
	</form>
	<div id="poststuff" class="woocommerce-reports-wrap">
		<div class="woocommerce-reports-sidebar">
			<div class="postbox">
				<h3><span><?php _e( 'Total taxes for year', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php
						if ( $total_tax > 0 )
							echo woocommerce_price( $total_tax );
						else
							_e( 'n/a', 'woocommerce' );
					?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total product taxes for year', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php
						if ( $total_sales_tax > 0 )
							echo woocommerce_price( $total_sales_tax );
						else
							_e( 'n/a', 'woocommerce' );
					?></p>
				</div>
			</div>
			<div class="postbox">
				<h3><span><?php _e( 'Total shipping tax for year', 'woocommerce' ); ?></span></h3>
				<div class="inside">
					<p class="stat"><?php
						if ( $total_shipping_tax > 0 )
							echo woocommerce_price( $total_shipping_tax );
						else
							_e( 'n/a', 'woocommerce' );
					?></p>
				</div>
			</div>
		</div>
		<div class="woocommerce-reports-main">
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e( 'Month', 'woocommerce' ); ?></th>
						<th class="total_row"><?php _e( 'Total Sales', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Order Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Shipping', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Shipping Total' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Product Taxes', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Cart Tax' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Shipping Taxes', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Shipping Tax' field within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Total Taxes', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("This is the sum of the 'Cart Tax' and 'Shipping Tax' fields within your orders.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<th class="total_row"><?php _e( 'Net profit', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e("Total sales minus shipping and tax.", 'woocommerce'); ?>" href="#">[?]</a></th>
						<?php
							$tax_row_labels = array_filter( array_unique( $tax_row_labels ) );
							foreach ( $tax_row_labels as $label )
								echo '<th class="tax_row">' . $label . '</th>';
						?>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<?php
							$total = array();

							foreach ( $taxes as $month => $tax ) {
								$total['gross'] = isset( $total['gross'] ) ? $total['gross'] + $tax['gross'] : $tax['gross'];
								$total['shipping'] = isset( $total['shipping'] ) ? $total['shipping'] + $tax['shipping'] : $tax['shipping'];
								$total['order_tax'] = isset( $total['order_tax'] ) ? $total['order_tax'] + $tax['order_tax'] : $tax['order_tax'];
								$total['shipping_tax'] = isset( $total['shipping_tax'] ) ? $total['shipping_tax'] + $tax['shipping_tax'] : $tax['shipping_tax'];
								$total['total_tax'] = isset( $total['total_tax'] ) ? $total['total_tax'] + $tax['total_tax'] : $tax['total_tax'];

								foreach ( $tax_row_labels as $label )
									foreach ( $tax['tax_rows'] as $tax_row )
										if ( $tax_row->name == $label ) {
											$total['tax_rows'][ $label ] = isset( $total['tax_rows'][ $label ] ) ? $total['tax_rows'][ $label ] + $tax_row->total_tax_amount : $tax_row->total_tax_amount;
										}

							}

							echo '
								<td>' . __( 'Total', 'woocommerce' ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['gross'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['shipping'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['order_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['shipping_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['total_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $total['gross'] - $total['shipping'] - $total['total_tax'] ) . '</td>';

							foreach ( $tax_row_labels as $label )
								if ( isset( $total['tax_rows'][ $label ] ) )
									echo '<td class="tax_row">' . woocommerce_price( $total['tax_rows'][ $label ] ) . '</td>';
								else
									echo '<td class="tax_row">' .  woocommerce_price( 0 ) . '</td>';
						?>
					</tr>
					<tr>
						<th colspan="<?php echo 7 + sizeof( $tax_row_labels ); ?>"><button class="button toggle_tax_rows"><?php _e( 'Toggle tax rows', 'woocommerce' ); ?></button></th>
					</tr>
				</tfoot>
				<tbody>
					<?php
						foreach ( $taxes as $month => $tax ) {
							$alt = ( isset( $alt ) && $alt == 'alt' ) ? '' : 'alt';
							echo '<tr class="' . $alt . '">
								<td>' . $month . '</td>
								<td class="total_row">' . woocommerce_price( $tax['gross'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['shipping'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['order_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['shipping_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['total_tax'] ) . '</td>
								<td class="total_row">' . woocommerce_price( $tax['gross'] - $tax['shipping'] - $tax['total_tax'] ) . '</td>';



							foreach ( $tax_row_labels as $label ) {

								$row_total = 0;

								foreach ( $tax['tax_rows'] as $tax_row ) {
									if ( $tax_row->name == $label ) {
										$row_total = $tax_row->total_tax_amount;
									}
								}

								echo '<td class="tax_row">' . woocommerce_price( $row_total ) . '</td>';
							}

							echo '</tr>';
						}
					?>
				</tbody>
			</table>
			<script type="text/javascript">
				jQuery('.toggle_tax_rows').click(function(){
					jQuery('.tax_row').toggle();
					jQuery('.total_row').toggle();
				});
				jQuery('.tax_row').hide();
			</script>
		</div>
	</div>
	<?php
}


/**
 * woocommerce_category_sales function.
 *
 * @access public
 * @return void
 */
function woocommerce_category_sales() {
	global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

	$first_year = $wpdb->get_var( "SELECT post_date FROM $wpdb->posts WHERE post_date != 0 ORDER BY post_date ASC LIMIT 1;" );
	$first_year = ( $first_year ) ? date( 'Y', strtotime( $first_year ) ) : date( 'Y' );

	$current_year 	= isset( $_POST['show_year'] ) ? $_POST['show_year'] : date( 'Y', current_time( 'timestamp' ) );

	$categories = get_terms( 'product_cat', array( 'orderby' => 'name' ) );
	?>
	<form method="post" action="" class="report_filters">
		<p>
			<label for="show_year"><?php _e( 'Show:', 'woocommerce' ); ?></label>
			<select name="show_year" id="show_year">
				<?php
					for ( $i = $first_year; $i <= date( 'Y' ); $i++ )
						printf( '<option value="%s" %s>%s</option>', $i, selected( $current_year, $i, false ), $i );
				?>
			</select>

			<select multiple="multiple" class="chosen_select" id="show_categories" name="show_categories[]" style="width: 300px;">
				<?php
					$r = array();
					$r['pad_counts'] 	= 1;
					$r['hierarchal'] 	= 1;
					$r['hide_empty'] 	= 1;
					$r['value']			= 'id';
					$r['selected'] 		= isset( $_POST['show_categories'] ) ? $_POST['show_categories'] : '';

					include_once( $woocommerce->plugin_path() . '/includes/walkers/class-product-cat-dropdown-walker.php' );

					echo woocommerce_walk_category_dropdown_tree( $categories, 0, $r );
				?>
			</select>

			<input type="submit" class="button" value="<?php _e( 'Show', 'woocommerce' ); ?>" />
		</p>
	</form>
	<?php

	$item_sales = array();

	// Get order items
	$order_items = apply_filters( 'woocommerce_reports_category_sales_order_items', $wpdb->get_results( $wpdb->prepare( "
		SELECT order_item_meta_2.meta_value as product_id, posts.post_date, SUM( order_item_meta.meta_value ) as line_total
		FROM {$wpdb->prefix}woocommerce_order_items as order_items

		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
		LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID = rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	posts.post_type 	= 'shop_order'
		AND 	posts.post_status 	= 'publish'
		AND 	tax.taxonomy		= 'shop_order_status'
		AND		term.slug			IN ('" . implode( "','", apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "')
		AND		date_format(posts.post_date,'%%Y') = %s
		AND 	order_items.order_item_type = 'line_item'
		AND 	order_item_meta.meta_key = '_line_total'
		AND 	order_item_meta_2.meta_key = '_product_id'
		GROUP BY order_items.order_item_id
		ORDER BY posts.post_date ASC
	", $current_year ) ) );

	if ( $order_items ) {
		foreach ( $order_items as $order_item ) {

			$month = date( 'm', strtotime( $order_item->post_date ) ) - 1;

			$item_sales[ $month ][ $order_item->product_id ] = isset( $item_sales[ $month ][ $order_item->product_id ] ) ? $item_sales[ $month ][ $order_item->product_id ] + $order_item->line_total : $order_item->line_total;
		}
	}

	if ( ! empty( $_POST['show_categories'] ) && sizeof( $_POST['show_categories'] ) > 0 ) {

		$show_categories = $include_categories = array_map( 'absint', $_POST['show_categories'] );

		foreach( $show_categories as $cat )
			$include_categories = array_merge( $include_categories, get_term_children( $cat, 'product_cat' ) );

		$categories = get_terms( 'product_cat', array( 'include' => array_unique( $include_categories ) ) );
		?>
		<div class="woocommerce-wide-reports-wrap">
			<table class="widefat">
				<thead>
					<tr>
						<th><?php _e( 'Category', 'woocommerce' ); ?></th>
						<?php
							$column_count = 0;
							for ( $count = 0; $count < 12; $count++ ) :
								if ( $count >= date ( 'm' ) && $current_year == date( 'Y' ) )
									continue;
								$column_count++;
								?>
								<th><?php echo date( 'F', strtotime( '2012-' . ( $count + 1 ) . '-01' ) ); ?></th>
						<?php endfor; ?>
						<th><strong><?php _e( 'Total', 'woocommerce' ); ?></strong></th>
					</tr>
				</thead>
				<tbody><?php
					// While outputting, lets store them for the chart
					$chart_data = $month_totals = $category_totals = array();
					$top_cat = $bottom_cat = $top_cat_name = $bottom_cat_name = '';

					for ( $count = 0; $count < 12; $count++ )
						if ( $count >= date( 'm' ) && $current_year == date( 'Y' ) )
							break;
						else
							$month_totals[ $count ] = 0;

					foreach ( $categories as $category ) {

						$cat_total = 0;
						$category_chart_data = $term_ids = array();

						$term_ids 		= get_term_children( $category->term_id, 'product_cat' );
						$term_ids[] 	= $category->term_id;
						$product_ids 	= get_objects_in_term( $term_ids, 'product_cat' );

						if ( $category->parent > 0 )
							$prepend = '&mdash; ';
						else
							$prepend = '';

						$category_sales_html = '<tr><th>' . $prepend . $category->name . '</th>';

						for ( $count = 0; $count < 12; $count++ ) {

							if ( $count >= date( 'm' ) && $current_year == date( 'Y' ) )
								continue;

							if ( ! empty( $item_sales[ $count ] ) ) {
								$matches = array_intersect_key( $item_sales[ $count ], array_flip( $product_ids ) );
								$total = array_sum( $matches );
								$cat_total += $total;
							} else {
								$total = 0;
							}

							if ( sizeof( array_intersect( $include_categories, get_ancestors( $category->term_id, 'product_cat' ) ) ) == 0 )
								$month_totals[ $count ] += $total;

							$category_sales_html .= '<td>' . woocommerce_price( $total ) . '</td>';

							$category_chart_data[] = array( strtotime( date( 'Ymd', strtotime( '2012-' . ( $count + 1 ) . '-01' ) ) ) . '000', $total );
						}

						if ( $cat_total == 0 )
							continue;

						$category_totals[] = $cat_total;

						$category_sales_html .= '<td><strong>' . woocommerce_price( $cat_total ) . '</strong></td>';

						$category_sales_html .= '</tr>';

						echo $category_sales_html;

						$chart_data[ $category->name ] = $category_chart_data;

						if ( $cat_total > $top_cat ) {
							$top_cat = $cat_total;
							$top_cat_name = $category->name;
						}

						if ( $cat_total < $bottom_cat || $bottom_cat === '' ) {
							$bottom_cat = $cat_total;
							$bottom_cat_name = $category->name;
						}

					}

					sort( $category_totals );

					echo '<tr><th><strong>' . __( 'Total', 'woocommerce' ) . '</strong></th>';
					for ( $count = 0; $count < 12; $count++ )
						if ( $count >= date( 'm' ) && $current_year == date( 'Y' ) )
							break;
						else
							echo '<td><strong>' . woocommerce_price( $month_totals[ $count ] ) . '</strong></td>';
					echo '<td><strong>' .  woocommerce_price( array_sum( $month_totals ) ) . '</strong></td></tr>';

				?></tbody>
			</table>
		</div>

		<div id="poststuff" class="woocommerce-reports-wrap">
			<div class="woocommerce-reports-sidebar">
				<div class="postbox">
					<h3><span><?php _e( 'Top category', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
							echo $top_cat_name . ' (' . woocommerce_price( $top_cat ) . ')';
						?></p>
					</div>
				</div>
				<?php if ( sizeof( $category_totals ) > 1 ) : ?>
				<div class="postbox">
					<h3><span><?php _e( 'Worst category', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
							echo $bottom_cat_name . ' (' . woocommerce_price( $bottom_cat ) . ')';
						?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e( 'Category sales average', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
							if ( sizeof( $category_totals ) > 0 )
								echo woocommerce_price( array_sum( $category_totals ) / sizeof( $category_totals ) );
							else
								echo __( 'N/A', 'woocommerce' );
						?></p>
					</div>
				</div>
				<div class="postbox">
					<h3><span><?php _e( 'Category sales median', 'woocommerce' ); ?></span></h3>
					<div class="inside">
						<p class="stat"><?php
							if ( sizeof( $category_totals ) == 0 )
								echo __( 'N/A', 'woocommerce' );
							elseif ( sizeof( $category_totals ) % 2 )
								echo woocommerce_price(
									(
										$category_totals[ floor( sizeof( $category_totals ) / 2 ) ] + $category_totals[ ceil( sizeof( $category_totals ) / 2 ) ]
									) / 2
								);
							else
								echo woocommerce_price( $category_totals[ sizeof( $category_totals ) / 2 ] );
						?></p>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<div class="woocommerce-reports-main">
				<div class="postbox">
					<h3><span><?php _e( 'Monthly sales by category', 'woocommerce' ); ?></span></h3>
					<div class="inside chart">
						<div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
						<div id="chart-legend"></div>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(function(){

				<?php
					// Variables
					foreach ( $chart_data as $name => $data ) {
						$varname = 'cat_' . str_replace( '-', '_', sanitize_title( $name ) ) . '_data';
						echo 'var ' . $varname . ' = jQuery.parseJSON( \'' . json_encode( $data ) . '\' );';
					}
				?>

				var placeholder = jQuery("#placeholder");

				var plot = jQuery.plot(placeholder, [
					<?php
					$labels = array();

					foreach ( $chart_data as $name => $data ) {
						$labels[] = '{ label: "' . esc_js( $name ) . '", data: ' . 'cat_' . str_replace( '-', '_', sanitize_title( $name ) ) . '_data }';
					}

					echo implode( ',', $labels );
					?>
				], {
					legend: {
						container: jQuery('#chart-legend'),
						noColumns: 2
					},
					series: {
						lines: { show: true, fill: true },
						points: { show: true, align: "left" }
					},
					grid: {
						show: true,
						aboveData: false,
						color: '#aaa',
						backgroundColor: '#fff',
						borderWidth: 2,
						borderColor: '#aaa',
						clickable: false,
						hoverable: true
					},
					xaxis: {
						mode: "time",
						timeformat: "%b",
						monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
						tickLength: 1,
						minTickSize: [1, "month"]
					},
					yaxes: [ { min: 0, tickDecimals: 2 } ]
			 	});

			 	placeholder.resize();
			});
		</script>
		<?php
	}
	?>
	<script type="text/javascript">
		jQuery(function(){
			jQuery("select.chosen_select").chosen();
		});
	</script>
	<?php
}
