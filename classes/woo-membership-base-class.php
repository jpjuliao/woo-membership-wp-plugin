<?php

/**
 * Main plugin class for Woo Membership and Exclude Category Products.
 */
class Woo_Membership_Base
{
  /**
   * Singleton instance of the class.
   *
   * @var Woo_Membership
   */
  private static $instance = null;

  /**
   * Private constructor to enforce singleton pattern.
   */
  protected function __construct()
  {
    add_action('admin_menu', array($this, 'add_admin_menu'));
  }

  /**
   * Get the singleton instance of the class.
   *
   * @return Woo_Membership
   */
  public static function instance()
  {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
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
   * Renders the combined admin page with tabs for settings.
   */
  public function admin_page()
  {
    include_once(plugin_dir_path(__FILE__) . 'admin/admin-page.php');
  }

}
