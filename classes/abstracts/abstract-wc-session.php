<?php
/**
 * Handle data for the current customers session.
 *
 * @class 		WC_Session
 * @version		2.0.0
 * @package		WooCommerce/Classes/Abstracts
 * @author 		WooThemes
 */
abstract class WC_Session {

    /** _data  */
    protected $_data = array();

    /** When something changes */
    protected $_dirty = false;

    /**
     * __get function.
     *
     * @access public
     * @param mixed $property
     * @return mixed
     */
    public function __get( $property ) {
        return isset( $this->_data[ $property ] ) ? $this->_data[ $property ] : null;
    }

    /**
     * __set function.
     *
     * @access public
     * @param mixed $property
     * @param mixed $value
     * @return void
     */
    public function __set( $property, $value ) {
        $this->_data[ $property ] = $value;
        $this->_dirty = true;
    }

     /**
     * __isset function.
     *
     * @access public
     * @param mixed $property
     * @return bool
     */
    public function __isset( $property ) {
        return isset( $this->_data[ $property ] );
    }

    /**
     * __unset function.
     *
     * @access public
     * @param mixed $property
     * @return void
     */
    public function __unset( $property ) {
    	if ( isset( $this->_data[ $property ] ) ) {
       		unset( $this->_data[ $property ] );
       		$this->_dirty = true;
        }
    }
}