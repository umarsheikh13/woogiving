<?php
/*
Plugin Name: WooGiving
Plugin URI: http://umarsheikh.co.uk/
Description: Adds a JustGiving payment gateway to WooCommerce.
Version: 1.0
Author: Umar Sheikh
Author URI: http://umarsheikh.co.uk/
License: LICENSE
*/
 
// Include class and register payment gateway

add_action( 'plugins_loaded', 'woogiving_init', 0 );

function woogiving_init() {
    
    // Check if WooCommerce installed

    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
     
    // Include the woogiving class
    
    require_once 'class.woogiving.php';
 
    // Add class to WooCommerce

    add_filter( 'woocommerce_payment_gateways', 'woogiving_add_gateway' );

    function woogiving_add_gateway( $methods ) {
        $methods[] = 'WooGiving';
        return $methods;
    }

}
 
// Add custom action links on plugin page

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woogiving_action_links' );

function woogiving_action_links( $links ) {
    
    // Create action links

    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'woogiving' ) . '</a>',
    );
 
    // Merge with default links
    
    return array_merge( $plugin_links, $links );    
}

// Create unique reference

function woogiving_create_ref( $order_id ) {
    $customer_order = new WC_Order( $order_id );
    $ref  = home_url();
    $ref .= $customer_order->order_key;
    $ref .= $order_id;
    return strtoupper( md5( $ref ) );
}

// Get checkout return URL

function woogiving_get_return_url( $order = null ) {
    if ( $order ) {
        $return_url = $order->get_checkout_order_received_url();
    } else {
        $return_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
    }
    if ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' ) {
        $return_url = str_replace( 'http:', 'https:', $return_url );
    }
    return apply_filters( 'woocommerce_get_return_url', $return_url );
}

// Process payment function

function woogiving_process_donation(){

    // Setup post variables
    
    $wg_order_id    = $_POST['wg_order_id'];
    $jg_donation_id = $_POST['jg_donation_id'];

    if ( is_numeric( $wg_order_id ) && ctype_alnum( $jg_donation_id ) ) {

        // Validate order ID

        $wg_order_id_validation = get_post( (int)$wg_order_id );

        if ( $wg_order_id_validation ) {
            
            // Get options
            
            $wg_options = get_option('woocommerce_woogiving_settings');

            // Include JG API and setup API

            require_once 'inc/JustGivingClient.php';

            $jg_client = new JustGivingClient( 'https://api.justgiving.com/', $wg_options['app_id'], 1, $wg_options['api_login'], $wg_options['api_password'] );

            // Check if donation exists in JG

            $wg_donation_id_check = $jg_client->Donation->RetrieveRef( woogiving_create_ref( $wg_order_id_validation->ID ) );
            
            if ( count( $wg_donation_id_check->donations ) ) {
                
                // Check if donations IDs match

                if ( $wg_donation_id_check->donations[0]->id == (int)$jg_donation_id ) {
                    
                    // Get donation details from JG

                    $wg_donation_id_status = $jg_client->Donation->RetrieveStatus( $jg_donation_id );
                    $jg_amount = $wg_donation_id_status->amount;
                    $jg_status = $wg_donation_id_status->status;

                    // Get customer order
                    
                    $customer_order = new WC_Order( $wg_order_id_validation->ID );

                    // Check if amount donated is equal to higher than the amount in the order

                    if ( $jg_amount >= $customer_order->order_total && $jg_status == 'Accepted' ) {
                        
                        // Get JG fundraising page ID

                        $wg_jg_page_check = $jg_client->Page->Retrieve( $wg_options['username'] );

                        // Check if donation was made to correct charity

                        if ( $wg_jg_page_check->charity->id == $wg_donation_id_check->donations[0]->charityId ) {
                            
                            // Check order status

                            if ( $customer_order->get_status() == 'completed' ){

                                // Order has already been completed

                                return json_encode( array(
                                    'wg_status' => 'failure',
                                    'wg_message' => __( 'This donation has already been processed.', 'woogiving' )
                                ));

                            } else {

                                // Reduce stock levels and complete payment
                                
                                $customer_order->reduce_order_stock();
                                $customer_order->payment_complete( $jg_donation_id );
                                $customer_order->update_status( 'completed' );

                                return json_encode( array(
                                    'wg_status' => 'success',
                                    'wg_redirect' => woogiving_get_return_url( $customer_order )
                                ));

                            }

                        } else {
                            return json_encode( array(
                                'wg_status' => 'failure',
                                'wg_message' => __( 'This donation cannot be processed, please contact us.', 'woogiving' )
                            ));
                        }

                    }

                } else {
                    return json_encode( array(
                        'wg_status' => 'failure',
                        'wg_message' => __( 'Invalid donation ID.', 'woogiving' )
                    ));
                }

            } else {
                return json_encode( array(
                    'wg_status' => 'failure',
                    'wg_message' => __( 'Invalid donation ID.', 'woogiving' )
                ));
            }

        } else {
            return json_encode( array(
                'wg_status' => 'failure',
                'wg_message' => __( 'Donation could not be processed.', 'woogiving' )
            ));
        }

    } else {
        return json_encode( array(
            'wg_status' => 'failure',
            'wg_message' => __( 'Donation could not be processed.', 'woogiving' )
        ));
    }

}

// Process payment via ajax (process.php)

function woogiving_ajax_process_donation(){
    
    // Process donation
    
    $process_one = json_decode(woogiving_process_donation(), true); // Try for the first time

    if ( $process_one['wg_status'] == 'failure' ) {

        sleep(5);

        $process_two = json_decode(woogiving_process_donation(), true); // Try for the second time

        if ( $process_two['wg_status'] == 'failure' ) {

            sleep(5);

            echo woogiving_process_donation(); // Try for the last time

        } else {
            echo json_encode( $process_two );
        }

    } else {
        echo json_encode( $process_one );
    }

    die();
    
}
add_action('wp_ajax_wg_ajax_process_donation', 'woogiving_ajax_process_donation');
add_action('wp_ajax_nopriv_wg_ajax_process_donation', 'woogiving_ajax_process_donation');

?>