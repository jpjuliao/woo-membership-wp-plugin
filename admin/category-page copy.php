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
