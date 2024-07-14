<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'membership_settings';
?>
<div class="wrap">

  <h1>Woo Membership</h1>

  <h2 class="nav-tab-wrapper">
    <a href="?page=woo-membership-settings&tab=woo-membership-list" class="nav-tab <?php echo $active_tab == 'woo-membership-list' ? 'nav-tab-active' : ''; ?>">Members</a>

    <a href="?page=woo-membership-settings&tab=membership_settings" class="nav-tab <?php echo $active_tab == 'membership_settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
  </h2>

  <form method="post" action="options.php">
    <?php
    
    if ($active_tab == 'membership_settings') {
      settings_fields('woo_membership_settings_group');
      include_once(plugin_dir_path(__FILE__) . '../admin/tab-settings.php');
    }
    
    if ($active_tab == 'woo-membership-list') {
      settings_fields('woo_membership_woo-membership-list_group');
      include_once(plugin_dir_path(__FILE__) . '../admin/tab-members.php');
    }

    ?>
    <?php submit_button(); ?>
  </form>

</div>