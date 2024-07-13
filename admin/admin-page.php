<?php
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
        include_once(plugin_dir_path(__FILE__) . 'admin/membership-settings-page.php');
      } else {
        settings_fields('exclude-category-products-group');
        include_once(plugin_dir_path(__FILE__) . 'admin/category-page.php');
      }
      ?>
      <?php submit_button(); ?>
    </form>
  </div>