<?php
/**
 * Template Name: Client Area
 */
get_header();

$booking = null;

// Token-based access (from email links)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = sanitize_text_field($_GET['token']);
    // Search all bookings for matching token
    $all = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1, 'meta_key' => '_access_token', 'meta_value' => $token]);
    if (!empty($all)) {
        $booking = $all[0];
    }
}

// Email + reference lookup
if (!$booking && isset($_GET['ref']) && isset($_GET['email'])) {
    $ref = sanitize_text_field($_GET['ref']);
    $email = sanitize_email($_GET['email']);
    $booking_id = intval(str_replace('#', '', $ref));

    if ($booking_id > 0) {
        $b = get_post($booking_id);
        if ($b && $b->post_type === 'studio_booking' && get_post_meta($b->ID, '_client_email', true) === $email) {
            $booking = $b;
        }
    }

    if (!$booking) {
        $q = new WP_Query(['post_type' => 'studio_booking', 'post_status' => 'publish', 'meta_query' => [['key' => '_client_email', 'value' => $email]], 'posts_per_page' => 1]);
        if ($q->have_posts()) { $booking = $q->posts[0]; }
        wp_reset_postdata();
    }
}
?>
<main class="page-content">
  <div class="container container-narrow">
    <div class="section-title">
      <h2>Client Area</h2>
      <p>Enter your email and booking reference to view your session details and invoice.</p>
    </div>

    <?php if (isset($_GET['error'])) : ?>
      <div class="alert alert-error">Invalid booking reference or email. Please check and try again.</div>
    <?php endif; ?>

    <?php if (!$booking) : ?>
    <div class="glass">
      <form method="get" action="<?php echo esc_url(get_permalink()); ?>">
        <div class="form-group">
          <label for="ref">Booking Reference</label>
          <input type="text" id="ref" name="ref" placeholder="e.g. #123" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="padding:16px;">View Booking</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($booking) :
        $status = get_post_meta($booking->ID, '_status', true) ?: 'Pending';
        $service = get_post_meta($booking->ID, '_service', true);
        $notes = get_post_meta($booking->ID, '_notes', true);
        $slots = get_post_meta($booking->ID, '_booking_slots', true);
        $total = get_post_meta($booking->ID, '_total_hours', true);
        $rate = get_post_meta($booking->ID, '_rate', true);
        $location = get_post_meta($booking->ID, '_location', true);
        $submitted = get_post_meta($booking->ID, '_submitted_at', true);
        $counter_rate = get_post_meta($booking->ID, '_counter_rate', true);
        $counter_hours = get_post_meta($booking->ID, '_counter_hours', true);
        $counter_message = get_post_meta($booking->ID, '_counter_message', true);
        $token_val = get_post_meta($booking->ID, '_access_token', true);
        $cls = strtolower(str_replace('-', '', $status));

        // Find linked invoice
        $invoice = null;
        $inv_q = new WP_Query(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => 1, 'meta_query' => [['key' => '_booking_id', 'value' => $booking->ID]]]);
        if ($inv_q->have_posts()) { $invoice = $inv_q->posts[0]; }
        wp_reset_postdata();
        $nonce_url_base = home_url('/');
    ?>
        <div class="glass" style="margin-top:32px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <p style="color:var(--gold);letter-spacing:2px;text-transform:uppercase;font-size:0.85rem;margin:0;">Booking #<?php echo $booking->ID; ?></p>
            <span class="badge badge-<?php echo esc_attr($cls); ?>"><?php echo esc_html($status); ?></span>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <div>
              <p style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Service</p>
              <p style="margin:0;font-weight:600;"><?php echo esc_html($service); ?></p>
            </div>
            <?php if ($rate) : ?>
            <div>
              <p style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Rate</p>
              <p style="margin:0;font-weight:600;">$<?php echo esc_html($rate); ?>/hr</p>
            </div>
            <?php endif; ?>
            <?php if ($total) : ?>
            <div>
              <p style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Total Hours</p>
              <p style="margin:0;font-weight:600;"><?php echo esc_html($total); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($location) : ?>
            <div>
              <p style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 4px;">Location</p>
              <p style="margin:0;font-weight:600;"><?php echo esc_html($location); ?></p>
            </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($slots) && is_array($slots)) : ?>
            <h4 style="margin:24px 0 12px;color:var(--gold);font-size:0.85rem;letter-spacing:2px;text-transform:uppercase;">Scheduled Sessions</h4>
            <?php foreach ($slots as $s) : ?>
              <div style="background:rgba(255,255,255,0.03);padding:16px 20px;border-radius:var(--radius-sm);margin-bottom:8px;border-left:3px solid var(--gold);">
                <p style="margin:0;font-weight:600;"><?php echo esc_html($s['date'] ?? ''); ?></p>
                <p style="margin:4px 0 0;color:var(--text-muted);font-size:0.9rem;"><?php echo esc_html(($s['start'] ?? '') . ' to ' . ($s['end'] ?? '')); ?><?php if (!empty($s['hours'])) : ?> &mdash; <?php echo esc_html($s['hours']); ?> hrs<?php endif; ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if ($notes) : ?>
            <div style="margin-top:20px;padding:16px 20px;background:rgba(255,255,255,0.02);border-radius:var(--radius-sm);">
              <p style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin:0 0 8px;">Project Notes</p>
              <p style="margin:0;line-height:1.7;"><?php echo esc_html($notes); ?></p>
            </div>
          <?php endif; ?>

          <?php if ($submitted) : ?>
            <p style="color:var(--text-muted);font-size:0.8rem;margin-top:20px;">Submitted <?php echo esc_html(date('M j, Y \a\t g:i A', strtotime($submitted))); ?></p>
          <?php endif; ?>
        </div>

        <?php if ($status === 'Counter-Offer' && $counter_rate) :
            $accept_url = wp_nonce_url(
                add_query_arg(['action' => 'accept_counter', 'booking_id' => $booking->ID, 'token' => $token_val], home_url('/')),
                'studio_booking_action_' . $booking->ID
            );
            $decline_url = wp_nonce_url(
                add_query_arg(['action' => 'decline_counter', 'booking_id' => $booking->ID, 'token' => $token_val], home_url('/')),
                'studio_booking_action_' . $booking->ID
            );
        ?>
        <div class="glass" style="margin-top:24px;border-color:rgba(74,158,255,0.3);">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <span style="color:var(--blue);font-size:1.2rem;">&#9998;</span>
            <h3 style="margin:0;color:var(--blue);font-size:1rem;letter-spacing:1px;text-transform:uppercase;">Counter Offer</h3>
          </div>
          <?php if ($counter_message) : ?>
            <p style="margin:0 0 16px;line-height:1.7;"><?php echo esc_html($counter_message); ?></p>
          <?php endif; ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
            <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
              <p style="color:var(--text-muted);font-size:0.75rem;margin:0 0 4px;text-transform:uppercase;">New Rate</p>
              <p style="margin:0;font-weight:600;color:var(--gold);">$<?php echo esc_html($counter_rate); ?>/hr</p>
            </div>
            <?php if ($counter_hours) : ?>
            <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
              <p style="color:var(--text-muted);font-size:0.75rem;margin:0 0 4px;text-transform:uppercase;">Estimated Hours</p>
              <p style="margin:0;font-weight:600;"><?php echo esc_html($counter_hours); ?></p>
            </div>
            <?php endif; ?>
          </div>
          <div style="display:flex;gap:12px;">
            <a href="<?php echo esc_url($accept_url); ?>" class="btn btn-success btn-block">Accept Offer</a>
            <a href="<?php echo esc_url($decline_url); ?>" class="btn btn-danger btn-block">Decline</a>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($invoice && $status === 'Approved') :
            $inv_total = floatval(get_post_meta($invoice->ID, '_total', true));
            $inv_deposit = floatval(get_post_meta($invoice->ID, '_deposit', true));
            $inv_deposit_paid = get_post_meta($invoice->ID, '_deposit_paid', true);
            $inv_status = get_post_meta($invoice->ID, '_status', true) ?: 'Draft';
            $inv_due = get_post_meta($invoice->ID, '_due_date', true);
            $pay_url = add_query_arg(['action' => 'pay_deposit', 'invoice_id' => $invoice->ID, 'token' => $token_val], home_url('/'));
        ?>
        <div class="glass" style="margin-top:24px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;font-size:1rem;letter-spacing:1px;text-transform:uppercase;color:var(--gold);">Invoice</h3>
            <span class="badge badge-<?php echo esc_attr(strtolower($inv_status)); ?>"><?php echo esc_html($inv_status); ?></span>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
              <p style="color:var(--text-muted);font-size:0.75rem;margin:0 0 4px;text-transform:uppercase;">Total</p>
              <p style="margin:0;font-weight:600;font-size:1.2rem;">$<?php echo number_format($inv_total, 2); ?></p>
            </div>
            <div style="padding:12px 16px;background:rgba(255,255,255,0.03);border-radius:var(--radius-sm);">
              <p style="color:var(--text-muted);font-size:0.75rem;margin:0 0 4px;text-transform:uppercase;">Deposit</p>
              <p style="margin:0;font-weight:600;font-size:1.2rem;">$<?php echo number_format($inv_deposit, 2); ?></p>
            </div>
          </div>

          <?php if ($inv_due) : ?>
            <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:16px;">Due by <?php echo esc_html(date('M j, Y', strtotime($inv_due))); ?></p>
          <?php endif; ?>

          <?php if (!$inv_deposit_paid && $inv_status !== 'Paid') : ?>
            <a href="<?php echo esc_url($pay_url); ?>" class="btn btn-primary btn-block" style="padding:16px;">Pay Deposit via PayPal</a>
          <?php else : ?>
            <div style="text-align:center;padding:12px;background:rgba(40,167,69,0.1);border-radius:var(--radius-sm);border:1px solid rgba(40,167,69,0.3);">
              <span style="color:var(--green);font-weight:600;">Deposit Paid</span>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php elseif ($booking_id > 0 || $email) : ?>
      <div class="alert alert-error" style="margin-top:32px;">No matching booking found. Please check your reference and email.</div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
