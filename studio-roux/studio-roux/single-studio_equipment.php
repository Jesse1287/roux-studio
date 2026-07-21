<?php get_header(); ?>
<main class="page-content">
  <div class="container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <?php
      $serial = get_post_meta(get_the_ID(), '_serial_number', true);
      $purchase_date = get_post_meta(get_the_ID(), '_purchase_date', true);
      $purchase_price = get_post_meta(get_the_ID(), '_purchase_price', true);
      $current_value = get_post_meta(get_the_ID(), '_current_value', true);
      $condition = get_post_meta(get_the_ID(), '_condition', true);
      $manufacturer = get_post_meta(get_the_ID(), '_manufacturer', true);
      $model = get_post_meta(get_the_ID(), '_model', true);
      $last_maint = get_post_meta(get_the_ID(), '_last_maintenance', true);
      $next_maint = get_post_meta(get_the_ID(), '_next_maintenance', true);
      $maint_notes = get_post_meta(get_the_ID(), '_maintenance_notes', true);
      $cats = get_the_terms(get_the_ID(), 'equipment_category');
      $has_thumbnail = has_post_thumbnail();
      ?>
      <div style="max-width:900px;margin:0 auto;">
        <a href="<?php echo home_url('/equipment/'); ?>" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;">&laquo; Back to Gear</a>

        <?php if ($has_thumbnail) : ?>
        <div class="glass" style="margin-top:24px;padding:0;overflow:hidden;max-height:400px;">
          <?php the_post_thumbnail('large', ['style' => 'width:100%;height:100%;object-fit:cover;display:block;']); ?>
        </div>
        <?php endif; ?>

        <div class="glass" style="margin-top:<?php echo $has_thumbnail ? '16px' : '24px'; ?>;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
            <div>
              <h1 style="margin-bottom:8px;"><?php the_title(); ?></h1>
              <?php if ($cats && !is_wp_error($cats)) : ?>
                <p style="color:var(--text-muted);font-size:0.85rem;margin:0;"><?php echo esc_html($cats[0]->name); ?></p>
              <?php endif; ?>
            </div>
            <?php if ($condition) :
              $cond_cls = strtolower(str_replace(' ', '', $condition));
            ?>
              <span class="badge badge-<?php echo $cond_cls === 'excellent' ? 'approved' : ($cond_cls === 'good' ? 'sent' : ($cond_cls === 'fair' ? 'new' : 'declined')); ?>"><?php echo esc_html($condition); ?></span>
            <?php endif; ?>
          </div>

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:32px;">
            <?php if ($manufacturer || $model) : ?>
              <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Make / Model</p>
                <p style="margin:0;font-weight:600;"><?php echo esc_html($manufacturer); ?> <?php echo esc_html($model); ?></p>
              </div>
            <?php endif; ?>
            <?php if ($serial) : ?>
              <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Serial Number</p>
                <p style="margin:0;font-weight:600;"><?php echo esc_html($serial); ?></p>
              </div>
            <?php endif; ?>
            <?php if ($purchase_date) : ?>
              <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Purchased</p>
                <p style="margin:0;font-weight:600;"><?php echo date('M j, Y', strtotime($purchase_date)); ?></p>
              </div>
            <?php endif; ?>
            <?php if ($purchase_price) : ?>
              <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Purchase Price</p>
                <p style="margin:0;font-weight:600;">$<?php echo number_format(floatval($purchase_price), 2); ?></p>
              </div>
            <?php endif; ?>
            <?php if ($current_value) : ?>
              <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Current Value</p>
                <p style="margin:0;font-weight:600;">$<?php echo number_format(floatval($current_value), 2); ?></p>
              </div>
            <?php endif; ?>
            <?php if ($purchase_price && $current_value) :
              $depr = floatval($purchase_price) - floatval($current_value);
              $pct = floatval($purchase_price) > 0 ? round($depr / floatval($purchase_price) * 100) : 0;
            ?>
              <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Depreciation</p>
                <p style="margin:0;font-weight:600;color:<?php echo $pct > 50 ? 'var(--red)' : 'var(--text)'; ?>">$<?php echo number_format($depr, 2); ?> (<?php echo $pct; ?>%)</p>
              </div>
            <?php endif; ?>
          </div>

          <?php if (get_the_content()) : ?>
            <div style="border-top:1px solid var(--surface-border);padding-top:24px;margin-bottom:24px;">
              <h4 style="margin-bottom:12px;color:var(--gold);font-size:0.85rem;letter-spacing:2px;text-transform:uppercase;">Details</h4>
              <div style="color:var(--text);line-height:1.8;"><?php the_content(); ?></div>
            </div>
          <?php endif; ?>

          <?php if ($last_maint || $next_maint || $maint_notes) :
            $overdue = $next_maint && strtotime($next_maint) < time();
          ?>
            <div style="border-top:1px solid var(--surface-border);padding-top:24px;">
              <h4 style="margin-bottom:12px;color:var(--gold);font-size:0.85rem;letter-spacing:2px;text-transform:uppercase;">Maintenance</h4>
              <?php if ($overdue) : ?>
                <div class="alert alert-error" style="margin-bottom:16px;">This item's maintenance is overdue. Last scheduled: <?php echo date('M j, Y', strtotime($next_maint)); ?></div>
              <?php endif; ?>
              <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:16px;">
                <?php if ($last_maint) : ?>
                  <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                    <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Last Service</p>
                    <p style="margin:0;font-weight:600;"><?php echo date('M j, Y', strtotime($last_maint)); ?></p>
                  </div>
                <?php endif; ?>
                <?php if ($next_maint) : ?>
                  <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
                    <p style="color:var(--text-muted);font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Next Service</p>
                    <p style="margin:0;font-weight:600;color:<?php echo $overdue ? 'var(--red)' : ($next_maint && strtotime($next_maint) <= strtotime('+30 days') ? 'var(--gold)' : 'var(--text)'); ?>"><?php echo date('M j, Y', strtotime($next_maint)); ?><?php echo $overdue ? ' (overdue)' : ''; ?></p>
                  </div>
                <?php endif; ?>
              </div>
              <?php if ($maint_notes) : ?>
                <div style="padding:16px 20px;background:rgba(255,255,255,0.02);border-radius:var(--radius-sm);line-height:1.7;">
                  <?php echo nl2br(esc_html($maint_notes)); ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer(); ?>
