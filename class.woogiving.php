<?php

// Create WooGiving payment gateway class

class WooGiving extends WC_Payment_Gateway {
 
    // Configure payment gateway
    
    function __construct() {
 
        // Configuration

        $this->id = 'woogiving';
        $this->method_title = __( 'JustGiving', 'woogiving' );
        $this->method_description = __( 'Adds a JustGiving payment gateway to WooCommerce.', 'woogiving' );
        $this->title = __( 'JustGiving', 'woogiving' );
        $this->icon = plugin_dir_url( __FILE__ ) . 'img/jg-logo.png';
        $this->has_fields = false;

        // Get settings and turn into variables

        $this->init_form_fields();
        $this->init_settings();
        
        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }
         
        // Save settings
        
        if ( is_admin() ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

    }
 
    // Build settings for payment gateway

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'     => __( 'Enable/Disable', 'woogiving' ),
                'label'     => __( 'Enable JustGiving', 'woogiving' ),
                'type'      => 'checkbox',
                'default'   => 'no'
            ),
            'username' => array(
                'title'     => __( 'JustGiving Fundraising Page Username', 'woogiving' ),
                'type'      => 'text',
                'desc_tip'  => __( 'The justgiving page URL username that you would like your users to donate to. For example https://www.justgiving.com/thisistheusername.', 'woogiving' )
            ),
            'app_id' => array(
                'title'     => __( 'App ID', 'woogiving' ),
                'type'      => 'text',
                'desc_tip'  => __( 'Signup for an JustGiving Developer account here: http://pages.justgiving.com/developer', 'woogiving' ),
            ),
            'api_login' => array(
                'title'     => __( 'JustGiving Developer Username', 'woogiving' ),
                'type'      => 'text'
            ),
            'api_password' => array(
                'title'     => __( 'JustGiving Developer Password', 'woogiving' ),
                'type'      => 'password'
            ),
            'description' => array(
                'title'     => __( 'Description', 'woogiving' ),
                'type'      => 'textarea',
                'desc_tip'  => __( 'The payment description on the checkout page.', 'woogiving' ),
                'default'   => __( 'Pay securely via JustGiving.', 'woogiving' )
            ),
        );      
    }
     
    // Redirect user to JustGiving
    
    public function process_payment( $order_id ) {
        
        global $woocommerce;

        // Get customer order
        
        $customer_order = new WC_Order( $order_id );

        // Generate URL

        $justgiving_qstr = array(
            'amount' => $customer_order->order_total,
            'reference' => woogiving_create_ref( $order_id ),
            'currency' => get_woocommerce_currency(),
            'exitUrl' => plugin_dir_url( __FILE__ ) . 'process.php?wg_action=process&wg_order_id=' . str_replace( '#', '', $customer_order->get_order_number() ) . '&jg_donation_id=JUSTGIVING-DONATION-ID'
        );
        $justgiving_url = 'https://www.justgiving.com/'.rawurlencode($this->username).'/4w350m3/donate/?' . http_build_query( $justgiving_qstr );

        // Redirect to JustGiving

        return array(
            'result'   => 'success',
            'redirect' => $justgiving_url,
        );
 
    }
     
    // Validate fields
    
    public function validate_fields() {
        return true;
    }
 
}

?>