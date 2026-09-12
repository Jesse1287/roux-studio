<?php
/**
 * Template Name: Dashboard
 */
get_header();
if (!is_user_logged_in()) { wp_redirect(wp_login_url(get_permalink())); exit; }
$user = wp_get_current_user();

// Counts
$bookings = wp_count_posts('studio_booking')->publish ?? 0;
$invoices = wp_count_posts('studio_invoice')->publish ?? 0;
$expenses = wp_count_posts('studio_expense')->publish ?? 0;
$equipment = wp_count_posts('studio_equipment')->publish ?? 0;
$gigs = wp_count_posts('studio_gig')->publish ?? 0;

// Pending bookings
$pending_bookings = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1, 'meta_key' => '_status', 'meta_value' => 'New']);
$pending = count($pending_bookings);

// This month financials
$revenue_month = 0;
$month_invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['year' => date('Y'), 'month' => date('m')]]]);
foreach ($month_invoices as $inv) { if (get_post_meta($inv->ID, '_deposit_paid', true)) { $revenue_month += floatval(get_post_meta($inv->ID, '_total', true)); } }

$expenses_month = 0;
$month_expenses = get_posts(['post_type' => 'studio_expense', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['year' => date('Y'), 'month' => date('m')]]]);
foreach ($month_expenses as $ex) { $expenses_month += floatval(get_post_meta($ex->ID, '_expense_amount', true)); }

$net_month = $revenue_month - $expenses_month;

// Year totals
$revenue_year = 0;
$year_invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['year' => date('Y')]]]);
foreach ($year_invoices as $inv) { if (get_post_meta($inv->ID, '_deposit_paid', true)) { $revenue_year += floatval(get_post_meta($inv->ID, '_total', true)); } }

$expenses_year = 0;
$year_expenses = get_posts(['post_type' => 'studio_expense', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['year' => date('Y')]]]);
foreach ($year_expenses as $ex) { $expenses_year += floatval(get_post_meta($ex->ID, '_expense_amount', true)); }

$net_year = $revenue_year - $expenses_year;

