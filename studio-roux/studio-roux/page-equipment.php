<?php
/**
 * Template Name: Equipment
 */
get_header(); ?>
<main class="page-content">
  <div class="container">
    <div class="section-title">
      <h2>Studio Gear</h2>
      <p>Equipment inventory and maintenance tracking.</p>
    </div>

    <?php
    $categories = get_terms(['taxonomy' => 'equipment_category', 'hide_empty' => true]);
    $args = ['post_type' => 'studio_equipment', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'];

    if (isset($_GET['cat']) && !empty($_GET['cat'])) {
      $args['tax_query'] = [['taxonomy' => 'equipment_category', 'field' => 'slug', 'terms' => sanitize_text_field($_GET['cat'])]];
    }

    $equip = new WP_Query($args);

    // Summary stats
    $total_items = $equip->found_posts;
    $total_value = 0;
    $condition_counts = [];
    if ($equip->have_posts()) {
      while ($equip->have_posts()) { $equip->the_post();
        $val = floatval(get_post_meta(get_the_ID(), '_current_value', true));
        $cond = get_post_meta(get_the_ID(), '_condition', true) ?: 'Unknown';
        $total_value += $val;
        $condition_counts[$cond] = ($condition_counts[$cond] ?? 0) + 1;
      }
      wp_reset_postdata();
    }
    $equip = new WP_Query($args); // reset query
    ?>

    <?php if ($total_items > 0) : ?>
    <div class="grid grid-4" style="margin-bottom:32px;">
      <div class="glass glass-compact stat-card">
        <div class="stat-number"><?php echo $total_items; ?></div>
        <div class="stat-label">Total Items</div>
      </div>
      <div class="glass glass-compact stat-card">
        <div class="stat-number">$<?php echo number_format($total_value, 0); ?></div>
        <div class="stat-label">Total Value</div>
      </div>
      <?php
      $cond_colors = ['Excellent' => 'approved', 'Good' => 'sent', 'Fair' => 'new', 'Poor' => 'declined', 'Needs Repair' => 'declined'];
      $shown = 0;
      foreach (['Excellent', 'Good', 'Fair', 'Poor', 'Needs Repair'] as $c) {
        if (isset($condition_counts[$c]) && $shown < 2) {
          echo '<div class="glass glass-compact stat-card"><div class="stat-number">' . $condition_counts[$c] . '</div><div class="stat-label">' . esc_html($c) . '</div></div>';
          $shown++;
        }
      }
      ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:32px;justify-content:center;">
        <a href="<?php echo get_permalink(); ?>" class="btn btn-sm <?php echo !isset($_GET['cat']) ? 'btn-primary' : ''; ?>">All</a>
        <?php foreach ($categories as $cat) : ?>
          <a href="<?php echo add_query_arg('cat', $cat->slug); ?>" class="btn btn-sm <?php echo (isset($_GET['cat']) && $_GET['cat'] === $cat->slug) ? 'btn-primary' : ''; ?>"><?php echo esc_html($cat->name); ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($equip->have_posts()) : ?>
      <div class="grid grid-2">
        <?php while ($equip->have_posts()) : $equip->the_post();
          $serial = get_post_meta(get_the_ID(), '_serial_number', true);
          $condition = get_post_meta(get_the_ID(), '_condition', true);
          $value = get_post_meta(get_the_ID(), '_current_value', true);
          $manufacturer = get_post_meta(get_the_ID(), '_manufacturer', true);
          $model = get_post_meta(get_the_ID(), '_model', true);
          $next_maint = get_post_meta(get_the_ID(), '_next_maintenance', true);
          $cats = get_the_terms(get_the_ID(), 'equipment_category');
          $cat_name = ($cats && !is_wp_error($cats)) ? $cats[0]->name : '';
          $cond_cls = strtolower(str_replace(' ', '', $condition));
          $has_thumbnail = has_post_thumbnail();
          $maint_overdue = $next_maint && strtotime($next_maint) < time();
          $maint_soon = $next_maint && strtotime($next_maint) <= strtotime('+30 days') && !$maint_overdue;
        ?>
          <a href="<?php the_permalink(); ?>" class="glass equip-card" style="text-decoration:none;color:inherit;display:flex;gap:20px;align-items:center;padding:<?php echo $has_thumbnail ? '20px' : '28px'; ?>;">
            <?php if ($has_thumbnail) : ?>
              <div style="width:80px;height:80px;min-width:80px;border-radius:var(--radius-sm);overflow:hidden;background:rgba(255,255,255,0.05);">
                <?php the_post_thumbnail('thumbnail', ['style' => 'width:100%;height:100%;object-fit:cover;']); ?>
              </div>
            <?php else : ?>
              <div class="equip-icon" style="width:80px;min-width:80px;text-align:center;font-size:2rem;">&#9881;</div>
            <?php endif; ?>
            <div class="equip-info" style="flex:1;min-width:0;">
              <h3 style="margin-bottom:4px;"><?php the_title(); ?></h3>
              <?php if ($manufacturer || $model) : ?>
                <p style="color:var(--text-muted);font-size:0.85rem;margin:0 0 6px;"><?php echo esc_html($manufacturer); ?> <?php echo esc_html($model); ?></p>
              <?php endif; ?>
              <div class="equip-meta">
                <?php if ($cat_name) : ?><span><?php echo esc_html($cat_name); ?></span><?php endif; ?>
                <?php if ($serial) : ?><span>S/N: <?php echo esc_html($serial); ?></span><?php endif; ?>
                <?php if ($condition) : ?>
                  <span class="badge badge-<?php echo $cond_cls === 'excellent' ? 'approved' : ($cond_cls === 'good' ? 'sent' : ($cond_cls === 'fair' ? 'new' : 'declined')); ?>"><?php echo esc_html($condition); ?></span>
                <?php endif; ?>
                <?php if ($value) : ?><span>$<?php echo number_format(floatval($value), 2); ?></span><?php endif; ?>
                <?php if ($maint_overdue) : ?>
                  <span style="color:var(--red);font-weight:600;">&#9888; Maintenance overdue</span>
                <?php elseif ($maint_soon) : ?>
                  <span style="color:var(--gold);">Maintenance due: <?php echo date('M j', strtotime($next_maint)); ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else : ?>
      <div class="glass" style="text-align:center;">
        <p style="color:var(--text-muted);">No equipment in the inventory yet.</p>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
