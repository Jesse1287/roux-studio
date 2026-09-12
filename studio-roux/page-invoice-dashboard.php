<?php
/**
 * Template Name: Invoice Dashboard
 */
get_header();
if (!is_user_logged_in()) { wp_redirect(wp_login_url(get_permalink())); exit; }
if (!current_user_can('manage_options')) { echo '<div class="container" style="padding:120px 0;"><p>Restricted.</p></div>'; get_footer(); exit; }

$status_msg = '';
$status_type = '';

if (isset($_GET['invoice_created']) && $_GET['invoice_created']) {
    $status_msg = 'Invoice #' . intval($_GET['invoice_created']) . ' created successfully.';
    $status_type = 'success';
}
if (isset($_GET['invoice_error']) && $_GET['invoice_error']) {
    $status_msg = 'Error creating invoice. Please check your input and try again.';
    $status_type = 'error';
}

$bookings = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']);
$gigs = get_posts(['post_type' => 'studio_gig', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'meta_value', 'meta_key' => '_gig_date', 'order' => 'ASC']);
?>
<main class="page-content">
  <div class="container">
    <div style="margin-bottom:32px;">
      <h1 style="font-size:1.6rem;">Invoice Creator</h1>
      <p style="color:var(--text-muted);">Create invoices with gig details, line items, and payment tracking.</p>
    </div>

    <?php if ($status_msg) : ?>
      <div class="alert alert-<?php echo esc_attr($status_type); ?>"><?php echo esc_html($status_msg); ?></div>
    <?php endif; ?>

    <!-- Create Invoice Form -->
    <div class="glass" style="padding:32px;margin-bottom:32px;">
      <h2 style="margin-top:0;margin-bottom:20px;">New Invoice</h2>
      <form method="post" action="" id="studio-invoice-form">
        <?php wp_nonce_field('studio_create_invoice_frontend', '_invoice_frontend_nonce'); ?>
        <input type="hidden" name="studio_create_invoice_frontend" value="1">

        <!-- Link to existing record -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
          <div class="form-group">
            <label for="link_booking">Link Booking</label>
            <select id="link_booking" name="booking_id">
              <option value="0">— None (Standalone) —</option>
              <?php foreach ($bookings as $bk) : ?>
                <option value="<?php echo $bk->ID; ?>"><?php echo esc_html("#{$bk->ID} - {$bk->post_title} (" . get_post_meta($bk->ID, '_service', true) . ")"); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="link_gig">Link Gig</label>
            <select id="link_gig" name="gig_id">
              <option value="0">— None (Standalone) —</option>
              <?php foreach ($gigs as $g) : ?>
                <option value="<?php echo $g->ID; ?>"><?php echo esc_html("#{$g->ID} - {$g->post_title} (" . get_post_meta($g->ID, '_gig_venue', true) . ")"); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Client Info -->
        <h3 style="font-size:0.85rem;text-transform:uppercase;letter-spacing:2px;color:var(--gold);margin-bottom:16px;">Client Information</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
          <div class="form-group">
            <label for="client_name">Full Name *</label>
            <input type="text" id="client_name" name="client_name" required placeholder="Client name">
          </div>
          <div class="form-group">
            <label for="client_email">Email *</label>
            <input type="email" id="client_email" name="client_email" required placeholder="you@example.com">
          </div>
          <div class="form-group">
            <label for="client_phone">Phone</label>
            <input type="tel" id="client_phone" name="client_phone" placeholder="(555) 555-5555">
          </div>
        </div>

        <!-- Gig / Event Details -->
        <h3 style="font-size:0.85rem;text-transform:uppercase;letter-spacing:2px;color:var(--gold);margin-bottom:16px;">Gig / Event Details</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
          <div class="form-group">
            <label for="event_date">Event Date *</label>
            <input type="date" id="event_date" name="event_date" required>
          </div>
          <div class="form-group">
            <label for="start_time">Start Time</label>
            <input type="time" id="start_time" name="start_time">
          </div>
          <div class="form-group">
            <label for="end_time">End Time</label>
            <input type="time" id="end_time" name="end_time">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
          <div class="form-group">
            <label for="venue">Venue / Location</label>
            <input type="text" id="venue" name="venue" placeholder="Venue or studio address">
          </div>
          <div class="form-group">
            <label for="service_type">Service Type</label>
            <select id="service_type" name="service_type">
              <option value="">Select service...</option>
              <option value="FOH Engineer">FOH Engineer - Live Sound</option>
              <option value="Recording">Recording</option>
              <option value="Mixing">Mixing</option>
              <option value="Mixing + Mastering">Mixing + Mastering</option>
              <option value="Full Production">Full Production</option>
              <option value="Monitor Engineer">Monitor Engineer</option>
              <option value="Sound Design">Sound Design</option>
              <option value="Consultation">Consultation</option>
              <option value="Travel/Transport">Travel / Transport</option>
              <option value="Equipment Rental">Equipment Rental</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>

        <!-- Line Items -->
        <h3 style="font-size:0.85rem;text-transform:uppercase;letter-spacing:2px;color:var(--gold);margin-bottom:16px;">Line Items</h3>
        <div id="line-item-container">
          <div class="line-item-row" style="display:grid;grid-template-columns:3fr 1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:end;">
            <div class="form-group" style="margin:0;">
              <label for="li_desc_0">Description</label>
              <input type="text" id="li_desc_0" name="line_item_desc[]" placeholder="e.g. 4hr FOH mixing" class="line-item-desc" required style="width:100%;">
            </div>
            <div class="form-group" style="margin:0;">
              <label for="li_qty_0">Qty/Hours</label>
              <input type="number" step="0.25" min="0" id="li_qty_0" name="line_item_qty[]" value="1" class="line-item-qty" required style="width:100%;">
            </div>
            <div class="form-group" style="margin:0;">
              <label for="li_rate_0">Rate ($)</label>
              <input type="number" step="0.01" min="0" id="li_rate_0" name="line_item_rate[]" value="" class="line-item-rate" required style="width:100%;">
            </div>
            <div class="form-group" style="margin:0;">
              <label for="li_total_0">Total ($)</label>
              <input type="text" id="li_total_0" name="line_item_total[]" readonly class="line-item-total" style="width:100%;background:rgba(255,255,255,0.03);color:var(--gold);">
            </div>
            <button type="button" class="li-remove-btn" style="margin-bottom:2px;display:none;">&times;</button>
          </div>
        </div>
        <button type="button" id="add-line-item" class="btn btn-sm" style="margin-top:8px;">+ Add Line Item</button>

        <!-- Totals -->
        <div style="text-align:right;margin-top:24px;padding-top:16px;border-top:1px solid var(--surface-border);">
          <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:12px;">
            <label for="invoice_discount" style="align-self:center;color:var(--text-muted);">Discount:</label>
            <input type="number" step="0.01" min="0" id="invoice_discount" name="discount" value="0" style="width:80px;padding:6px 10px;background:rgba(255,255,255,0.05);border:1px solid var(--surface-border);border-radius:4px;color:#e0e0e0;">
            <select id="invoice_discount_type" name="discount_type" style="padding:6px 10px;background:rgba(255,255,255,0.05);border:1px solid var(--surface-border);border-radius:4px;color:#e0e0e0;">
              <option value="percent">%</option>
              <option value="fixed">$</option>
            </select>
          </div>

          <div style="display:flex;gap:8px;justify-content:flex-end;margin-bottom:12px;">
            <label for="invoice_tax_rate" style="align-self:center;color:var(--text-muted);">Tax Rate (%):</label>
            <input type="number" step="0.001" min="0" id="invoice_tax_rate" name="tax_rate" value="0" style="width:80px;padding:6px 10px;background:rgba(255,255,255,0.05);border:1px solid var(--surface-border);border-radius:4px;color:#e0e0e0;">
            <input type="text" id="invoice_tax_jurisdiction" name="tax_jurisdiction" placeholder="Jurisdiction" style="width:120px;padding:6px 10px;background:rgba(255,255,255,0.05);border:1px solid var(--surface-border);border-radius:4px;color:#e0e0e0;">
          </div>

          <div style="display:flex;justify-content:flex-end;gap:32px;margin-bottom:8px;">
            <span style="color:var(--text-muted);">Subtotal:</span>
            <strong id="subtotal-display">$0.00</strong>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:32px;margin-bottom:8px;">
            <span style="color:var(--text-muted);">Discount:</span>
            <span id="discount-display" style="color:var(--gold);">$0.00</span>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:32px;margin-bottom:8px;">
            <span style="color:var(--text-muted);">Tax:</span>
            <span id="tax-display" style="color:var(--blue);">$0.00</span>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:32px;font-size:1.2rem;padding-top:8px;border-top:1px solid var(--surface-border);">
            <strong>Total Due:</strong>
            <strong id="grand-total-display" style="color:var(--green);">$0.00</strong>
          </div>
        </div>

        <!-- Additional Fields -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:24px;">
          <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="date" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
          </div>
          <div class="form-group">
            <label for="invoice_status">Status</label>
            <select id="invoice_status" name="invoice_status">
              <option value="Draft">Draft</option>
              <option value="Sent">Sent</option>
              <option value="Paid">Paid</option>
            </select>
          </div>
        </div>
        <div class="form-group" style="margin-top:16px;">
          <label for="invoice_notes">Notes</label>
          <textarea id="invoice_notes" name="notes" rows="3" placeholder="Payment terms, PO number, etc."></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="padding:16px;margin-top:24px;">Create Invoice</button>
      </form>
    </div>

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

  // Line item management
  var liIndex = 1;

  function recalcTotals(){
    var subtotal = 0;
    $('.line-item-row').each(function(){
      var qty = parseFloat($(this).find('.line-item-qty').val()) || 0;
      var rate = parseFloat($(this).find('.line-item-rate').val()) || 0;
      var lineTotal = qty * rate;
      $(this).find('.line-item-total').val(lineTotal.toFixed(2));
      subtotal += lineTotal;
    });

    var discountVal = parseFloat($('#invoice_discount').val()) || 0;
    var discountType = $('#invoice_discount_type').val();
    var discountAmt = (discountType === 'percent') ? subtotal * (discountVal / 100) : discountVal;
    var afterDiscount = Math.max(0, subtotal - discountAmt);

    var taxRate = parseFloat($('#invoice_tax_rate').val()) || 0;
    var taxAmt = afterDiscount * (taxRate / 100);

    var grandTotal = afterDiscount + taxAmt;

    $('#subtotal-display').text('$' + subtotal.toFixed(2));
    $('#discount-display').text('-$' + discountAmt.toFixed(2));
    $('#tax-display').text('$' + taxAmt.toFixed(2));
    $('#grand-total-display').text('$' + grandTotal.toFixed(2));
  }

  // Init first row
  recalcTotals();

  // Add line item
  $('#add-line-item').on('click', function(){
    var container = $('#line-item-container');
    var idx = liIndex++;
    var html = '<div class="line-item-row" style="display:grid;grid-template-columns:3fr 1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:end;">';
    html += '<div class="form-group" style="margin:0;"><label> Description</label>';
    html += '<input type="text" name="line_item_desc[]" placeholder="Description" class="line-item-desc" required style="width:100%;"></div>';
    html += '<div class="form-group" style="margin:0;"><label> Qty/Hours</label>';
    html += '<input type="number" step="0.25" min="0" name="line_item_qty[]" value="1" class="line-item-qty" required style="width:100%;"></div>';
    html += '<div class="form-group" style="margin:0;"><label> Rate ($)</label>';
    html += '<input type="number" step="0.01" min="0" name="line_item_rate[]" value="" class="line-item-rate" required style="width:100%;"></div>';
    html += '<div class="form-group" style="margin:0;"><label> Total ($)</label>';
    html += '<input type="text" name="line_item_total[]" readonly class="line-item-total" style="width:100%;background:rgba(255,255,255,0.03);color:var(--gold);"></div>';
    html += '<button type="button" class="li-remove-btn" style="margin-bottom:2px;">&times;</button></div>';
    container.append(html);

    var newRow = container.find('.line-item-row').last();
    newRow.find('input').on('input', recalcTotals);
    newRow.find('.li-remove-btn').on('click', function(){
      if(container.find('.line-item-row').length > 1){
        newRow.remove();
        recalcTotals();
      }
    });
  });

  // Recalc on input change
  $(document).on('input', '.line-item-qty, .line-item-rate, #invoice_discount, #invoice_tax_rate', function(){
    recalcTotals();
  });
  $('#invoice_discount_type').on('change', recalcTotals);

  // Autopopulate from booking selection
  $('#link_booking').on('change', function(){
    var val = $(this).val();
    if(val === '0') return;
    $.post(ajaxUrl, {action:'studio_invoice_autopopulate_booking', id: val, nonce:<?php echo json_encode(wp_create_nonce('studio_autopopulate')); ?>}, function(r){
      if(r.success && r.data){
        if(r.data.client_name) $('#client_name').val(r.data.client_name);
        if(r.data.client_email) $('#client_email').val(r.data.client_email);
        if(r.data.client_phone) $('#client_phone').val(r.data.client_phone);
        if(r.data.event_date) $('#event_date').val(r.data.event_date);
        if(r.data.start_time) $('#start_time').val(r.data.start_time);
        if(r.data.end_time) $('#end_time').val(r.data.end_time);
        if(r.data.venue) $('#venue').val(r.data.venue);
        if(r.data.service_type) $('#service_type').val(r.data.service_type);
      }
    }, 'json');
  });

  // Autopopulate from gig selection
  $('#link_gig').on('change', function(){
    var val = $(this).val();
    if(val === '0') return;
    $.post(ajaxUrl, {action:'studio_invoice_autopopulate_gig', id: val, nonce:<?php echo json_encode(wp_create_nonce('studio_autopopulate')); ?>}, function(r){
      if(r.success && r.data){
        if(r.data.event_date) $('#event_date').val(r.data.event_date);
        if(r.data.start_time) $('#start_time').val(r.data.start_time);
        if(r.data.end_time) $('#end_time').val(r.data.end_time);
        if(r.data.venue) $('#venue').val(r.data.venue);
      }
    }, 'json');
  });
});
</script>
<?php get_footer(); ?>

