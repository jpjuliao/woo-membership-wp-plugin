<?php
/*
Plugin Name: Woo Membership
Description: Adds a meta field 'woo_membership_status' to the current user when buying a specific product and allows manual addition of membership from the admin page. Excludes all products of a specific category from all frontend queries.
Version: 1.0
Author: Juan Pablo Juliao
Author URI: https://jpjuliao.github.io
*/

if (!class_exists('Woo_Membership')) {

  include_once(
    plugin_dir_path(__FILE__) . 'classes/woo-membership-class.php');

  /**
   * Returns the main instance of the plugin.
   *
   * @return Woo_Membership
   */
  function Woo_Membership()
  {
    new Woo_Membership();
  }

  // Initialize the plugin
  Woo_Membership();
}
