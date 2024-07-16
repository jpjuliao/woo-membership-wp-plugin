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
  
}
