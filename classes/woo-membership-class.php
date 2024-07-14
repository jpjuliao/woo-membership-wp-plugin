<?php

/**
 * Main plugin class for Woo Membership and Exclude Category Products.
 */
class Woo_Membership extends Woo_Membership_Base
{

  private function __construct()
  {
    parent::__construct();

    add_action(
      'admin_init',
      array($this, 'register_settings')
    );

    add_action(
      'woocommerce_order_status_completed',
      array($this, 'handle_add_membership_on_order_completed'),
      10,
      1
    );

    add_action(
      'admin_post_add_membership',
      array($this, 'handle_add_membership_on_admin_page')
    );


    add_action(
      'pre_get_posts',
      array($this, 'exclude_membership_products_on_frontend_queries')
    );
  }

  /**
   * Registers the settings for the plugin.
   */
  public function register_settings()
  {
    register_setting(
      'woo_membership_settings_group',
      'woo_membership_product_id'
    );
  }

  /**
   * Adds a meta field to the current user when they purchase a specific product.
   *
   * @param int $order_id The ID of the completed order.
   */
  public function handle_add_membership_on_order_completed($order_id)
  {
    $order = wc_get_order($order_id);
    $woo_membership_product_id = get_option('woo_membership_product_id');

    foreach ($order->get_items() as $item) {
      $product_id = $item->get_product_id();
      if ($product_id == $woo_membership_product_id) {
        $user_id = $order->get_user_id();
        if ($user_id) {
          update_user_meta($user_id, $this->meta_key, true);
        }
        break;
      }
    }
  }

  /**
   * Handles the form submission to add membership manually.
   */
  public function handle_add_membership_on_admin_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    check_admin_referer('add_membership_nonce');

    if (isset($_POST['user_id'])) {
      $user_id = intval($_POST['user_id']);
      if ($user_id) {
        update_user_meta($user_id, $this->meta_key, true);
        wp_redirect(
          add_query_arg('membership_added', 'true', wp_get_referer())
        );
        exit;
      }
    }

    wp_redirect(add_query_arg('membership_added', 'false', wp_get_referer()));
    exit;
  }

  /**
   * Modifies the main query to exclude products from a specific category.
   *
   * @param WP_Query $query The current WP_Query object.
   */
  public function exclude_membership_products_on_frontend_queries($query)
  {
    $user = wp_get_current_user();
    if ($user->exists() && get_user_meta($user->ID, $this->meta_key, true)) {
      return;
    }
    
    if (
      !is_admin() && $query->is_main_query() &&
      (is_shop() || is_product_category() || is_product_tag())
    ) {
      $exclude_category = get_option('woo_membership_product_id');
      if ($exclude_category) {

        $tax_query = $query->get('tax_query') ?: array();
        $tax_query[] = array(
          'taxonomy' => 'product_cat',
          'field'    => 'slug',
          'terms'    => $exclude_category,
          'operator' => 'NOT IN',
        );
        $query->set('tax_query', $tax_query);
      }
    }
  }
}
