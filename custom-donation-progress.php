<?php
/**
 * Plugin Name: Custom Donation Progress
 * Description: Adds a "funded" category to products when donation goals are met.
 * Version: 1.0
 * Author: Talha Ansari
 * Author URI: https://wa.me/+919022172070
 * Text Domain: custom-donation-progress
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds the "funded" category to a product when donation progress reaches or exceeds 100%.
 *
 * @param WC_Product $product The WooCommerce product object.
 * @param object $object The donation goal and progress data object.
 */

// Ensure "Funded" category exists only once, at plugin activation
function ensure_funded_category_exists() {
    if (!get_term_by('name', 'Funded', 'product_cat')) {
        wp_insert_term(
            'Funded',
            'product_cat',
            array(
                'description' => 'Products that have reached their funding goals.',
                'slug' => 'funded'
            )
        );
    }
}
register_activation_hook(__FILE__, 'ensure_funded_category_exists');

function custom_donation_message($product, $object) {
    if (!class_exists('WcdonationSetting')) {
        return; 
    }

    // Get goal settings
    $goalDisp = !empty($object->goal['display']) ? $object->goal['display'] : '';
    $closeForm = !empty($object->goal['form_close']) ? $object->goal['form_close'] : '';
    $goalType = !empty($object->goal['type']) ? $object->goal['type'] : '';
    $donation_product = !empty($object->product['product_id']) ? $object->product['product_id'] : '';
    $get_donations = WcdonationSetting::has_bought_items($donation_product);

    if ('enabled' === $goalDisp && 'enabled' === $closeForm) {
        $progress = 0;

        // Calculate progress
        if ('fixed_amount' === $goalType || 'percentage_amount' === $goalType) { 
            $fixedAmount = !empty($object->goal['fixed_amount']) ? $object->goal['fixed_amount'] : 0;
            $totalDonationAmount = !empty($get_donations['total_donation_amount']) ? $get_donations['total_donation_amount'] : 0;

            if ($fixedAmount > 0) {
                $progress = ($totalDonationAmount / $fixedAmount) * 100;
            }
        }

        if ('no_of_donation' === $goalType) { 
            $no_of_donation = !empty($object->goal['no_of_donation']) ? $object->goal['no_of_donation'] : 0;
            $totalDonations = !empty($get_donations['total_donations']) ? $get_donations['total_donations'] : 0;

            if ($no_of_donation > 0) {
                $progress = ($totalDonations / $no_of_donation) * 100;
            }
        }

        if ($progress >= 100) {
            // Get the "Funded" category ID
            $funded_category = get_term_by('name', 'Funded', 'product_cat');
            $funded_category_id = $funded_category ? $funded_category->term_id : 0;

            if ($funded_category_id && !has_term($funded_category_id, 'product_cat', $donation_product)) {
                wp_set_object_terms($donation_product, $funded_category_id, 'product_cat', true);
            }
        }
    }
}

add_action('wc_donation_after_archive_add_donation_button', 'custom_donation_message', 10, 2);
