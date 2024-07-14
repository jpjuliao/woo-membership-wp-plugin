<?php settings_fields('woo_membership_settings'); ?>
<table class="form-table">
  <tr valign="top">
    <th scope="row">Select the product category for the Membership</th>
    <td>
      <?php
      $selected_category = get_option('woo_membership_settings_option');
      $args = array(
        'taxonomy' => 'product_cat',
        'orderby' => 'name',
        'hide_empty' => false,
      );
      $categories = get_categories($args);
      ?>
      <select name="woo_membership_settings_option">
        <?php 
        if (!$selected_category) {
          echo '<option value="" disabled selected>- Choose a Category -</option>';
        }

        foreach ($categories as $category) { ?>
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
    <th scope="row">Select the product to subscribe to membership</th>
    <td>
      <select name="woo_membership_product_id">
        <?php
        $args = array(
          'post_type' => 'product',
          'posts_per_page' => -1
        );
        $products = get_posts($args);
        $selected_product = get_option('woo_membership_product_id');

        if (!$selected_product) {
          echo '<option value="" disabled selected>- Choose a Product -</option>';
        }

        foreach ($products as $product) {
          echo '<option value="' . esc_attr($product->ID) . '" ' . 
          selected($selected_product, $product->ID, false) . '>' . 
          esc_html($product->post_title) . '</option>';
        }
        ?>
      </select>
    </td>
  </tr>
</table>