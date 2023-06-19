<?php
/*
Plugin Name: WooCommerce Product Status Updater
Plugin URI: https://deanhattingh.co.za/
Description: A plugin to update WooCommerce product status in bulk originally made for qqfabrics .
Version: 1.0.0
Author: Dean Hattingh
Author URI: https://deanhattingh.co.za/
License: GPL2
*/

// Add the plugin menu in the dashboard
function wcpsu_add_menu() {
    add_menu_page(
        'Product Status Updater', // Page title
        'Product Status Updater', // Menu title
        'manage_options', // Capability
        'wcpsu-plugin', // Menu slug
        'wcpsu_plugin_page', // Callback function
        'dashicons-admin-generic', // Icon
        90 // Position
    );
}
add_action( 'admin_menu', 'wcpsu_add_menu' );

// Callback function to render the plugin page
function wcpsu_plugin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['wcpsu_update_status'] ) && wp_verify_nonce( $_POST['wcpsu_update_status'], 'wcpsu_nonce' ) ) {
        $category_id = absint( $_POST['wcpsu_category'] );
        $status = sanitize_text_field( $_POST['wcpsu_status'] );

        // Update product status for products in the selected category
        if ( $category_id && $status && in_array( $status, array( 'draft', 'pending', 'publish' ) ) ) {
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $category_id,
                    ),
                ),
            );
            $products = new WP_Query( $args );

            while ( $products->have_posts() ) {
                $products->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product( $product_id );
                if ( $product ) {
                    $product->set_status( $status );
                    $product->save();
                }
            }

            wp_reset_postdata();

            echo '<div class="notice notice-success"><p>Product status updated successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Please select a category and status.</p></div>';
        }
    }

    // Render the plugin page content
    echo '<div class="wrap">';
    echo '<h1>WooCommerce Product Status Updater</h1>';
    echo '<form method="post">';
    echo wp_nonce_field( 'wcpsu_nonce', 'wcpsu_update_status' );
    echo '<label for="wcpsu_category">Category:</label> ';
    echo '<select name="wcpsu_category">';
    echo '<option value="">Select a category</option>';
    $categories = get_terms( 'product_cat', array( 'hide_empty' => false ) );
    foreach ( $categories as $category ) {
        echo '<option value="' . esc_attr( $category->term_id ) . '"' . selected( $category_id, $category->term_id, false ) . '>' . esc_html( $category->name ) . '</option>';
    }
    echo '</select>';
    echo '<br><br>';
   
echo '<label for="wcpsu_status">Status:</label> ';
echo '<select name="wcpsu_status">';
echo '<option value="">Select a status</option>';
$statuses = array(
'draft' => 'Draft',
'pending' => 'Pending Review',
'publish' => 'Published',
);
foreach ( $statuses as $value => $label ) {
echo '<option value="' . esc_attr( $value ) . '"' . selected( $status, $value, false ) . '>' . esc_html( $label ) . '</option>';
}
echo '</select>';
echo '<br><br>';
echo '<input type="submit" class="button button-primary" value="Update Status">';
echo '</form>';
echo '</div>';
}