<?php
/**
 * Template Name: Invoice Dashboard
 */
get_header();
if (!is_user_logged_in()) { wp_redirect(wp_login_url(get_permalink())); exit; }
if (!current_user_can('manage_options')) { echo '<div class="container" style="padding:120px 0;"><p>Restricted.</p></div>'; get_footer(); exit; }

?>
<main class="page-content">
  <div class="container">
    <div style="margin-bottom:32px;">
      <h1 style="font-size:1.6rem;">Invoice Overview</h1>
      <p style="color:var(--text-muted);">Review invoice status, payments, and financial activity.</p>
    </div>

    <p><a href="<?php echo esc_url(admin_url('post-new.php?post_type=studio_invoice')); ?>" class="btn btn-primary">Create Invoice in WordPress Admin</a></p>

    <!-- Recent Invoices -->
    <?php
    $recent_invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => 10, 'orderby' => 'date', 'order' => 'DESC']);
    ?>
    <div class="glass" style="padding:32px;margin-bottom:32px;">
      <h2>Invoices</h2>
      <?php if (empty($recent_invoices)) : ?>
        <p style="color:var(--text-muted);">No invoices yet.</p>
      <?php else : ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Client</th>
                <th>Gig Details</th>
                <th>Total</th>
                <th>Status</th>
                <th>Due</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_invoices as $inv) :
                  $status = get_post_meta($inv->ID, '_status', true) ?: 'Draft';
                  $total = floatval(get_post_meta($inv->ID, '_total', true));
                  $due = get_post_meta($inv->ID, '_due_date', true);
                  $client = get_post_meta($inv->ID, '_client_name', true);
                  $event_date = get_post_meta($inv->ID, '_event_date', true);
                  $venue = get_post_meta($inv->ID, '_venue', true);
                  $cls = strtolower(str_replace(['-', ''], '', $status));
              ?>
                <tr>
                  <td><?php echo $inv->ID; ?></td>
                  <td><a href="<?php echo get_edit_post_link($inv->ID); ?>"><?php echo esc_html($client ?: $inv->post_title); ?></a></td>
                  <td>
                    <?php if ($event_date) : ?>
                      <span style="color:var(--text-muted);"><?php echo date('M j, Y', strtotime($event_date)); ?></span>
                      <?php if ($venue) : ?><br><small><?php echo esc_html($venue); ?></small><?php endif; ?>
                    <?php else : ?>—<?php endif; ?>
                  </td>
                  <td>$<?php echo number_format($total, 2); ?></td>
                  <td><span class="badge badge-<?php echo esc_attr($cls ?: 'draft'); ?>"><?php echo esc_html($status); ?></span></td>
                  <td><?php echo esc_html($due ?: '—'); ?></td>
                  <td>
                    <a href="<?php echo get_edit_post_link($inv->ID); ?>">Edit</a>
                    <?php if ($status === 'Draft') : ?>
                      <?php $send_nonce = wp_create_nonce('studio_invoice_submit_' . $inv->ID); ?>
                      | <a href="<?php echo admin_url("admin-post.php?action=studio_invoice_quick_submit&post_id={$inv->ID}&_wpnonce=$send_nonce"); ?>">Send</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-4" style="margin-top:24px;">
      <a href="<?php echo admin_url('post-new.php?post_type=studio_expense'); ?>" class="btn btn-sm">Log Expense</a>
      <a href="<?php echo admin_url('edit.php?post_type=studio_booking'); ?>" class="btn btn-sm">Bookings</a>
      <a href="<?php echo home_url('/invoice-dashboard/#financials'); ?>" class="btn btn-sm">Financial Overview</a>
      <button id="studio-confirm-backup" class="btn btn-sm">Confirm Backup Today</button>
    </div>

    <!-- Backup status -->
    <?php
    $last_backup = get_option('studio_last_backup_confirmed');
    if ($last_backup) { ?>
      <p style="text-align:center;margin-top:20px;font-size:0.8rem;color:var(--green);"><span class="health-dot ok"></span> Backup last confirmed: <?php echo date('M j, Y \a\t g:i A', intval($last_backup)); ?></p>
    <?php } else { ?>
      <p style="text-align:center;margin-top:20px;font-size:0.8rem;color:var(--gold);"><span class="health-dot warn"></span> Backup never confirmed yet. Use "Confirm Backup Today" above after copying files to offsite storage.</p>
    <?php } ?>

    <!-- Financial Overview (scrollable section) -->
    <div id="financials" class="glass" style="padding:32px;margin-top:32px;">
      <?php echo do_shortcode('[studio_invoice_dashboard]'); ?>
    </div>
  </div>
</main>

<script type="text/javascript">
jQuery(document).ready(function($){
  var ajaxUrl = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;

  // Backup button
  $('#studio-confirm-backup').on('click', function(e){
    e.preventDefault();
    var btn = $(this);
    btn.text('Saving...');
    $.post(ajaxUrl, {action:'studio_confirm_backup', nonce:<?php echo json_encode(wp_create_nonce('studio_confirm_backup_ajax')); ?>}, function(r){ if(r.success) location.reload(); });
  });
});
</script>
<?php get_footer(); ?>

