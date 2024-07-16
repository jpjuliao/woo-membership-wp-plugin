<?php

/**
 * Main plugin class for Woo Membership and Exclude Category Products.
 */
class Woo_Membership extends Woo_Membership_Base
{

  /**
   * Meta key for user membership status.
   *
   * @var string
   */
  protected $membership_status_meta_key = 'woo_membership_status';

  private function __construct()
  {
    foreach (array(
      'admin_init',
      'admin_menu',
      'pre_get_posts',
      'woocommerce_order_status_completed',
      'admin_post_add_membership',
      'register_settings'
    ) as $action) {
      add_action($action, array($this, $action));
    }
  }

  /**
   * Adds the admin menu item for the plugin.
   */
  public function admin_menu()
  {
    add_menu_page(
      'Woo Membership',
      'Woo Membership',
      'manage_options',
      'woo-membership-list',
      array($this, 'admin_page'),
      'dashicons-admin-generic'
    );
  }

  /**
   * Registers the settings for the plugin.
   */
  public function register_settings()
  {
    register_setting(
      'woo_membership_settings_group',
      'woo_membership_members_category'
    );
    register_setting(
      'woo_membership_settings_group',
      'woo_membership_product_id'
    );
  }

  /**
   * Renders the combined admin page with tabs for settings.
   */
  public function admin_page()
  {
    do_action('admin_post_add_membership');
    include_once(plugin_dir_path(__FILE__) . '../admin/dashboard.php');
  }


  /**
   * Adds a meta field to the current user when they purchase a specific product.
   *
   * @param int $order_id The ID of the completed order.
   */
  public function woocommerce_order_status_completed($order_id)
  {
    $order = wc_get_order($order_id);
    $woo_membership_product_id = get_option('woo_membership_product_id');

    foreach ($order->get_items() as $item) {
      $product_id = $item->get_product_id();

      if ($product_id == $woo_membership_product_id) {
        $user_id = $order->get_user_id();

        if ($user_id) {
          update_user_meta($user_id, $this->membership_status_meta_key, true);
        }
        break;
      }
    }
  }

  /**
   * Handles the form submission to add membership manually.
   */
  public function admin_post_add_membership()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    check_admin_referer('add_membership_nonce');

    if (isset($_POST['user_id'])) {
      $user_id = intval($_POST['user_id']);

      if ($user_id) {

        update_user_meta($user_id, $this->membership_status_meta_key, true);

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
  public function pre_get_posts($query)
  {
    $user = wp_get_current_user();

    if (
      $user->exists() && 
      get_user_meta($user->ID, $this->membership_status_meta_key, true)
    ) {
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
