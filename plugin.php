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
    <?php
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
    ?>
      <table class="form-table">
        <tr valign="top">
          <th scope="row">Category to Exclude</th>
          <td>
            <?php
            $selected_category = get_option('exclude_category_products_option');
            $args = array(
              'taxonomy' => 'product_cat',
              'orderby' => 'name',
              'hide_empty' => false,
            );
            $categories = get_categories($args);
            ?>
            <select name="exclude_category_products_option">
              <?php foreach ($categories as $category) { ?>
                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_category, $category->slug); ?>>
                  <?php echo esc_html($category->name); ?>
                </option>
              <?php } ?>
            </select>
          </td>
        </tr>
      </table>
<?php
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
