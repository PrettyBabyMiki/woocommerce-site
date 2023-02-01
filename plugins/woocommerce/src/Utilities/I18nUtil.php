<?php
/**
 * A class of utilities for dealing with internationalization.
 */

namespace Automattic\WooCommerce\Utilities;

final class I18nUtil {
	/**
	 * @var array $units
	 */
	private static $units;

	/**
	 * Get the translated label for a weight unit of measure.
	 *
	 * @param string $weight_unit
	 *
	 * @return string
	 */
	public static function get_weight_unit_label( $weight_unit ) {
		if ( empty( self::$units ) ) {
			self::$units = include WC()->plugin_path() . '/i18n/units.php';
		}

		$label = '';

		if ( ! empty( self::$units['weight'][ $weight_unit ] ) ) {
			$label = self::$units['weight'][ $weight_unit ];
		}

		return $label;
	}

	/**
	 * Get the translated label for a dimensions unit of measure.
	 *
	 * @param string $dimensions_unit
	 *
	 * @return string
	 */
	public static function get_dimensions_unit_label( $dimensions_unit ) {
		if ( empty( self::$units ) ) {
			self::$units = include WC()->plugin_path() . '/i18n/units.php';
		}

		$label = '';

		if ( ! empty( self::$units['dimensions'][ $dimensions_unit ] ) ) {
			$label = self::$units['dimensions'][ $dimensions_unit ];
		}

		return $label;
	}
}
