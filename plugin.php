<?php

/**
 * Plugin Name: Woo Membership
 * Description: Adds a meta field 'woo_membership_status' to the current user when buying a specific product and allows manual addition of membership from the admin page.
 * Version: 1.2
 * Author: Juan Pablo Juliao
 * Author URI: https://jpjuliao.github.io
 */

if (!class_exists('Woo_Membership')) {
  class Woo_Membership
  {
    /**
     * The single instance of the class.
     *
     * @var Woo_Membership
     */
    private static $instance = null;

    /**
     * Meta key name for user membership status.
     *
     * @var string
     */
    private $meta_key = 'woo_membership_status';

    /**
     * Main Woo_Membership Instance.
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return Woo_Membership - Main instance.
     */
    public static function instance()
    {
      if (is_null(self::$instance)) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    /**
     * Woo_Membership Constructor.
     */
    private function __construct()
    {
      // Hook into WooCommerce order completion
      add_action('woocommerce_order_status_completed', array($this, 'add_meta_field_on_purchase'), 10, 1);

      // Add admin menu page
      add_action('admin_menu', array($this, 'woo_membership_menu'));

      // Register settings
      add_action('admin_init', array($this, 'woo_membership_settings'));

      // Handle form submissions
      add_action('admin_post_add_membership', array($this, 'handle_add_membership'));
    }

    /**
     * Adds a meta field to the current user when they purchase a specific product.
     *
     * @param int $order_id The ID of the completed order.
     */
    public function add_meta_field_on_purchase($order_id)
    {
      // Get the order object
      $order = wc_get_order($order_id);

      // Get the product ID from the settings
      $woo_membership_product_id = get_option('woo_membership_product_id');

      // Loop through order items
      foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();

        // Check if the purchased product is the specific product
        if ($product_id == $woo_membership_product_id) {
          // Get the user ID
          $user_id = $order->get_user_id();

          if ($user_id) {
            // Add user meta
            update_user_meta($user_id, $this->meta_key, true);
          }
          break;
        }
      }
    }

    /**
     * Adds a menu item to the WordPress admin menu.
     */
    public function woo_membership_menu()
    {
      add_menu_page(
        'Woo Membership Settings',
        'Woo Membership',
        'manage_options',
        'woo-membership',
        array($this, 'woo_membership_settings_page'),
        'dashicons-admin-generic'
      );
    }

    /**
     * Registers the settings for the plugin.
     */
    public function woo_membership_settings()
    {
      register_setting('woo_membership_settings_group', 'woo_membership_product_id');
    }

    /**
     * Handles the form submission to add membership manually.
     */
    public function handle_add_membership()
    {
      if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
      }

      // Check nonce for security
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
     * Renders the settings page for the plugin.
     */
    public function woo_membership_settings_page()
    {
?>
      <div class="wrap">
        <h1>Woo Membership Settings</h1>
        <form method="post" action="options.php">
          <?php
          settings_fields('woo_membership_settings_group');
          do_settings_sections('woo_membership_settings_group');
          ?>

          <table class="form-table">
            <tr valign="top">
              <th scope="row">Select Product</th>
              <td>
                <select name="woo_membership_product_id">
                  <?php
                  $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => -1
                  );
                  $products = get_posts($args);
                  $selected_product = get_option('woo_membership_product_id');

                  foreach ($products as $product) {
                    echo '<option value="' . esc_attr($product->ID) . '" ' . selected($selected_product, $product->ID, false) . '>' . esc_html($product->post_title) . '</option>';
                  }
                  ?>
                </select>
              </td>
            </tr>
          </table>

          <?php submit_button(); ?>
        </form>

        <h2>Members List</h2>
        <table class="widefat fixed" cellspacing="0">
          <thead>
            <tr>
              <th class="manage-column column-username" scope="col">Username</th>
              <th class="manage-column column-name" scope="col">Name</th>
              <th class="manage-column column-email" scope="col">Email</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $args = array(
              'meta_key' => $this->meta_key,
              'meta_value' => true
            );
            $members = get_users($args);

            if (!empty($members)) {
              foreach ($members as $member) {
                echo '<tr>';
                echo '<td>' . esc_html($member->user_login) . '</td>';
                echo '<td>' . esc_html($member->display_name) . '</td>';
                echo '<td>' . esc_html($member->user_email) . '</td>';
                echo '</tr>';
              }
            } else {
              echo '<tr><td colspan="3">No members found.</td></tr>';
            }
            ?>
          </tbody>
        </table>

        <h2>Add Membership</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <?php wp_nonce_field('add_membership_nonce'); ?>
          <input type="hidden" name="action" value="add_membership">
          <table class="form-table">
            <tr valign="top">
              <th scope="row">Select User</th>
              <td>
                <select name="user_id">
                  <?php
                  $users = get_users(array('fields' => array('ID', 'user_login')));

                  foreach ($users as $user) {
                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->user_login) . '</option>';
                  }
                  ?>
                </select>
              </td>
            </tr>
          </table>

          <?php submit_button('Add Membership'); ?>
        </form>
      </div>
<?php
    }
  }

  /**
   * Returns the main instance of Woo_Membership.
   *
   * @return Woo_Membership
   */
  function Woo_Membership()
  {
    return Woo_Membership::instance();
  }

  // Initialize the plugin
  Woo_Membership();
}
