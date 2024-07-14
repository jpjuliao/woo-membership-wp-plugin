<?php ?>
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