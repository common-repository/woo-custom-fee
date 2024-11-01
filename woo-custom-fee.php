<?php
/*
 * Plugin Name: Custom Fee Woocommerce
 * Author: QuanticEdge
 * Author URI: https://quanticedgesolutions.com//?utm-source=free-plugin&utm-medium=wooextend
 * Description: This plugin allows user to add custom fee to user's order total. It allows admin to change fees description and amount from admin. 
 * Version: 1.8
 * Tested up to: 6.5
 * WC tested up to: 8.4.5
 */


class WC_Settings_Custom_Fee {
    /**
     * Class and hooks required actions & filters.
     */
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_custom_shipping_fee', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_custom_shipping_fee', __CLASS__ . '::update_settings' );
    }    
    
    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['custom_shipping_fee'] = __( 'Custom Fee', 'woocommerce-settings-custom-fee' );
        return $settings_tabs;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings.
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __( 'Custom Fee', 'woocommerce-settings-custom-fee' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_custom_shipping_fee_section_title'
            ),
            'enable' => array(
                'name' => __( 'Enable Custom Fee', 'woocommerce-settings-custom-fee' ),
                'type' => 'checkbox',
                'desc' => __( 'Check this if you want to enable this feature.', 'woocommerce-settings-custom-fee' ),
                'id'   => 'wc_enable_custom_shipping_fee'
            ),
            'title' => array(
                'name' => __( 'Fee Title', 'woocommerce-settings-custom-fee' ),
                'type' => 'text',
                'desc' => __( 'This will be title of the fee on checkout page.', 'woocommerce-settings-custom-fee' ),
                'id'   => 'wc_custom_shipping_fee_title'
            ),
            'amount' => array(
                'name' => __( 'Fixed Amount ( ' . get_woocommerce_currency_symbol() . ' )', 'woocommerce-settings-custom-fee' ),
                'type' => 'text',
                'desc' => __( 'This amount will be added to the order total when user places an order.', 'woocommerce-settings-custom-fee' ),
                'id'   => 'wc_custom_shipping_fee_amount'
            ),
            'type' => array(
                'name' => __( 'Type of Fee', 'woocommerce-settings-custom-fee' ),
                'type' => 'select',
                'desc' => __( 'Whether this is a fixed amount fee or % of order total.', 'woocommerce-settings-custom-fee' ),
                'options' => array( 'Fixed Amount' => 'Fixed Amount', '% of Order Total' => '% of Order Total'),
                'id'   => 'wc_custom_shipping_fee_type'
            ),
            'condition_type' => array(
                'name' => __( 'Apply fee when (optional)', 'woocommerce-settings-custom-fee' ),
                'type' => 'select',
                'desc' => __( 'Whether you want to apply fee if order amount is more than $XX or less than $XX. (Leave empty if you do not wish to use this.)', 'woocommerce-settings-custom-fee' ),
                'options' => array( 'Order is more than' => 'Order is more than', 'Order is less than' => 'Order is less than'),
                'id'   => 'wc_custom_fee_condition_type'
            ),
            'minimum_cart_amount' => array(
                'name' => __( 'Cart Amount (Optional)', 'woocommerce-settings-custom-fee' ),
                'type' => 'text',
                'desc' => __( 'cart amount, after which this fee will be added to order total. (Leave blank if you do not wish to use this feature.)', 'woocommerce-settings-custom-fee' ),
                'id'   => 'wc_custom_fee_minimum_cart_amount'
            ),
            'shipping_charge' => array(
                'name' => __( 'Include Shipping Charge', 'woocommerce-settings-custom-fee' ),
                'type' => 'checkbox',
                'desc' => __( 'Include shipping charge in Order Total when "Type of Fee" is "% of Order Total".', 'woocommerce-settings-custom-fee' ),
                'id'   => 'wc_custom_shipping_fee_include_shipping_charge'
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_custom_shipping_fee_section_end'
            )
        );
        return apply_filters( 'wc_custom_shipping_fee_settings', $settings );
    }
}
WC_Settings_Custom_Fee::init();

function tofloat($num) {
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
   
    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    } 

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
    );
}

add_action( 'woocommerce_cart_calculate_fees', 'woo_add_cart_fee');

function woo_add_cart_fee() {

    if(get_option('wc_enable_custom_shipping_fee') == 'yes') {
        if(get_option('wc_custom_shipping_fee_type') == 'Fixed Amount') {
    
            // Check for minimum cart amount
            $arrCart = WC()->cart->get_totals();
            $cartVal = $arrCart['subtotal'];
            $minCartVal = get_option('wc_custom_fee_minimum_cart_amount');
            $conditionType = get_option('wc_custom_fee_condition_type');
            
            if(isset($minCartVal) && !empty($minCartVal) && $conditionType == 'Order is more than') {
                if($minCartVal > $cartVal) {
                    return;
                }
            } else if(isset($minCartVal) && !empty($minCartVal) && $conditionType == 'Order is less than') {
                if($minCartVal < $cartVal) {
                    return;
                }
            }
            WC()->cart->add_fee( __(get_option('wc_custom_shipping_fee_title'), 'woocommerce'), tofloat(get_option('wc_custom_shipping_fee_amount')) );            
        } else {
            
            // Check for minimum cart amount
            $arrCart = WC()->cart->get_totals();
            $cartVal = $arrCart['subtotal'];
            $minCartVal = get_option('wc_custom_fee_minimum_cart_amount');
            $conditionType = get_option('wc_custom_fee_condition_type');

            if(isset($minCartVal) && !empty($minCartVal) && $conditionType == 'Order is more than') {
                if($minCartVal > $cartVal) {
                    return;
                }
            } else if(isset($minCartVal) && !empty($minCartVal) && $conditionType == 'Order is less than') {
                if($minCartVal < $cartVal) {
                    return;
                }
            }

            if(get_option('wc_custom_shipping_fee_include_shipping_charge') == 'yes') {
                $cartAmount = tofloat($cartVal) + tofloat(preg_replace( '#[^\d.]#', '', WC()->cart->shipping_total));
            } else {
                $cartAmount = tofloat($cartVal);
            }

            $fltFee = tofloat(tofloat( $cartAmount) * tofloat(get_option('wc_custom_shipping_fee_amount')) / 100) ;
            
            WC()->cart->add_fee( __(get_option('wc_custom_shipping_fee_title'), 'woocommerce'), $fltFee );
        }
    }
}