// Upcoming gigs
$upcoming_gigs = get_posts(['post_type' => 'studio_gig', 'post_status' => 'publish', 'numberposts' => 5, 'meta_key' => '_gig_date', 'meta_value' => date('Y-m-d'), 'compare' => '>=', 'orderby' => 'meta_value', 'order' => 'ASC']);
?>
<main class="page-content">
  <div class="container">
    <div class="section-title">
      <h2>Dashboard</h2>
      <p>Welcome back, <?php echo esc_html($user->display_name); ?>.</p>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-4" style="margin-bottom:32px;">
      <a href="<?php echo admin_url('edit.php?post_type=studio_booking'); ?>" class="glass glass-compact stat-card" style="text-decoration:none;">
        <div class="stat-number"><?php echo $bookings; ?></div>
        <div class="stat-label">Bookings</div>
        <?php if ($pending > 0) : ?>
          <span class="badge badge-new" style="margin-top:8px;"><?php echo $pending; ?> pending</span>
        <?php endif; ?>
      </a>
      <a href="<?php echo admin_url('edit.php?post_type=studio_invoice'); ?>" class="glass glass-compact stat-card" style="text-decoration:none;">
        <div class="stat-number"><?php echo $invoices; ?></div>
        <div class="stat-label">Invoices</div>
      </a>
      <a href="<?php echo admin_url('edit.php?post_type=studio_expense'); ?>" class="glass glass-compact stat-card" style="text-decoration:none;">
        <div class="stat-number"><?php echo $expenses; ?></div>
        <div class="stat-label">Expenses</div>
      </a>
      <a href="<?php echo admin_url('edit.php?post_type=studio_equipment'); ?>" class="glass glass-compact stat-card" style="text-decoration:none;">
        <div class="stat-number"><?php echo $equipment; ?></div>
        <div class="stat-label">Equipment</div>
      </a>
    </div>

    <!-- Financials + Quick Actions -->
    <div class="grid grid-2">
      <div class="glass">
        <h3 style="margin-bottom:16px;">This Month &mdash; <?php echo date('F Y'); ?></h3>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);">
          <span style="color:var(--text-muted);">Revenue</span>
          <span style="color:var(--green);font-weight:600;">$<?php echo number_format($revenue_month, 2); ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);">
          <span style="color:var(--text-muted);">Expenses</span>
          <span style="color:var(--red);font-weight:600;">$<?php echo number_format($expenses_month, 2); ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);">
          <span style="color:var(--text-muted);">Net</span>
          <span style="color:<?php echo $net_month >= 0 ? 'var(--green)' : 'var(--red)'; ?>;font-weight:600;">$<?php echo number_format($net_month, 2); ?></span>
        </div>

        <h4 style="margin:20px 0 12px;color:var(--text-muted);font-size:0.75rem;letter-spacing:2px;text-transform:uppercase;">Year to Date &mdash; <?php echo date('Y'); ?></h4>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);">
          <span style="color:var(--text-muted);">Revenue</span>
          <span style="color:var(--green);font-weight:600;">$<?php echo number_format($revenue_year, 2); ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);">
          <span style="color:var(--text-muted);">Expenses</span>
          <span style="color:var(--red);font-weight:600;">$<?php echo number_format($expenses_year, 2); ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:10px 0;">
          <span style="color:var(--text-muted);">Net</span>
          <span style="color:<?php echo $net_year >= 0 ? 'var(--green)' : 'var(--red)'; ?>;font-weight:600;">$<?php echo number_format($net_year, 2); ?></span>
        </div>
      </div>

      <div class="glass">
        <h3 style="margin-bottom:16px;">Quick Actions</h3>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <a href="<?php echo home_url('/booking/'); ?>" class="btn btn-sm btn-block">New Booking Form</a>
          <a href="<?php echo admin_url('post-new.php?post_type=studio_invoice'); ?>" class="btn btn-sm btn-block">Create Invoice</a>
          <a href="<?php echo admin_url('post-new.php?post_type=studio_expense'); ?>" class="btn btn-sm btn-block">Log Expense</a>
          <a href="<?php echo admin_url('post-new.php?post_type=studio_gig'); ?>" class="btn btn-sm btn-block">Add Gig</a>
          <a href="<?php echo home_url('/gigs/?ical=bookings'); ?>" class="btn btn-sm btn-block" target="_blank">Export iCal</a>
          <a href="<?php echo admin_url('edit.php?post_type=studio_booking'); ?>" class="btn btn-sm btn-block">Manage Bookings</a>
          <a href="<?php echo admin_url('edit.php?post_type=studio_invoice'); ?>" class="btn btn-sm btn-block">Manage Invoices</a>
        </div>

        <?php if ($pending > 0) : ?>
        <div style="margin-top:20px;padding:16px;background:rgba(74,158,255,0.08);border-radius:var(--radius-sm);border:1px solid rgba(74,158,255,0.2);">
          <p style="margin:0 0 8px;font-weight:600;color:var(--blue);">Pending Bookings</p>
          <?php foreach (array_slice($pending_bookings, 0, 3) as $pb) :
              $pb_svc = get_post_meta($pb->ID, '_service', true);
          ?>
          <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(74,158,255,0.1);">
            <a href="<?php echo get_edit_post_link($pb->ID); ?>" style="color:var(--text);text-decoration:none;"><?php echo esc_html($pb->post_title); ?></a>
            <span style="color:var(--text-muted);font-size:0.85rem;"><?php echo esc_html($pb_svc); ?></span>
          </div>
          <?php endforeach; ?>
          <?php if ($pending > 3) : ?>
            <a href="<?php echo admin_url('edit.php?post_type=studio_booking&meta_key=_status&meta_value=New'); ?>" style="color:var(--blue);font-size:0.85rem;text-decoration:none;">View all <?php echo $pending; ?> pending &rarr;</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent Bookings -->
    <div class="glass" style="margin-top:32px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">Recent Bookings</h3>
        <a href="<?php echo admin_url('edit.php?post_type=studio_booking'); ?>" style="color:var(--gold);font-size:0.85rem;text-decoration:none;">View all &rarr;</a>
      </div>
      <?php
      $recent = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => 5, 'orderby' => 'date', 'order' => 'DESC']);
      if ($recent) : ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Client</th><th>Service</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($recent as $bk) :
                $st = get_post_meta($bk->ID, '_status', true) ?: 'New';
                $sv = get_post_meta($bk->ID, '_service', true);
                $cls = strtolower(str_replace('-', '', $st));
              ?>
                <tr>
                  <td><a href="<?php echo get_edit_post_link($bk->ID); ?>"><?php echo esc_html($bk->post_title); ?></a></td>
                  <td><?php echo esc_html($sv); ?></td>
                  <td><span class="badge badge-<?php echo esc_attr($cls); ?>"><?php echo esc_html($st); ?></span></td>
                  <td><?php echo get_the_date('M j, Y', $bk->ID); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else : ?>
        <p style="color:var(--text-muted);">No bookings yet.</p>
      <?php endif; ?>
    </div>

    <!-- Upcoming Gigs -->
    <?php if (!empty($upcoming_gigs)) : ?>
    <div class="glass" style="margin-top:32px;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;">Upcoming Gigs</h3>
        <a href="<?php echo admin_url('edit.php?post_type=studio_gig'); ?>" style="color:var(--gold);font-size:0.85rem;text-decoration:none;">View all &rarr;</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Event</th><th>Venue</th><th>Date</th><th>Pay</th></tr></thead>
          <tbody>
            <?php foreach ($upcoming_gigs as $gig) :
              $venue = get_post_meta($gig->ID, '_gig_venue', true);
              $gig_date = get_post_meta($gig->ID, '_gig_date', true);
              $pay = get_post_meta($gig->ID, '_gig_pay', true);
            ?>
              <tr>
                <td><a href="<?php echo get_edit_post_link($gig->ID); ?>"><?php echo esc_html($gig->post_title); ?></a></td>
                <td><?php echo esc_html($venue); ?></td>
                <td><?php echo esc_html($gig_date ? date('M j, Y', strtotime($gig_date)) : '—'); ?></td>
                <td><?php echo $pay ? '$' . number_format(floatval($pay), 2) : '—'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>
<?php get_footer(); ?>
