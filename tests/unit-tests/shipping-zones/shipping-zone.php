<?php
namespace WooCommerce\Tests\Shipping_Zone;

/**
 * Class Shipping_Zone.
 * @package WooCommerce\Tests\Shipping_Zone
 */
class Shipping_Zone extends \WC_Unit_Test_Case {

	/**
	 * Test: WC_Shipping_Zone::get_data
	 */
	public function test_get_data() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );
        $data = $zone->get_data();

        // Assert
        $this->assertTrue( \is_array( $data ) );
        $this->assertTrue( 4 === \sizeof( $data ) );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zones::get_zone_id
	 */
    public function test_get_zone_id() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertEquals( $zone->get_zone_id(), 1 );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
    }

    /**
	 * Test: WC_Shipping_Zones::get_zone_name
	 */
    public function test_get_zone_name() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertEquals( $zone->get_zone_name(), 'Local' );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
    }

    /**
	 * Test: WC_Shipping_Zones::get_zone_order
	 */
    public function test_get_zone_order() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertEquals( $zone->get_zone_order(), 1 );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
    }

    /**
	 * Test: WC_Shipping_Zones::get_zone_locations
	 */
    public function test_get_zone_locations() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertTrue( \is_array( $zone->get_zone_locations() ) );
        $this->assertTrue( 2 === \sizeof( $zone->get_zone_locations() ) );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
    }

    /**
	 * Test: WC_Shipping_Zones::get_formatted_location
	 */
    public function test_get_formatted_location() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertEquals( $zone->get_formatted_location(), 'United Kingdom (UK), CB*' );

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 2 );

        // Assert
        $this->assertEquals( $zone->get_formatted_location(), 'Europe' );

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 3 );

        // Assert
        $this->assertEquals( $zone->get_formatted_location(), 'California' );

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 4 );

        // Assert
        $this->assertEquals( $zone->get_formatted_location(), 'United States (US)' );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
    }

    /**
	 * Test: WC_Shipping_Zone::get_shipping_methods
	 */
	public function test_get_shipping_methods() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );
		$zone->add_shipping_method( 'flat_rate' );
		$methods = $zone->get_shipping_methods();

        // Assert
        $this->assertTrue( 1 === \sizeof( $methods ) );
		$this->assertInstanceOf( 'WC_Shipping_Method', current( $methods ) );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::set_zone_name
	 */
	public function test_set_zone_name() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );
		$zone->set_zone_name( 'I am a fish' );

        // Assert
        $this->assertEquals( $zone->get_zone_name(), 'I am a fish' );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::set_zone_order
	 */
	public function test_set_zone_order() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );
		$zone->set_zone_order( 100 );

        // Assert
        $this->assertEquals( $zone->get_zone_order(), 100 );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::is_valid_location_type
	 */
	public function test_is_valid_location_type() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertEquals( $zone->get_zone_order(), 1 );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::add_location
	 */
	public function test_add_location() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertTrue( $zone->is_valid_location_type( 'state' ) );
		$this->assertTrue( $zone->is_valid_location_type( 'country' ) );
		$this->assertTrue( $zone->is_valid_location_type( 'continent' ) );
		$this->assertTrue( $zone->is_valid_location_type( 'postcode' ) );
		$this->assertFalse( $zone->is_valid_location_type( 'poop' ) );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::clear_locations
	 */
	public function test_clear_locations() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );
		$zone->clear_locations();

        // Assert
        $zone_locations = $zone->get_zone_locations();
        $this->assertTrue( empty( $zone_locations ) );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::set_locations
	 */
	public function test_set_locations() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );
		$zone->clear_locations();
		$zone->set_locations( array(
			array(
				'code' => 'US',
				'type' => 'country'
			),
			array(
				'code' => '90210',
				'type' => 'postcode'
			)
		) );

        // Assert
        $this->assertEquals( $zone->get_zone_locations(), array(
			2 => (object) array(
				'code' => 'US',
				'type' => 'country'
			),
			3 => (object) array(
				'code' => '90210',
				'type' => 'postcode'
			)
		) );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::save
	 */
	public function test_save() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

		// Test
        $zone = \WC_Shipping_Zones::get_zone( 1 );
		$zone->set_zone_name( 'I am a fish' );
		$zone->save();
		$zone = \WC_Shipping_Zones::get_zone( 1 );

        // Assert
        $this->assertEquals( $zone->get_zone_name(), 'I am a fish' );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}

    /**
	 * Test: WC_Shipping_Zone::add_shipping_method
	 */
	public function test_add_shipping_method() {
        // Setup
        \WC_Helper_Shipping_Zones::create_mock_zones();

        // Test
        $zone        = \WC_Shipping_Zones::get_zone( 1 );
		$zone->add_shipping_method( 'flat_rate' );
		$zone->add_shipping_method( 'free_shipping' );

        // Assert
		$methods = $zone->get_shipping_methods();

		// Assert
		$this->assertTrue( 2 === \sizeof( $methods ) );
		$this->assertInstanceOf( 'WC_Shipping_Method', current( $methods ) );

        // Clean
        \WC_Helper_Shipping_Zones::remove_mock_zones();
	}
}
