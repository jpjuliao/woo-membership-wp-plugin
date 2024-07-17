<?php
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'list';
?>
<div class="wrap">

  <h1>Woo Membership</h1>

  <h2 class="nav-tab-wrapper">
    <a href="?page=woo-membership&tab=list" class="nav-tab <?php echo $active_tab == 'list' ? 'nav-tab-active' : ''; ?>">Members</a>

    <a href="?page=woo-membership&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
  </h2>

  <?php

  settings_fields('woo_membership_settings_group');
  do_settings_sections('woo-membership-settings');

  if ($active_tab == 'settings') {
    include_once(plugin_dir_path(__FILE__) . '../admin/tab-settings.php');
  }

  if ($active_tab == 'list') {
    include_once(plugin_dir_path(__FILE__) . '../admin/tab-members.php');
  }

  ?>

</div>