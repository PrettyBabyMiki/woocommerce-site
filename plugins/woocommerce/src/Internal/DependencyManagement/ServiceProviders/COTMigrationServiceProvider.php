<?php
/**
 *
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable\WPPostToCOTMigrator;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;

class COTMigrationServiceProvider extends AbstractServiceProvider {

	protected $provides = array(
		WPPostToCOTMigrator::class
	);

	/**
	 * Use the register method to register items with the container via the
	 * protected $this->leagueContainer property or the `getLeagueContainer` method
	 * from the ContainerAwareTrait.
	 *
	 * @return void
	 */
	public function register() {
		$this->share( WPPostToCOTMigrator::class );
	}
}
