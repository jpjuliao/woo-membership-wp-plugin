<?php
/*
Plugin Name: Woo Membership and Exclude Category Products
Description: Adds a meta field 'woo_membership_status' to the current user when buying a specific product and allows manual addition of membership from the admin page. Excludes all products of a specific category from all frontend queries.
Version: 1.0
Author: Juan Pablo Juliao
Author URI: https://jpjuliao.github.io
*/

if (!class_exists('Woo_Membership_Exclude_Category')) {
  /**
   * Main plugin class for Woo Membership and Exclude Category Products.
   */
  class Woo_Membership_Exclude_Category
  {
    /**
     * Singleton instance of the class.
     *
     * @var Woo_Membership_Exclude_Category
     */
    private static $instance = null;

    /**
     * Meta key for user membership status.
     *
     * @var string
     */
    private $meta_key = 'woo_membership_status';

    /**
     * Private constructor to enforce singleton pattern.
     */
    private function __construct()
    {
      // Membership related hooks
      add_action('woocommerce_order_status_completed', array($this, 'add_meta_field_on_purchase'), 10, 1);
      add_action('admin_menu', array($this, 'add_admin_menu'));
      add_action('admin_init', array($this, 'register_settings'));
      add_action('admin_post_add_membership', array($this, 'handle_add_membership'));

      // Category exclusion hooks
      add_action('admin_init', array($this, 'exclude_category_settings'));
      add_action('pre_get_posts', array($this, 'modify_query'));
    }

    /**
     * Get the singleton instance of the class.
     *
     * @return Woo_Membership_Exclude_Category
     */
    public static function instance()
    {
      if (is_null(self::$instance)) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    /**
     * Adds a meta field to the current user when they purchase a specific product.
     *
     * @param int $order_id The ID of the completed order.
     */
    public function add_meta_field_on_purchase($order_id)
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
     * Adds the admin menu item for the plugin.
     */
    public function add_admin_menu()
    {
      add_menu_page(
        'Woo Membership & Exclude Category',
        'Woo Membership & Exclude Category',
        'manage_options',
        'woo-membership-exclude-category',
        array($this, 'admin_page'),
        'dashicons-admin-generic'
      );
    }

    /**
     * Registers the settings for the plugin.
     */
    public function register_settings()
    {
      register_setting('woo_membership_settings_group', 'woo_membership_product_id');
      register_setting('exclude-category-products-group', 'exclude_category_products_option');
    }

    /**
     * Handles the form submission to add membership manually.
     */
    public function handle_add_membership()
    {
      if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
      }

      check_admin_referer('add_membership_nonce');

      if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        if ($user_id) {
          update_user_meta($user_id, $this->meta_key, true);
          wp_redirect(add_query_arg('membership_added', 'true', wp_get_referer()));
          exit;
        }
      }

      wp_redirect(add_query_arg('membership_added', 'false', wp_get_referer()));
      exit;
    }

    /**
     * Renders the combined admin page with tabs for settings.
     */
    public function admin_page()
    {
      $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'membership_settings';
?>
      <div class="wrap">
        <h1>Woo Membership & Exclude Category</h1>
        <h2 class="nav-tab-wrapper">
          <a href="?page=woo-membership-exclude-category&tab=membership_settings" class="nav-tab <?php echo $active_tab == 'membership_settings' ? 'nav-tab-active' : ''; ?>">Membership Settings</a>
          <a href="?page=woo-membership-exclude-category&tab=exclude_category_settings" class="nav-tab <?php echo $active_tab == 'exclude_category_settings' ? 'nav-tab-active' : ''; ?>">Exclude Category Settings</a>
        </h2>
        <form method="post" action="options.php">
          <?php
          if ($active_tab == 'membership_settings') {
            settings_fields('woo_membership_settings_group');
            $this->membership_settings_page();
          } else {
            settings_fields('exclude-category-products-group');
            $this->exclude_category_page();
          }
          ?>
          <?php submit_button(); ?>
        </form>
      </div>
    <?php
    }

    /**
     * Renders the membership settings page.
     */
    public function membership_settings_page()
    {
      include_once(plugin_dir_path(__FILE__) . 'admin/membership-settings.php');
    }

    /**
     * Registers settings for category exclusion.
     */
    public function exclude_category_settings()
    {
      register_setting('exclude-category-products-group', 'exclude_category_products_option');
    }

    /**
     * Renders the category exclusion settings page.
     */
    public function exclude_category_page()
    {
      include_once(plugin_dir_path(__FILE__) . 'admin/category-page.php');
    }

    /**
     * Modifies the main query to exclude products from a specific category.
     *
     * @param WP_Query $query The current WP_Query object.
     */
    public function modify_query($query)
    {
      if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_category() || is_product_tag())) {
        $exclude_category = get_option('exclude_category_products_option');
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

  /**
   * Returns the main instance of the plugin.
   *
   * @return Woo_Membership_Exclude_Category
   */
  function Woo_Membership_Exclude_Category()
  {
    return Woo_Membership_Exclude_Category::instance();
  }

  // Initialize the plugin
  Woo_Membership_Exclude_Category();
}
