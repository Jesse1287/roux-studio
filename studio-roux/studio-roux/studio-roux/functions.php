<?php
update_option('timezone_string', 'America/Chicago');
add_theme_support('title-tag');
add_theme_support('post-thumbnails');
add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
add_theme_support('customize-selective-refresh-widgets');

register_nav_menus(['primary' => 'Primary Navigation']);

/* ----------------------------------------------
   SERVICE DEFINITIONS (machine-readable slugs)
   ---------------------------------------------- */
function studio_service_rates() {
    return [
        'recording'    => ['label' => 'Tier 1 - Recording',           'rate' => 75,  'hourly' => true,  'desc' => '$75/hr'],
        'mixing'       => ['label' => 'Tier 2 - Mixing',              'rate' => 150, 'hourly' => false, 'desc' => '$150 flat'],
        'mix-master'   => ['label' => 'Tier 3 - Mixing + Mastering',  'rate' => 200, 'hourly' => false, 'desc' => '$200 flat'],
        'full-prod'    => ['label' => 'Tier 4 - Full Production',     'rate' => 250, 'hourly' => false, 'desc' => '$250 flat'],
        'foh'          => ['label' => 'FOH Engineer - Live Sound',     'rate' => 50,  'hourly' => true,  'desc' => '$50/hr'],
        'consultation' => ['label' => 'Consultation',                  'rate' => 0,   'hourly' => false, 'desc' => 'Free'],
    ];
}

function studio_rate_for_service($service) {
    $rates = studio_service_rates();
    foreach ($rates as $key => $info) {
        if (strpos($service, $info['label']) !== false || strpos($service, $key) !== false) {
            return $info;
        }
    }
    return ['rate' => 75, 'hourly' => true, 'label' => $service, 'desc' => '$75/hr'];
}

/* ----------------------------------------------
   TOKEN GENERATION (for client-facing actions)
   ---------------------------------------------- */
function studio_generate_token($post_id) {
    $token = wp_generate_password(32, false);
    update_post_meta($post_id, '_access_token', $token);
    return $token;
}

function studio_verify_token($post_id, $token) {
    $stored = get_post_meta($post_id, '_access_token', true);
    return $stored && hash_equals($stored, $token);
}

/* ----------------------------------------------
   ENQUEUE SCRIPTS & STYLES
   ---------------------------------------------- */
function studio_scripts() {
    wp_enqueue_style('studio-roux', get_stylesheet_uri(), [], '3.3.0');

    if (is_page_template('page-booking.php')) {
        wp_enqueue_script('studio-booking', '', [], '1.0', true);
        $booking_js = '
(function(){
  var svc = document.getElementById("service");
  if(!svc) return;
  svc.addEventListener("change", function(){
    var opt = this.options[this.selectedIndex];
    var h = opt && opt.getAttribute("data-hourly") === "true";
    var slots = document.getElementById("hourly-slots");
    if(slots) slots.style.display = h ? "block" : "none";
  });
  var addBtn = document.getElementById("add-slot-btn");
  if(addBtn) addBtn.addEventListener("click", function(){
    var c = document.getElementById("slot-container");
    var r = document.createElement("div");
    r.className = "slot-row";
    r.innerHTML = "<input type=\"date\" name=\"slot_date[]\" required><input type=\"time\" name=\"slot_start[]\" placeholder=\"Start\" required><input type=\"time\" name=\"slot_end[]\" placeholder=\"End\" required>";
    c.appendChild(r);
  });
  var form = document.querySelector("form");
  if(form) form.addEventListener("submit", function(e){
    var slots = document.querySelectorAll(".slot-row");
    for(var i=0;i<slots.length;i++){
      var st = slots[i].querySelector("input[name*=slot_start]");
      var en = slots[i].querySelector("input[name*=slot_end]");
      if(st && en && st.value && en.value && en.value <= st.value){
        e.preventDefault();
        alert("End time must be after start time.");
        return;
      }
    }
  });
  });
})();';
        wp_add_inline_script('studio-booking', $booking_js);
    }
}
add_action('wp_enqueue_scripts', 'studio_scripts');

function studio_admin_css() {
  echo '<style>
    .roux-admin-dashboard{display:grid;gap:12px;margin-top:12px}
    .roux-admin-dashboard .rstat{display:flex;align-items:center;gap:12px;padding:10px 14px;background:#1d1d1d;border:1px solid #333;border-radius:8px}
    .roux-admin-dashboard .rstat-icon{width:32px;height:32px;border-radius:6px;background:rgba(212,165,116,0.15);display:flex;align-items:center;justify-content:center;color:#d4a574;font-size:0.9rem}
    .roux-admin-dashboard .rstat-val{font-size:1.2rem;font-weight:700;color:#d4a574}
    .roux-admin-dashboard .rstat-label{font-size:0.7rem;color:#999;text-transform:uppercase;letter-spacing:1px}
    .roux-health-list{list-style:none;margin:0;padding:0}
    .roux-health-list li{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #333;font-size:0.85rem}
    .roux-health-list li:last-child{border-bottom:none}
  </style>';
}
add_action('admin_head', 'studio_admin_css');

function studio_email_html($body_html, $title = '') {
    return '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#0a0a0a;font-family:Georgia,serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 20px;">
<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;">
<tr>
<td style="background:#1a1a1a;border-radius:12px 12px 0 0;padding:32px 40px 20px;text-align:center;border-bottom:2px solid #d4a574;">
<h1 style="margin:0;font-family:Georgia,serif;font-size:20px;color:#d4a574;letter-spacing:3px;font-weight:700;">ROUX\'S AUDIO<br>PRODUCTION</h1>
' . ($title ? '<p style="margin:10px 0 0;font-size:13px;color:#888;font-family:Arial,sans-serif;">' . $title . '</p>' : '') . '
</td>
</tr>
<tr>
<td style="background:#1a1a1a;padding:32px 40px;border-radius:0 0 12px 12px;font-size:15px;line-height:1.7;color:#ddd;font-family:Arial,sans-serif;">
' . $body_html . '
</td>
</tr>
<tr>
<td style="padding:24px 40px 0;text-align:center;">
<p style="margin:0;font-size:12px;color:#555;font-family:Arial,sans-serif;">Roux\'s Audio Production</p>
<p style="margin:4px 0 0;font-size:12px;color:#555;font-family:Arial,sans-serif;"><a href="https://rouxsaudioproduction.us" style="color:#d4a574;text-decoration:none;">rouxsaudioproduction.us</a></p>
</td>
</tr>
</table>
</td></tr></table>
</body>
</html>';
}

function studio_log_error($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    error_log("[$timestamp] STUDIO_ERROR: $message$context_str\n");
}

function studio_send_email($to, $subject, $body_html) {
    return wp_mail($to, $subject, studio_email_html($body_html), ['Content-Type: text/html; charset=UTF-8']);
}

function studio_badge_html($status) {
    $cls = strtolower(str_replace(' ', '-', $status));
    $map = ['new' => 'badge-new', 'approved' => 'badge-approved', 'declined' => 'badge-declined',
            'sent' => 'badge-sent', 'paid' => 'badge-paid', 'draft' => 'badge-draft',
            'counter-offer' => 'badge-new', 'partial' => 'badge-sent'];
    $class = $map[$cls] ?? 'badge-draft';
    return '<span class="' . $class . '">' . esc_html($status) . '</span>';
}

/* ----------------------------------------------
   CRON SCHEDULING
   ---------------------------------------------- */
if (!wp_next_scheduled('studio_daily_reminder_event')) {
    wp_schedule_event(time(), 'daily', 'studio_daily_reminder_event');
}
add_action('studio_daily_reminder_event', 'studio_send_payment_reminders');

if (!wp_next_scheduled('studio_session_reminder_event')) {
    wp_schedule_event(time(), 'daily', 'studio_session_reminder_event');
}
add_action('studio_session_reminder_event', 'studio_send_session_reminders');

if (!wp_next_scheduled('studio_health_check_event')) {
    wp_schedule_event(time(), 'hourly', 'studio_health_check_event');
}
add_action('studio_health_check_event', 'studio_run_health_check');

if (!wp_next_scheduled('studio_maintenance_reminder_event')) {
    wp_schedule_event(time(), 'daily', 'studio_maintenance_reminder_event');
}
add_action('studio_maintenance_reminder_event', 'studio_send_maintenance_reminders');

/* ----------------------------------------------
   BOOKING FORM HANDLER
   ---------------------------------------------- */
function studio_booking_handler() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['studio_booking'])) return;

    // Verify nonce
    if (!isset($_POST['_booking_nonce']) || !wp_verify_nonce($_POST['_booking_nonce'], 'studio_booking_submit')) {
        wp_safe_redirect(add_query_arg('booking', 'error', home_url('/booking/')));
        exit;
    }

    // Rate limiting: max 3 submissions per IP per hour
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'studio_booking_rate_' . md5($ip);
    $count = intval(get_transient($rate_key) ?: 0);
    if ($count >= 3) {
        wp_safe_redirect(add_query_arg('booking', 'error', home_url('/booking/')));
        exit;
    }
    set_transient($rate_key, $count + 1, HOUR_IN_SECONDS);

    // Server-side Turnstile verification
    if (defined('STUDIO_TURNSTILE_SECRET') && !empty(STUDIO_TURNSTILE_SECRET)) {
        $turnstile_response = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');
        if (empty($turnstile_response)) {
            wp_safe_redirect(add_query_arg('booking', 'error', home_url('/booking/')));
            exit;
        }
        $verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret'   => STUDIO_TURNSTILE_SECRET,
                'response' => $turnstile_response,
                'remoteip' => $ip,
            ],
        ]);
        $result = json_decode(wp_remote_retrieve_body($verify), true);
        if (empty($result['success'])) {
            wp_safe_redirect(add_query_arg('booking', 'error', home_url('/booking/')));
            exit;
        }
    }

    $name     = sanitize_text_field($_POST['name'] ?? '');
    $email    = sanitize_email($_POST['email'] ?? '');
    $phone    = sanitize_text_field($_POST['phone'] ?? '');
    $service  = sanitize_text_field($_POST['service'] ?? '');
    $notes    = sanitize_textarea_field($_POST['notes'] ?? '');
    $location = sanitize_text_field($_POST['location'] ?? '');

    $rate_info = studio_rate_for_service($service);
    $is_hourly = $rate_info['hourly'];

    $slot_dates  = array_map('sanitize_text_field', $_POST['slot_date'] ?? []);
    $slot_starts = array_map('sanitize_text_field', $_POST['slot_start'] ?? []);
    $slot_ends   = array_map('sanitize_text_field', $_POST['slot_end'] ?? []);

    $slots = [];
    $total_hours = 0;

    if ($is_hourly) {
        foreach ($slot_dates as $i => $date) {
            if (empty($date)) continue;
            $slot = ['date' => $date, 'start' => $slot_starts[$i] ?? '', 'end' => $slot_ends[$i] ?? ''];
            if ($slot['start'] && $slot['end']) {
                $sp = explode(':', $slot['start']);
                $ep = explode(':', $slot['end']);
                $diff = ($ep[0] * 60 + $ep[1]) - ($sp[0] * 60 + $sp[1]);
                if ($diff <= 0) continue;
                $slot['hours'] = round($diff / 60 * 4) / 4;
                $total_hours += $slot['hours'];
            }
            $slots[] = $slot;
        }
    }

    $post_id = wp_insert_post([
        'post_type'   => 'studio_booking',
        'post_title'  => $name,
        'post_status' => 'publish',
    ]);

    if ($post_id) {
        $token = studio_generate_token($post_id);
        update_post_meta($post_id, '_status', 'New');
        update_post_meta($post_id, '_client_email', $email);
        update_post_meta($post_id, '_client_phone', $phone);
        update_post_meta($post_id, '_service', $service);
        update_post_meta($post_id, '_notes', $notes);
        update_post_meta($post_id, '_location', $location);
        update_post_meta($post_id, '_total_hours', $total_hours);
        update_post_meta($post_id, '_booking_slots', $slots);
        update_post_meta($post_id, '_submitted_at', current_time('mysql'));

        studio_send_email(get_option('admin_email'), "New Booking Request from $name",
            '<p>New booking request received for <strong>' . esc_html($service) . '</strong>.</p>' .
            '<p><strong>Client:</strong> ' . esc_html($name) . '</p>' .
            '<p><strong>Email:</strong> ' . esc_html($email) . '</p>' .
            ($phone ? '<p><strong>Phone:</strong> ' . esc_html($phone) . '</p>' : '') .
            ($total_hours ? '<p><strong>Hours:</strong> ' . $total_hours . '</p>' : '') .
            ($notes ? '<p><strong>Notes:</strong> ' . esc_html($notes) . '</p>' : ''));

        studio_send_email($email, "Booking Request Received",
            '<p>Hi ' . esc_html($name) . ', we have received your booking request for <strong>' . esc_html($service) . '</strong>.</p><p>We will review it within 24 hours.</p>');

        wp_safe_redirect(add_query_arg('booking', 'sent', home_url('/booking/')));
    }
    exit;
}
add_action('init', 'studio_booking_handler');

/* ----------------------------------------------
   BOOKING ADMIN ACTIONS (with nonce verification)
   ---------------------------------------------- */
function studio_booking_action() {
    if (!current_user_can('manage_options')) return;

    if (isset($_GET['studio_booking_approve'])) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'studio_booking_approve_' . intval($_GET['studio_booking_approve']))) {
            wp_die('Security check failed');
        }
        $id = intval($_GET['studio_booking_approve']);
        if (isset($_GET['co_rate']) && isset($_GET['co_hours'])) {
            $co_rate = floatval($_GET['co_rate']);
            $co_hours = floatval($_GET['co_hours']);
            update_post_meta($id, '_total_hours', $co_hours);
            studio_do_approve($id, 0, 'percent', $co_rate);
        } else {
            studio_do_approve($id, 0, 'percent');
        }
        wp_redirect(admin_url('edit.php?post_type=studio_booking'));
        exit;
    }

    if (isset($_GET['studio_booking_decline'])) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'studio_booking_decline_' . intval($_GET['studio_booking_decline']))) {
            wp_die('Security check failed');
        }
        update_post_meta(intval($_GET['studio_booking_decline']), '_status', 'Declined');
        wp_redirect(admin_url('edit.php?post_type=studio_booking'));
        exit;
    }
}
add_action('admin_init', 'studio_booking_action');

function studio_counter_offer_meta_callback($post) {
    $status = get_post_meta($post->ID, '_status', true) ?: 'New';
    if ($status === 'Approved' || $status === 'Declined') return;
    ?>
    <p style="font-size:12px;color:#666;margin-bottom:12px;">Send a counter offer to the client before approving.</p>
    <p><label>Counter Rate ($/hr): <input type="number" step="0.01" name="co_rate" value="<?php echo esc_attr(get_post_meta($post->ID, '_counter_rate', true) ?: '75'); ?>" style="width:100%;"></label></p>
    <p><label>Counter Hours: <input type="number" step="0.25" name="co_hours" value="<?php echo esc_attr(get_post_meta($post->ID, '_counter_hours', true) ?: get_post_meta($post->ID, '_total_hours', true)); ?>" style="width:100%;"></label></p>
    <p><label>Note to Client: <textarea name="co_message" rows="3" style="width:100%;"><?php echo esc_textarea(get_post_meta($post->ID, '_counter_message', true)); ?></textarea></label></p>
    <?php
    wp_nonce_field('studio_counter_offer', '_co_nonce');
}

/* ----------------------------------------------
   INVOICE SYSTEM
   ---------------------------------------------- */
function studio_invoice_cpt() {
    register_post_type('studio_invoice', [
        'labels' => ['name' => 'Invoices', 'singular_name' => 'Invoice', 'menu_name' => 'Invoices'],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-money-alt',
        'supports' => ['title', 'editor', 'custom-fields'],
    ]);
}
add_action('init', 'studio_invoice_cpt');

function studio_invoice_adjustments_callback($post) {
    $adjustments = get_post_meta($post->ID, '_adjustments', true);
    if (!is_array($adjustments)) $adjustments = [];
    if (!empty($adjustments)) {
        echo '<h3>Adjustments</h3>';
        foreach ($adjustments as $adj) {
            echo '<p>' . esc_html($adj['desc']) . ': $' . esc_html($adj['amount']) . '</p>';
        }
    }

    $status = get_post_meta($post->ID, '_status', true) ?: 'Draft';
    $total = floatval(get_post_meta($post->ID, '_total', true));
    $deposit = floatval(get_post_meta($post->ID, '_deposit', true));
    $deposit_paid = floatval(get_post_meta($post->ID, '_deposit_paid', true));
    $due = get_post_meta($post->ID, '_due_date', true);

    echo '<div style="margin-top:16px;padding-top:12px;border-top:1px solid #ddd;">';
    echo '<p><strong>Status:</strong> ' . esc_html($status) . '</p>';
    echo '<p><strong>Total:</strong> $' . number_format($total, 2) . '</p>';
    echo '<p><strong>Deposit:</strong> $' . number_format($deposit, 2) . ($deposit_paid ? ' (Paid)' : ' (Pending)') . '</p>';
    if ($due) echo '<p><strong>Due:</strong> ' . esc_html($due) . '</p>';
    echo '</div>';
}
function studio_invoice_meta_boxes() {
    add_meta_box('studio_invoice_adjustments', 'Summary', 'studio_invoice_adjustments_callback', 'studio_invoice', 'normal', 'high');
    add_meta_box('studio_invoice_tax', 'Tax Information', 'studio_invoice_tax_meta', 'studio_invoice', 'side', 'default');
}
add_action('add_meta_boxes', 'studio_invoice_meta_boxes');

function studio_invoice_tax_meta($post) {
    $tax_rate = get_post_meta($post->ID, '_tax_rate', true) ?: '0';
    $tax_collected = get_post_meta($post->ID, '_tax_collected', true) ?: '0';
    $tax_jurisdiction = get_post_meta($post->ID, '_tax_jurisdiction', true);
    ?>
    <p><label>Tax Rate (%): <input type="number" step="0.001" name="_tax_rate" value="<?php echo esc_attr($tax_rate); ?>" style="width:100%;"></label></p>
    <p><label>Tax Collected ($): <input type="number" step="0.01" name="_tax_collected" value="<?php echo esc_attr($tax_collected); ?>" style="width:100%;"></label></p>
    <p><label>Tax Jurisdiction: <input type="text" name="_tax_jurisdiction" value="<?php echo esc_attr($tax_jurisdiction); ?>" placeholder="e.g. Texas" style="width:100%;"></label></p>
    <?php
}

function studio_save_invoice_tax_meta($post_id) {
    if (!isset($_POST['_studio_invoice_nonce']) || !wp_verify_nonce($_POST['_studio_invoice_nonce'], 'studio_save_invoice')) return;
    if (isset($_POST['_tax_rate'])) update_post_meta($post_id, '_tax_rate', floatval($_POST['_tax_rate']));
    if (isset($_POST['_tax_collected'])) update_post_meta($post_id, '_tax_collected', floatval($_POST['_tax_collected']));
    if (isset($_POST['_tax_jurisdiction'])) update_post_meta($post_id, '_tax_jurisdiction', sanitize_text_field($_POST['_tax_jurisdiction']));
}
add_action('save_post_studio_invoice', 'studio_save_invoice_tax_meta');

/* ----------------------------------------------
   EXPENSE CPT
   ---------------------------------------------- */
function studio_expense_cpt() {
    register_post_type('studio_expense', [
        'labels' => ['name' => 'Expenses', 'singular_name' => 'Expense', 'menu_name' => 'Expenses'],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-cart',
        'supports' => ['title', 'editor'],
    ]);
}
add_action('init', 'studio_expense_cpt');

function studio_expense_meta_boxes() {
    add_meta_box('studio_expense_details', 'Expense Details', function($post) {
        $cats = ['Equipment', 'Software', 'Travel', 'Venue', 'Marketing', 'Supplies', 'Utilities', 'Maintenance', 'Other'];
        $current = get_post_meta($post->ID, '_expense_category', true);
        $tax_cats = ['Operating Expense', 'Capital Expenditure', 'Home Office', 'Vehicle', 'Professional Development', 'Insurance', 'Not Deductible'];
        $tax_cat = get_post_meta($post->ID, '_tax_category', true);
        $deductible = get_post_meta($post->ID, '_tax_deductible', true);
        $receipt_url = get_post_meta($post->ID, '_receipt_url', true);
        ?>
        <p><label>Amount ($): <input type="number" step="0.01" name="_expense_amount" value="<?php echo esc_attr(get_post_meta($post->ID, '_expense_amount', true)); ?>" style="width:100%;"></label></p>
        <p><label>Date: <input type="date" name="_expense_date" value="<?php echo esc_attr(get_post_meta($post->ID, '_expense_date', true)); ?>" style="width:100%;"></label></p>
        <p><label>Category: <select name="_expense_category" style="width:100%;">
            <?php foreach ($cats as $c) : ?>
                <option value="<?php echo esc_attr($c); ?>" <?php selected($current, $c); ?>><?php echo esc_html($c); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <hr style="margin:12px 0;border-color:#ddd;">
        <p><label>Tax Category: <select name="_tax_category" style="width:100%;">
            <option value="">Select...</option>
            <?php foreach ($tax_cats as $tc) : ?>
                <option value="<?php echo esc_attr($tc); ?>" <?php selected($tax_cat, $tc); ?>><?php echo esc_html($tc); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <p><label><input type="checkbox" name="_tax_deductible" value="1" <?php checked($deductible, '1'); ?>> Tax Deductible</label></p>
        <p><label>Receipt URL: <input type="url" name="_receipt_url" value="<?php echo esc_url($receipt_url); ?>" placeholder="Link to receipt photo/PDF" style="width:100%;"></label></p>
        <?php
    }, 'studio_expense', 'side', 'high');
}
add_action('add_meta_boxes', 'studio_expense_meta_boxes');

function studio_save_expense_meta($post_id) {
    if (!isset($_POST['_studio_expense_nonce']) || !wp_verify_nonce($_POST['_studio_expense_nonce'], 'studio_save_expense')) return;
    if (isset($_POST['_expense_amount'])) update_post_meta($post_id, '_expense_amount', floatval($_POST['_expense_amount']));
    if (isset($_POST['_expense_date'])) update_post_meta($post_id, '_expense_date', sanitize_text_field($_POST['_expense_date']));
    if (isset($_POST['_expense_category'])) update_post_meta($post_id, '_expense_category', sanitize_text_field($_POST['_expense_category']));
    if (isset($_POST['_tax_category'])) update_post_meta($post_id, '_tax_category', sanitize_text_field($_POST['_tax_category']));
    if (isset($_POST['_tax_deductible'])) update_post_meta($post_id, '_tax_deductible', '1');
    elseif (!isset($_POST['_tax_deductible'])) delete_post_meta($post_id, '_tax_deductible');
    if (isset($_POST['_receipt_url'])) update_post_meta($post_id, '_receipt_url', esc_url_raw($_POST['_receipt_url']));
}
add_action('save_post_studio_expense', 'studio_save_expense_meta');

/* ----------------------------------------------
   GIG CPT
   ---------------------------------------------- */
function studio_gig_cpt() {
    register_post_type('studio_gig', [
        'labels' => ['name' => 'Gigs', 'singular_name' => 'Gig', 'menu_name' => 'Gigs'],
        'public' => true, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'gig-listings'],
    ]);
}
add_action('init', 'studio_gig_cpt');

function studio_gig_meta_boxes() {
    add_meta_box('studio_gig_details', 'Gig Details', function($post) {
        ?>
        <p><label>Date: <input type="date" name="_gig_date" value="<?php echo esc_attr(get_post_meta($post->ID, '_gig_date', true)); ?>" style="width:100%;"></label></p>
        <p><label>Venue: <input type="text" name="_gig_venue" value="<?php echo esc_attr(get_post_meta($post->ID, '_gig_venue', true)); ?>" style="width:100%;"></label></p>
        <p><label>Time: <input type="text" name="_gig_time" value="<?php echo esc_attr(get_post_meta($post->ID, '_gig_time', true)); ?>" placeholder="e.g. 8:00 PM" style="width:100%;"></label></p>
        <p><label>Ticket URL: <input type="url" name="_gig_ticket_url" value="<?php echo esc_url(get_post_meta($post->ID, '_gig_ticket_url', true)); ?>" style="width:100%;"></label></p>
        <p><label>Pay ($): <input type="number" step="0.01" name="_gig_pay" value="<?php echo esc_attr(get_post_meta($post->ID, '_gig_pay', true)); ?>" style="width:100%;"></label></p>
        <?php
    }, 'studio_gig', 'side', 'high');
}
add_action('add_meta_boxes', 'studio_gig_meta_boxes');

function studio_save_gig_meta($post_id) {
    if (!isset($_POST['_studio_gig_nonce']) || !wp_verify_nonce($_POST['_studio_gig_nonce'], 'studio_save_gig')) return;
    if (isset($_POST['_gig_date'])) update_post_meta($post_id, '_gig_date', sanitize_text_field($_POST['_gig_date']));
    if (isset($_POST['_gig_venue'])) update_post_meta($post_id, '_gig_venue', sanitize_text_field($_POST['_gig_venue']));
    if (isset($_POST['_gig_time'])) update_post_meta($post_id, '_gig_time', sanitize_text_field($_POST['_gig_time']));
    if (isset($_POST['_gig_ticket_url'])) update_post_meta($post_id, '_gig_ticket_url', esc_url_raw($_POST['_gig_ticket_url']));
    if (isset($_POST['_gig_pay'])) update_post_meta($post_id, '_gig_pay', floatval($_POST['_gig_pay']));
}
add_action('save_post_studio_gig', 'studio_save_gig_meta');

/* ----------------------------------------------
   PROMO CPT
   ---------------------------------------------- */
function studio_promo_cpt() {
    register_post_type('studio_promo', [
        'labels' => ['name' => 'Promotions', 'singular_name' => 'Promotion', 'menu_name' => 'Promotions'],
        'public' => false, 'show_ui' => true,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => ['title', 'editor'],
    ]);
}
add_action('init', 'studio_promo_cpt');

function studio_promo_meta_boxes() {
    add_meta_box('studio_promo_details', 'Promotion Details', function($post) {
        ?>
        <p><label>Discount: <input type="text" name="_promo_discount" value="<?php echo esc_attr(get_post_meta($post->ID, '_promo_discount', true)); ?>" placeholder="e.g. 20%" style="width:100%;"></label></p>
        <p><label>Promo Code: <input type="text" name="_promo_code" value="<?php echo esc_attr(get_post_meta($post->ID, '_promo_code', true)); ?>" style="width:100%;"></label></p>
        <?php
    }, 'studio_promo', 'side', 'high');
}
add_action('add_meta_boxes', 'studio_promo_meta_boxes');

function studio_save_promo_meta($post_id) {
    if (!isset($_POST['_studio_promo_nonce']) || !wp_verify_nonce($_POST['_studio_promo_nonce'], 'studio_save_promo')) return;
    if (isset($_POST['_promo_discount'])) update_post_meta($post_id, '_promo_discount', sanitize_text_field($_POST['_promo_discount']));
    if (isset($_POST['_promo_code'])) update_post_meta($post_id, '_promo_code', sanitize_text_field($_POST['_promo_code']));
}
add_action('save_post_studio_promo', 'studio_save_promo_meta');

/* ----------------------------------------------
   BOOKING CPT
   ---------------------------------------------- */
function studio_booking_cpt() {
    register_post_type('studio_booking', [
        'labels' => ['name' => 'Bookings', 'singular_name' => 'Booking', 'menu_name' => 'Bookings', 'add_new_item' => 'Add Booking'],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title'],
        'capability_type' => 'post',
    ]);
}
add_action('init', 'studio_booking_cpt');

function studio_booking_actions_meta_callback($post) {
    $status = get_post_meta($post->ID, '_status', true) ?: 'Pending';
    echo '<p><strong>Status:</strong> ' . esc_html($status) . '</p>';
    if ($status === 'Pending') {
        $token = get_post_meta($post->ID, '_access_token', true);
        $approve_url = wp_nonce_url(
            add_query_arg(['studio_booking_approve' => $post->ID, '_token' => $token], home_url('/')),
            'studio_booking_action_' . $post->ID
        );
        $decline_url = wp_nonce_url(
            add_query_arg(['studio_booking_decline' => $post->ID, '_token' => $token], home_url('/')),
            'studio_booking_action_' . $post->ID
        );
        echo '<p><a href="' . esc_url($approve_url) . '" class="button button-primary" style="width:100%;text-align:center;">Approve</a></p>';
        echo '<p><a href="' . esc_url($decline_url) . '" class="button" style="width:100%;text-align:center;">Decline</a></p>';
    }
}

function studio_booking_meta_boxes() {
    add_meta_box('studio_booking_actions', 'Booking Actions', 'studio_booking_actions_meta_callback', 'studio_booking', 'side', 'high');
    add_meta_box('studio_booking_counter', 'Counter Offer', 'studio_counter_offer_meta_callback', 'studio_booking', 'side', 'default');
}
add_action('add_meta_boxes', 'studio_booking_meta_boxes');

/* ----------------------------------------------
   EQUIPMENT CPT + TAXONOMY
   ---------------------------------------------- */
function studio_equipment_cpt() {
    register_post_type('studio_equipment', [
        'labels' => ['name' => 'Equipment', 'singular_name' => 'Equipment', 'menu_name' => 'Equipment', 'add_new_item' => 'Add Equipment'],
        'public' => true, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-slides',
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => true,
        'rewrite' => ['slug' => 'equipment'],
    ]);

    register_taxonomy('equipment_category', 'studio_equipment', [
        'labels' => ['name' => 'Categories', 'singular_name' => 'Category', 'add_new_item' => 'Add Category'],
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'gear'],
    ]);
}
add_action('init', 'studio_equipment_cpt');

function studio_equipment_meta_boxes() {
    add_meta_box('studio_equipment_details', 'Equipment Details', function($post) {
        $cond = get_post_meta($post->ID, '_condition', true);
        $conds = ['Excellent', 'Good', 'Fair', 'Poor', 'Needs Repair'];
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <p><label>Manufacturer<br><input type="text" name="_manufacturer" value="<?php echo esc_attr(get_post_meta($post->ID, '_manufacturer', true)); ?>" style="width:100%;"></label></p>
            <p><label>Model<br><input type="text" name="_model" value="<?php echo esc_attr(get_post_meta($post->ID, '_model', true)); ?>" style="width:100%;"></label></p>
            <p><label>Serial Number<br><input type="text" name="_serial_number" value="<?php echo esc_attr(get_post_meta($post->ID, '_serial_number', true)); ?>" style="width:100%;"></label></p>
            <p><label>Condition<br><select name="_condition" style="width:100%;">
                <option value="">Select...</option>
                <?php foreach ($conds as $c) : ?>
                    <option value="<?php echo esc_attr($c); ?>" <?php selected($cond, $c); ?>><?php echo esc_html($c); ?></option>
                <?php endforeach; ?>
            </select></label></p>
            <p><label>Purchase Date<br><input type="date" name="_purchase_date" value="<?php echo esc_attr(get_post_meta($post->ID, '_purchase_date', true)); ?>" style="width:100%;"></label></p>
            <p><label>Purchase Price ($)<br><input type="number" step="0.01" name="_purchase_price" value="<?php echo esc_attr(get_post_meta($post->ID, '_purchase_price', true)); ?>" style="width:100%;"></label></p>
            <p><label>Current Value ($)<br><input type="number" step="0.01" name="_current_value" value="<?php echo esc_attr(get_post_meta($post->ID, '_current_value', true)); ?>" style="width:100%;"></label></p>
        </div>
        <?php
    }, 'studio_equipment', 'normal', 'high');

    add_meta_box('studio_equipment_maintenance', 'Maintenance', function($post) {
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <p><label>Last Maintenance<br><input type="date" name="_last_maintenance" value="<?php echo esc_attr(get_post_meta($post->ID, '_last_maintenance', true)); ?>" style="width:100%;"></label></p>
            <p><label>Next Maintenance<br><input type="date" name="_next_maintenance" value="<?php echo esc_attr(get_post_meta($post->ID, '_next_maintenance', true)); ?>" style="width:100%;"></label></p>
        </div>
        <p><label>Maintenance Notes<br><textarea name="_maintenance_notes" rows="4" style="width:100%;"><?php echo esc_textarea(get_post_meta($post->ID, '_maintenance_notes', true)); ?></textarea></label></p>
        <?php
    }, 'studio_equipment', 'side', 'default');
}
add_action('add_meta_boxes', 'studio_equipment_meta_boxes');

function studio_save_equipment_meta($post_id) {
    if (!isset($_POST['_studio_equipment_nonce']) || !wp_verify_nonce($_POST['_studio_equipment_nonce'], 'studio_save_equipment')) return;
    $fields = ['_manufacturer', '_model', '_serial_number', '_condition', '_purchase_date', '_maintenance_notes'];
    $num_fields = ['_purchase_price', '_current_value'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) update_post_meta($post_id, $f, sanitize_text_field($_POST[$f]));
    }
    foreach ($num_fields as $f) {
        if (isset($_POST[$f])) update_post_meta($post_id, $f, floatval($_POST[$f]));
    }
    if (isset($_POST['_last_maintenance'])) update_post_meta($post_id, '_last_maintenance', sanitize_text_field($_POST['_last_maintenance']));
    if (isset($_POST['_next_maintenance'])) update_post_meta($post_id, '_next_maintenance', sanitize_text_field($_POST['_next_maintenance']));
}
add_action('save_post_studio_equipment', 'studio_save_equipment_meta');

/* ----------------------------------------------
   COUNTER-OFFER HANDLER
   ---------------------------------------------- */
function studio_counter_offer_handler() {
    if (!current_user_can('manage_options')) return;
    if (!isset($_POST['studio_counter_offer']) || !wp_verify_nonce($_POST['_co_nonce'] ?? '', 'studio_counter_offer')) return;

    $booking_id = intval($_POST['booking_id']);
    $new_rate = floatval($_POST['new_rate']);
    $new_hours = floatval($_POST['new_hours']);
    $message = sanitize_textarea_field($_POST['co_message'] ?? '');

    update_post_meta($booking_id, '_status', 'Counter-Offer');
    update_post_meta($booking_id, '_counter_rate', $new_rate);
    update_post_meta($booking_id, '_counter_hours', $new_hours);
    update_post_meta($booking_id, '_counter_message', $message);

    $client_email = get_post_meta($booking_id, '_client_email', true);
    $name = get_the_title($booking_id);
    $token = get_post_meta($booking_id, '_access_token', true) ?: studio_generate_token($booking_id);

    if ($client_email) {
        $accept_url = home_url("/booking-action/?action=accept_counter&booking_id=$booking_id&token=$token");
        $decline_url = home_url("/booking-action/?action=decline_counter&booking_id=$booking_id&token=$token");
        studio_send_email($client_email, "Counter Offer for Your Booking",
            '<p>Hi ' . esc_html($name) . ', we have a counter offer for your booking:</p>' .
            '<p><strong>New Rate:</strong> $' . number_format($new_rate, 2) . '/hr</p>' .
            '<p><strong>Hours:</strong> ' . number_format($new_hours, 1) . '</p>' .
            ($message ? '<p><strong>Note:</strong> ' . esc_html($message) . '</p>' : '') .
            '<p><a href="' . $accept_url . '" style="display:inline-block;padding:12px 32px;background:#d4a574;color:#0a0a0a;text-decoration:none;border-radius:8px;font-weight:bold;">Accept Counter Offer</a></p>' .
            '<p style="margin-top:12px;"><a href="' . $decline_url . '" style="color:#888;">Decline Counter Offer</a></p>');
    }
    wp_redirect(admin_url('edit.php?post_type=studio_booking'));
    exit;
}
add_action('admin_init', 'studio_counter_offer_handler');

/* ----------------------------------------------
   BOOKING ACTION HANDLER (Client-Facing, with token verification)
   ---------------------------------------------- */
function studio_booking_action_handler() {
    if (!isset($_GET['action'])) return;
    $action = sanitize_text_field($_GET['action']);

    if ($action === 'pay_deposit') {
        $invoice_id = intval($_GET['invoice_id'] ?? 0);
        $token = sanitize_text_field($_GET['token'] ?? '');
        if ($invoice_id && $token) {
            $booking_id = intval(get_post_meta($invoice_id, '_booking_id', true));
            if (!studio_verify_token($booking_id, $token)) {
                wp_die('Invalid or expired access token.');
            }
            $deposit = get_post_meta($invoice_id, '_deposit', true);
            $total = get_post_meta($invoice_id, '_total', true);
            $amount = floatval($deposit ?: floatval($total) * 0.5);
            wp_redirect("https://www.paypal.com/paypalme/Jesseroux87/{$amount}USD");
            exit;
        }
    }

    if ($action === 'accept_counter') {
        $booking_id = intval($_GET['booking_id'] ?? 0);
        $token = sanitize_text_field($_GET['token'] ?? '');
        if ($booking_id && studio_verify_token($booking_id, $token)) {
            $new_hours = floatval(get_post_meta($booking_id, '_counter_hours', true));
            update_post_meta($booking_id, '_total_hours', $new_hours);
            studio_do_approve($booking_id, 0, 'percent');
            wp_redirect(home_url('/booking/?booking=sent'));
            exit;
        }
        wp_die('Invalid or expired access token.');
    }

    if ($action === 'decline_counter') {
        $booking_id = intval($_GET['booking_id'] ?? 0);
        $token = sanitize_text_field($_GET['token'] ?? '');
        if ($booking_id && studio_verify_token($booking_id, $token)) {
            update_post_meta($booking_id, '_status', 'Declined');
            wp_redirect(home_url('/booking/?booking=sent'));
            exit;
        }
        wp_die('Invalid or expired access token.');
    }
}
add_action('template_redirect', 'studio_booking_action_handler');

/* ----------------------------------------------
   BOOKING APPROVAL + INVOICE GENERATION
   ---------------------------------------------- */
function studio_do_approve($post_id, $discount = 0, $discount_type = 'percent', $override_rate = null) {
    update_post_meta($post_id, '_status', 'Approved');
    update_post_meta($post_id, '_discount', $discount);
    update_post_meta($post_id, '_discount_type', $discount_type);

    $service = get_post_meta($post_id, '_service', true);
    $total_hours = floatval(get_post_meta($post_id, '_total_hours', true));
    $name = get_the_title($post_id);
    $client_email = get_post_meta($post_id, '_client_email', true);

    if ($override_rate !== null) {
        $rate = $override_rate;
    } else {
        $rate_info = studio_rate_for_service($service);
        $rate = $rate_info['rate'];
        if (!$rate_info['hourly'] && $rate > 0) $total_hours = 1;
        if ($rate === 0) $total_hours = 0;
    }

    $subtotal = $rate * $total_hours;
    $discount_amount = 0;
    if ($discount > 0) {
        $discount_amount = ($discount_type === 'percent') ? $subtotal * ($discount / 100) : $discount;
    }
    $total = max(0, $subtotal - $discount_amount);
    $deposit = round($total * 0.5, 2);

    // Skip invoice generation for free services
    if ($total <= 0) {
        if ($client_email) {
            studio_send_email($client_email, "Booking Approved!",
                '<p>Great news, ' . esc_html($name) . '! Your booking for <strong>' . esc_html($service) . '</strong> has been approved.</p>' .
                '<p>This is a complimentary session. No payment required.</p>');
        }
        return;
    }

    $token = get_post_meta($post_id, '_access_token', true) ?: studio_generate_token($post_id);

    $invoice_id = wp_insert_post(['post_type' => 'studio_invoice', 'post_title' => "Invoice for $name", 'post_status' => 'publish']);
    if ($invoice_id) {
        $meta = [
            '_booking_id' => $post_id, '_client_email' => $client_email, '_client_name' => $name,
            '_service' => $service, '_rate' => $rate, '_total_hours' => $total_hours,
            '_subtotal' => $subtotal, '_discount' => $discount, '_discount_type' => $discount_type,
            '_discount_amount' => $discount_amount, '_total' => $total, '_deposit' => $deposit,
            '_deposit_paid' => 0, '_status' => 'Draft',
            '_due_date' => date('Y-m-d', strtotime('+7 days')),
            '_created_at' => current_time('mysql'),
        ];
        foreach ($meta as $k => $v) update_post_meta($invoice_id, $k, $v);
        update_post_meta($post_id, '_invoice_id', $invoice_id);
    }

    if ($client_email) {
        $deposit_url = home_url("/booking-action/?action=pay_deposit&invoice_id=$invoice_id&token=$token");
        $slots = get_post_meta($post_id, '_booking_slots', true);
        $first_slot = is_array($slots) && !empty($slots[0]) ? $slots[0] : null;

        $calendar_section = '';
        if ($first_slot && !empty($first_slot['date'])) {
            $date_str = $first_slot['date'];
            $start_time = str_replace(':', '', $first_slot['start'] ?? '0900');
            $end_time = str_replace(':', '', $first_slot['end'] ?? '1700');
            $gcal_start = $date_str . 'T' . $start_time . '00';
            $gcal_end = $date_str . 'T' . $end_time . '00';
            $gcal_url = "https://calendar.google.com/calendar/render?action=TEMPLATE"
                . "&text=" . urlencode($service . " - " . $name)
                . "&dates=" . $gcal_start . "/" . $gcal_end
                . "&details=" . urlencode("Session with Roux's Audio Production");
            $calendar_section = '<p style="margin-top:16px;"><a href="' . $gcal_url . '" style="display:inline-block;padding:10px 24px;border:1px solid #d4a574;color:#d4a574;text-decoration:none;border-radius:8px;margin-right:8px;">Add to Google Calendar</a></p>';
        }

        studio_send_email($client_email, "Booking Approved!",
            '<p>Great news, ' . esc_html($name) . '! Your booking for <strong>' . esc_html($service) . '</strong> has been approved.</p>' .
            '<p><strong>Total:</strong> $' . number_format($total, 2) . '</p>' .
            '<p><strong>50% Deposit Required:</strong> $' . number_format($deposit, 2) . '</p>' .
            '<p><a href="' . $deposit_url . '" style="display:inline-block;padding:12px 32px;background:#d4a574;color:#0a0a0a;text-decoration:none;border-radius:8px;font-weight:bold;">Pay Deposit via PayPal</a></p>' .
            $calendar_section);
    }
}

/* ----------------------------------------------
   PERFORMANCE: Transient cache helper
   ---------------------------------------------- */
function studio_cache_get($key, $ttl = 300) {
    $cached = get_transient($key);
    if ($cached !== false) return $cached;
    return null;
}

function studio_cache_set($key, $data, $ttl = 300) {
    set_transient($key, $data, $ttl);
}

function studio_cache_flush($key) {
    delete_transient($key);
}

/* ----------------------------------------------
   CRON CALLBACKS (optimized: bulk meta queries)
   ---------------------------------------------- */
function studio_send_payment_reminders() {
    $invoices = get_posts([
        'post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1,
        'meta_query' => [['key' => '_status', 'value' => 'Sent']],
    ]);
    $today = date('Y-m-d');
    foreach ($invoices as $inv) {
        $due = get_post_meta($inv->ID, '_due_date', true);
        if ($due && strtotime($due) < strtotime('+3 days')) {
            $client_email = get_post_meta($inv->ID, '_client_email', true);
            $sent_key = '_reminder_sent_' . $today;
            if ($client_email && !get_post_meta($inv->ID, $sent_key, true)) {
                studio_send_email($client_email, "Payment Reminder - Invoice #{$inv->ID}",
                    '<p>This is a friendly reminder that your invoice is due on <strong>' . esc_html($due) . '</strong>. Please submit payment at your earliest convenience.</p>');
                update_post_meta($inv->ID, $sent_key, '1');
            }
        }
    }
}

function studio_send_session_reminders() {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $bookings = get_posts([
        'post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1,
        'meta_query' => [['key' => '_status', 'value' => 'Approved']],
    ]);
    foreach ($bookings as $bk) {
        $slots = get_post_meta($bk->ID, '_booking_slots', true);
        if (!is_array($slots)) continue;
        foreach ($slots as $slot) {
            if (isset($slot['date']) && $slot['date'] === $tomorrow) {
                $client_email = get_post_meta($bk->ID, '_client_email', true);
                $sent_key = '_reminder_sent_' . $tomorrow;
                if ($client_email && !get_post_meta($bk->ID, $sent_key, true)) {
                    studio_send_email($client_email, "Session Reminder - Tomorrow",
                        '<p>Hi ' . esc_html($bk->post_title) . ', this is a reminder that you have a session scheduled for <strong>tomorrow (' . esc_html($tomorrow) . ')</strong> from ' . esc_html($slot['start'] ?? '') . ' to ' . esc_html($slot['end'] ?? '') . '.</p>');
                    update_post_meta($bk->ID, $sent_key, '1');
                }
                break;
            }
        }
    }
}

function studio_send_maintenance_reminders() {
    $equipment = get_posts(['post_type' => 'studio_equipment', 'post_status' => 'publish', 'numberposts' => -1]);
    $soon = date('Y-m-d', strtotime('+14 days'));
    $upcoming = [];
    foreach ($equipment as $eq) {
        $next = get_post_meta($eq->ID, '_next_maintenance', true);
        if ($next && $next <= $soon) {
            $upcoming[] = $eq->post_title . ' (' . ($next <= date('Y-m-d') ? 'OVERDUE' : 'due ' . date('M j', strtotime($next))) . ')';
        }
    }
    if (!empty($upcoming)) {
        $body = '<p>Equipment maintenance reminders:</p><ul>';
        foreach ($upcoming as $item) $body .= '<li>' . esc_html($item) . '</li>';
        $body .= '</ul>';
        studio_send_email(get_option('admin_email'), 'Equipment Maintenance Due', $body);
    }
}

/* ----------------------------------------------
   SERVER HEALTH CHECK
   ---------------------------------------------- */
function studio_run_health_check() {
    global $wpdb;
    $checks = [];

    $checks['db'] = ['label' => 'Database', 'status' => 'ok', 'detail' => ''];
    $start = microtime(true);
    if (!$wpdb->check_connection()) {
        $checks['db']['status'] = 'err';
        $checks['db']['detail'] = 'Connection failed';
    } else {
        $wpdb->get_var("SELECT 1");
        $elapsed = round((microtime(true) - $start) * 1000);
        $checks['db']['detail'] = $elapsed . 'ms';
        if ($elapsed > 500) $checks['db']['status'] = 'warn';
    }

    $checks['disk'] = ['label' => 'Disk Space', 'status' => 'ok', 'detail' => ''];
    $free = @disk_free_space('/');
    $total = @disk_total_space('/');
    if ($free && $total) {
        $used_pct = round((1 - $free / $total) * 100);
        $free_gb = round($free / 1073741824, 1);
        $checks['disk']['detail'] = $free_gb . 'GB free (' . $used_pct . '% used)';
        if ($used_pct > 90) $checks['disk']['status'] = 'err';
        elseif ($used_pct > 75) $checks['disk']['status'] = 'warn';
    }

    $checks['memory'] = ['label' => 'PHP Memory', 'status' => 'ok', 'detail' => ''];
    $mem_used = round(memory_get_usage(true) / 1048576, 1);
    $mem_limit = ini_get('memory_limit');
    $mem_limit_mb = intval($mem_limit);
    if ($mem_limit_mb > 0) {
        $mem_pct = round($mem_used / $mem_limit_mb * 100);
        $checks['memory']['detail'] = $mem_used . 'MB / ' . $mem_limit_mb . 'MB';
        if ($mem_pct > 85) $checks['memory']['status'] = 'err';
        elseif ($mem_pct > 65) $checks['memory']['status'] = 'warn';
    }

    $checks['cron'] = ['label' => 'WP-Cron', 'status' => 'ok', 'detail' => ''];
    $cron_count = count(_get_cron_array());
    $checks['cron']['detail'] = $cron_count . ' scheduled events';
    if ($cron_count === 0) $checks['cron']['status'] = 'warn';

    $checks['php'] = ['label' => 'PHP Version', 'status' => 'ok', 'detail' => PHP_VERSION];
    $checks['wp'] = ['label' => 'WordPress', 'status' => 'ok', 'detail' => get_bloginfo('version')];

    $updates = get_site_transient('update_core');
    if (isset($updates->updates) && is_array($updates->updates)) {
        foreach ($updates->updates as $up) {
            if (isset($up->response) && $up->response === 'update-available') {
                $checks['wp']['status'] = 'warn';
                $checks['wp']['detail'] .= ' (update available)';
                break;
            }
        }
    }

    $plugin_updates = get_plugin_updates();
    if (!empty($plugin_updates)) {
        $checks['plugins'] = [
            'label' => 'Plugins',
            'status' => 'warn',
            'detail' => count($plugin_updates) . ' update(s) available',
        ];
    }

    $site_health = get_site_transient('site_health');
    if ($site_health && isset($site_health['status'])) {
        $checks['health'] = [
            'label' => 'Site Health',
            'status' => $site_health['status'] === 'good' ? 'ok' : 'warn',
            'detail' => $site_health['status'] === 'good' ? 'All checks passed' : 'Issues detected',
        ];
    }

    update_option('studio_health_data', ['checks' => $checks, 'timestamp' => current_time('mysql')]);
}

function studio_get_health_data() {
    $cached = studio_cache_get('studio_health_data', 3600);
    if ($cached) return $cached;
    $data = get_option('studio_health_data', []);
    if (empty($data) || empty($data['timestamp'])) {
        studio_run_health_check();
        $data = get_option('studio_health_data', ['checks' => [], 'timestamp' => 'never']);
    }
    studio_cache_set('studio_health_data', $data, 3600);
    return $data;
}

/* ----------------------------------------------
   ADMIN DASHBOARD WIDGETS
   ---------------------------------------------- */
function studio_admin_dashboard_widgets() {
    wp_add_dashboard_widget('studio_overview', 'Roux Studio Overview', 'studio_dashboard_overview_callback');
    wp_add_dashboard_widget('studio_health', 'Server Health', 'studio_dashboard_health_callback');
    wp_add_dashboard_widget('studio_recent_bookings', 'Recent Bookings', 'studio_dashboard_recent_bookings_callback');
}
add_action('wp_dashboard_setup', 'studio_admin_dashboard_widgets');

function studio_dashboard_overview_callback() {
    $cached = studio_cache_get('studio_dashboard_overview', 120);
    if ($cached) {
        echo $cached;
        return;
    }
    ob_start();

    $bookings = wp_count_posts('studio_booking')->publish ?? 0;
    $invoices = wp_count_posts('studio_invoice')->publish ?? 0;
    $expenses = wp_count_posts('studio_expense')->publish ?? 0;
    $equipment = wp_count_posts('studio_equipment')->publish ?? 0;

    $pending_q = new WP_Query(['post_type' => 'studio_booking', 'post_status' => 'publish', 'meta_key' => '_status', 'meta_value' => 'New', 'fields' => 'ids', 'posts_per_page' => -1]);
    $pending = $pending_q->found_posts;
    wp_reset_postdata();

    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');

    $revenue = 0;
    $month_invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['after' => $month_start, 'before' => $month_end, 'inclusive' => true]], 'fields' => 'ids']);
    foreach ($month_invoices as $inv_id) { if (get_post_meta($inv_id, '_deposit_paid', true)) { $revenue += floatval(get_post_meta($inv_id, '_total', true)); } }

    $exp = 0;
    $month_expenses = get_posts(['post_type' => 'studio_expense', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['after' => $month_start, 'before' => $month_end, 'inclusive' => true]], 'fields' => 'ids']);
    foreach ($month_expenses as $ex_id) { $exp += floatval(get_post_meta($ex_id, '_expense_amount', true)); }

    echo '<div class="roux-admin-dashboard">';
    echo '<div class="rstat"><div class="rstat-icon">&#128197;</div><div><div class="rstat-val">' . $bookings . '</div><div class="rstat-label">Bookings</div></div>';
    if ($pending > 0) echo ' <span style="background:rgba(74,158,255,0.15);color:#4a9eff;padding:2px 8px;border-radius:12px;font-size:0.7rem;margin-left:auto;">' . $pending . ' pending</span>';
    echo '</div>';
    echo '<div class="rstat"><div class="rstat-icon">&#128176;</div><div><div class="rstat-val">' . $invoices . '</div><div class="rstat-label">Invoices</div></div></div>';
    echo '<div class="rstat"><div class="rstat-icon">&#128722;</div><div><div class="rstat-val">$' . number_format($exp, 0) . '</div><div class="rstat-label">Expenses This Month</div></div></div>';
    echo '<div class="rstat"><div class="rstat-icon">&#127925;</div><div><div class="rstat-val">' . $equipment . '</div><div class="rstat-label">Equipment Items</div></div></div>';
    echo '<div class="rstat"><div class="rstat-icon">&#9650;</div><div><div class="rstat-val" style="color:var(--green);">$' . number_format($revenue, 0) . '</div><div class="rstat-label">Revenue This Month</div></div></div>';
    echo '</div>';

    $html = ob_get_clean();
    echo $html;
    studio_cache_set('studio_dashboard_overview', $html, 120);
}

function studio_dashboard_health_callback() {
    $data = studio_get_health_data();
    $checks = $data['checks'] ?? [];
    $ts = $data['timestamp'] ?? 'never';

    if (empty($checks)) {
        echo '<p style="color:#888;">No health data available. <a href="' . admin_url('admin.php?action=studio_health_refresh') . '">Run check now</a>.</p>';
        return;
    }

    echo '<ul class="roux-health-list">';
    foreach ($checks as $key => $check) {
        $dot_cls = $check['status'] === 'ok' ? 'ok' : ($check['status'] === 'warn' ? 'warn' : 'err');
        echo '<li>';
        echo '<span class="health-dot ' . $dot_cls . '"></span>';
        echo '<strong>' . esc_html($check['label']) . '</strong>';
        echo '<span style="margin-left:auto;color:#888;">' . esc_html($check['detail']) . '</span>';
        echo '</li>';
    }
    echo '</ul>';
    echo '<p style="font-size:0.75rem;color:#555;margin-top:12px;">Last check: ' . esc_html($ts) . ' &mdash; <a href="' . wp_nonce_url(admin_url('admin.php?action=studio_health_refresh'), 'studio_health_refresh') . '">Refresh</a></p>';
}

function studio_dashboard_recent_bookings_callback() {
    $recent = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => 5, 'orderby' => 'date', 'order' => 'DESC']);
    if (!$recent) { echo '<p style="color:#888;">No bookings yet.</p>'; return; }
    echo '<table style="width:100%;border-collapse:collapse;">';
    echo '<thead><tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #ddd;font-size:0.8rem;">Client</th><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #ddd;font-size:0.8rem;">Service</th><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #ddd;font-size:0.8rem;">Status</th></tr></thead>';
    echo '<tbody>';
    foreach ($recent as $bk) {
        $st = get_post_meta($bk->ID, '_status', true) ?: 'New';
        $sv = get_post_meta($bk->ID, '_service', true);
        echo '<tr>';
        echo '<td style="padding:6px 8px;border-bottom:1px solid #eee;"><a href="' . get_edit_post_link($bk->ID) . '">' . esc_html($bk->post_title) . '</a></td>';
        echo '<td style="padding:6px 8px;border-bottom:1px solid #eee;font-size:0.85rem;">' . esc_html($sv) . '</td>';
        echo '<td style="padding:6px 8px;border-bottom:1px solid #eee;">' . studio_badge_html($st) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function studio_health_refresh_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'studio_health_refresh')) wp_die('Invalid nonce');
    studio_run_health_check();
    wp_redirect(admin_url('index.php?studio_health_refreshed=1'));
    exit;
}
add_action('admin_action_studio_health_refresh', 'studio_health_refresh_handler');

/* ----------------------------------------------
   ADMIN COLUMNS — BOOKINGS
   ---------------------------------------------- */
function studio_booking_columns($columns) {
    $new = [];
    foreach ($columns as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['status'] = 'Status';
            $new['service'] = 'Service';
            $new['date_col'] = 'Date';
        }
    }
    return $new;
}
add_filter('manage_studio_booking_posts_columns', 'studio_booking_columns');

function studio_booking_column_data($column, $post_id) {
    switch ($column) {
        case 'status':
            $st = get_post_meta($post_id, '_status', true) ?: 'New';
            echo studio_badge_html($st);
            break;
        case 'service':
            echo esc_html(get_post_meta($post_id, '_service', true));
            break;
        case 'date_col':
            echo get_the_date('M j, Y', $post_id);
            break;
    }
}
add_action('manage_studio_booking_posts_custom_column', 'studio_booking_column_data', 10, 2);

function studio_booking_sortable_columns($columns) {
    $columns['status'] = 'status';
    $columns['date_col'] = 'date_col';
    return $columns;
}
add_filter('manage_edit-studio_booking_sortable_columns', 'studio_booking_sortable_columns');

function studio_booking_status_sort($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'studio_booking') return;
    if ($query->get('orderby') === 'status') {
        $query->set('meta_key', '_status');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'studio_booking_status_sort');

/* ----------------------------------------------
   ADMIN COLUMNS — INVOICES
   ---------------------------------------------- */
function studio_invoice_columns($columns) {
    $new = [];
    foreach ($columns as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['inv_status'] = 'Status';
            $new['inv_total'] = 'Total';
            $new['inv_due'] = 'Due Date';
        }
    }
    return $new;
}
add_filter('manage_studio_invoice_posts_columns', 'studio_invoice_columns');

function studio_invoice_column_data($column, $post_id) {
    switch ($column) {
        case 'inv_status':
            $st = get_post_meta($post_id, '_status', true) ?: 'Draft';
            echo studio_badge_html($st);
            break;
        case 'inv_total':
            echo '$' . number_format(floatval(get_post_meta($post_id, '_total', true)), 2);
            break;
        case 'inv_due':
            $due = get_post_meta($post_id, '_due_date', true);
            $overdue = ($due && $due < date('Y-m-d') && get_post_meta($post_id, '_status', true) !== 'Paid');
            echo '<span style="color:' . ($overdue ? 'var(--red)' : 'inherit') . ';">' . esc_html($due) . ($overdue ? ' (overdue)' : '') . '</span>';
            break;
    }
}
add_action('manage_studio_invoice_posts_custom_column', 'studio_invoice_column_data', 10, 2);

/* ----------------------------------------------
   ADMIN COLUMNS — EQUIPMENT
   ---------------------------------------------- */
function studio_equipment_columns($columns) {
    $new = [];
    foreach ($columns as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['eq_manufacturer'] = 'Manufacturer';
            $new['eq_condition'] = 'Condition';
            $new['eq_value'] = 'Value';
            $new['eq_maintenance'] = 'Next Maintenance';
        }
    }
    return $new;
}
add_filter('manage_studio_equipment_posts_columns', 'studio_equipment_columns');

function studio_equipment_column_data($column, $post_id) {
    switch ($column) {
        case 'eq_manufacturer':
            echo esc_html(get_post_meta($post_id, '_manufacturer', true));
            break;
        case 'eq_condition':
            $c = get_post_meta($post_id, '_condition', true);
            if ($c) echo studio_badge_html($c);
            break;
        case 'eq_value':
            $v = get_post_meta($post_id, '_current_value', true);
            if ($v) echo '$' . number_format(floatval($v), 2);
            break;
        case 'eq_maintenance':
            $next = get_post_meta($post_id, '_next_maintenance', true);
            if ($next) {
                $overdue = strtotime($next) < time();
                echo '<span style="color:' . ($overdue ? 'var(--red)' : 'inherit') . ';">' . date('M j, Y', strtotime($next)) . ($overdue ? ' (!)' : '') . '</span>';
            }
            break;
    }
}
add_action('manage_studio_equipment_posts_custom_column', 'studio_equipment_column_data', 10, 2);

/* ----------------------------------------------
   iCal EXPORT (require login or admin)
   ---------------------------------------------- */
function studio_ical_export() {
    if (!isset($_GET['ical'])) return;
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to export calendar data.');
    }
    $type = sanitize_text_field($_GET['ical']);

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="roux-studio-' . $type . '.ics"');

    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Roux Audio Production//EN\r\nCALSCALE:GREGORIAN\r\n";
    echo "X-WR-TIMEZONE:America/Chicago\r\n";
    echo "BEGIN:VTIMEZONE\r\nTZID:America/Chicago\r\nBEGIN:DAYLIGHT\r\nTZOFFSETFROM:-0600\r\nTZOFFSETTO:-0500\r\nTZNAME:CDT\r\nDTSTART:19700308T020000\r\nRRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU\r\nEND:DAYLIGHT\r\nBEGIN:STANDARD\r\nTZOFFSETFROM:-0500\r\nTZOFFSETTO:-0600\r\nTZNAME:CST\r\nDTSTART:19701101T020000\r\nRRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU\r\nEND:STANDARD\r\nEND:VTIMEZONE\r\n";

    if ($type === 'bookings') {
        $bookings = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1, 'meta_key' => '_status', 'meta_value' => 'Approved']);
        foreach ($bookings as $bk) {
            $slots = get_post_meta($bk->ID, '_booking_slots', true);
            if (!is_array($slots)) continue;
            foreach ($slots as $slot) {
                if (empty($slot['date'])) continue;
                $start = $slot['date'] . 'T' . str_replace(':', '', $slot['start'] ?? '00:00') . '00';
                $end = $slot['date'] . 'T' . str_replace(':', '', $slot['end'] ?? '23:59') . '00';
                echo "BEGIN:VEVENT\r\n";
                echo "DTSTART;TZID=America/Chicago:" . date('Ymd\THis', strtotime($start)) . "\r\n";
                echo "DTEND;TZID=America/Chicago:" . date('Ymd\THis', strtotime($end)) . "\r\n";
                echo "SUMMARY:" . esc_html($bk->post_title . ' - ' . get_post_meta($bk->ID, '_service', true)) . "\r\n";
                echo "UID:" . $bk->ID . "-" . $slot['date'] . "@rouxsaudioproduction.us\r\n";
                echo "END:VEVENT\r\n";
            }
        }
    }

    echo "END:VCALENDAR\r\n";
    exit;
}
add_action('init', 'studio_ical_export');

/* ----------------------------------------------
   SHORTCODES
   ---------------------------------------------- */
function studio_pnl_shortcode($atts) {
    $atts = shortcode_atts(['month' => date('m'), 'year' => date('Y'), 'range' => 'month'], $atts);

    if ($atts['range'] === 'year') {
        $year = intval($atts['year']);
        $start = "$year-01-01";
        $end = "$year-12-31";
        $label = "P&L Report \u2014 $year";
    } elseif ($atts['range'] === 'quarter') {
        $quarter = ceil(date('m') / 3);
        $q_start_month = (($quarter - 1) * 3) + 1;
        $start = date('Y') . '-' . str_pad($q_start_month, 2, '0', STR_PAD_LEFT) . '-01';
        $end = date('Y-m-t', strtotime($start . '+2 months'));
        $label = "P&L Report \u2014 Q$quarter " . date('Y');
    } else {
        $start = "{$atts['year']}-{$atts['month']}-01";
        $end = date('Y-m-t', strtotime($start));
        $label = 'P&L \u2014 ' . date('F Y', strtotime($start));
    }

    $revenue = 0;
    $invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['after' => $start, 'before' => $end, 'inclusive' => true]]]);
    foreach ($invoices as $inv) {
        if (get_post_meta($inv->ID, '_deposit_paid', true)) {
            $revenue += floatval(get_post_meta($inv->ID, '_total', true));
        }
    }

    $expenses = 0;
    $categories = [];
    $exp_posts = get_posts(['post_type' => 'studio_expense', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['after' => $start, 'before' => $end, 'inclusive' => true]]]);
    foreach ($exp_posts as $ex) {
        $amt = floatval(get_post_meta($ex->ID, '_expense_amount', true));
        $cat = get_post_meta($ex->ID, '_expense_category', true) ?: 'Other';
        $expenses += $amt;
        $categories[$cat] = ($categories[$cat] ?? 0) + $amt;
    }

    $net = $revenue - $expenses;
    $ob = '<div class="glass"><h3>' . $label . '</h3>';
    $ob .= '<div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--surface-border);"><span style="color:var(--text-muted);">Revenue</span><span style="color:var(--green);font-weight:600;">$' . number_format($revenue, 2) . '</span></div>';
    $ob .= '<div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--surface-border);"><span style="color:var(--text-muted);">Expenses</span><span style="color:var(--red);font-weight:600;">$' . number_format($expenses, 2) . '</span></div>';
    $ob .= '<div style="display:flex;justify-content:space-between;padding:12px 0;"><span style="color:var(--text-muted);">Net</span><span style="color:' . ($net >= 0 ? 'var(--green)' : 'var(--red)') . ';font-weight:600;">$' . number_format($net, 2) . '</span></div>';

    if (!empty($categories)) {
        arsort($categories);
        $ob .= '<h4 style="margin-top:24px;">Expense Breakdown</h4>';
        foreach ($categories as $cat => $amt) {
            $pct = $expenses > 0 ? round($amt / $expenses * 100) : 0;
            $ob .= '<div style="display:flex;justify-content:space-between;padding:6px 0;font-size:0.9rem;"><span style="color:var(--text-muted);">' . esc_html($cat) . ' (' . $pct . '%)</span><span>$' . number_format($amt, 2) . '</span></div>';
        }
    }

    $ob .= '</div>';
    return $ob;
}
add_shortcode('studio_pnl', 'studio_pnl_shortcode');

function studio_promo_shortcode() {
    $promos = get_posts(['post_type' => 'studio_promo', 'post_status' => 'publish', 'numberposts' => 3]);
    if (empty($promos)) return '';
    $ob = '<div class="grid grid-3">';
    foreach ($promos as $p) {
        $discount = get_post_meta($p->ID, '_promo_discount', true);
        $code = get_post_meta($p->ID, '_promo_code', true);
        $ob .= '<div class="glass" style="text-align:center;">';
        $ob .= '<h3 style="color:var(--gold);">' . esc_html($p->post_title) . '</h3>';
        $ob .= '<p style="font-size:2rem;color:var(--gold);margin:16px 0;">' . esc_html($discount) . ' OFF</p>';
        if ($code) $ob .= '<p style="color:var(--text-muted);">Use code: <strong>' . esc_html($code) . '</strong></p>';
        $ob .= '<div style="color:var(--text);font-size:0.95rem;">' . wp_kses_post($p->post_content) . '</div>';
        $ob .= '</div>';
    }
    $ob .= '</div>';
    return $ob;
}
add_shortcode('studio_promos', 'studio_promo_shortcode');

function studio_equipment_value_shortcode() {
    $equipment = get_posts(['post_type' => 'studio_equipment', 'post_status' => 'publish', 'numberposts' => -1]);
    $total_value = 0;
    $total_cost = 0;
    $count = 0;
    foreach ($equipment as $eq) {
        $val = floatval(get_post_meta($eq->ID, '_current_value', true));
        $cost = floatval(get_post_meta($eq->ID, '_purchase_price', true));
        $total_value += $val;
        $total_cost += $cost;
        $count++;
    }
    $depr = $total_cost - $total_value;
    $ob = '<div class="glass">';
    $ob .= '<h3>Equipment Portfolio</h3>';
    $ob .= '<div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);"><span style="color:var(--text-muted);">Total Items</span><span>' . $count . '</span></div>';
    $ob .= '<div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);"><span style="color:var(--text-muted);">Original Cost</span><span>$' . number_format($total_cost, 2) . '</span></div>';
    $ob .= '<div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--surface-border);"><span style="color:var(--text-muted);">Current Value</span><span>$' . number_format($total_value, 2) . '</span></div>';
    $ob .= '<div style="display:flex;justify-content:space-between;padding:10px 0;"><span style="color:var(--text-muted);">Depreciation</span><span style="color:var(--red);">$' . number_format($depr, 2) . '</span></div>';
    $ob .= '</div>';
    return $ob;
}
add_shortcode('studio_equipment_value', 'studio_equipment_value_shortcode');

function studio_health_shortcode() {
    if (!current_user_can('manage_options')) return '<p style="color:var(--text-muted);">Access restricted.</p>';
    $data = studio_get_health_data();
    $checks = $data['checks'] ?? [];
    $ts = $data['timestamp'] ?? '';
    if (empty($checks)) return '<p style="color:var(--text-muted);">No health data.</p>';

    $ob = '<div class="glass"><h3>Server Health</h3>';
    foreach ($checks as $check) {
        $dot = $check['status'] === 'ok' ? 'ok' : ($check['status'] === 'warn' ? 'warn' : 'err');
        $ob .= '<div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--surface-border);">';
        $ob .= '<span class="health-dot ' . $dot . '"></span>';
        $ob .= '<span style="flex:1;">' . esc_html($check['label']) . '</span>';
        $ob .= '<span style="color:var(--text-muted);font-size:0.85rem;">' . esc_html($check['detail']) . '</span>';
        $ob .= '</div>';
    }
    $ob .= '<p style="font-size:0.75rem;color:var(--text-dim);margin-top:12px;">Last check: ' . esc_html($ts) . '</p>';
    $ob .= '</div>';
    return $ob;
}
add_shortcode('studio_health', 'studio_health_shortcode');

function studio_booking_shortcode($atts) {
    $atts = shortcode_atts(['status' => '', 'limit' => 10], $atts);
    $args = ['post_type' => 'studio_booking', 'post_status' => 'publish', 'posts_per_page' => intval($atts['limit']), 'orderby' => 'date', 'order' => 'DESC'];
    if (!empty($atts['status'])) {
        $args['meta_query'] = [['key' => '_status', 'value' => sanitize_text_field($atts['status'])]];
    }
    $bookings = get_posts($args);
    if (empty($bookings)) return '<p style="color:var(--text-muted);">No bookings found.</p>';

    $ob = '<div class="table-wrap"><table><thead><tr><th>Client</th><th>Service</th><th>Status</th><th>Date</th></tr></thead><tbody>';
    foreach ($bookings as $bk) {
        $st = get_post_meta($bk->ID, '_status', true) ?: 'New';
        $sv = get_post_meta($bk->ID, '_service', true);
        $ob .= '<tr><td>' . esc_html($bk->post_title) . '</td><td>' . esc_html($sv) . '</td><td>' . studio_badge_html($st) . '</td><td>' . get_the_date('M j, Y', $bk->ID) . '</td></tr>';
    }
    $ob .= '</tbody></table></div>';
    return $ob;
}
add_shortcode('studio_bookings', 'studio_booking_shortcode');

/* ----------------------------------------------
   TAX REPORT SHORTCODE
   ---------------------------------------------- */
function studio_tax_report_shortcode($atts) {
    if (!current_user_can('manage_options')) return '';
    $atts = shortcode_atts(['year' => date('Y')], $atts);
    $year = intval($atts['year']);
    $start = "$year-01-01";
    $end = "$year-12-31";

    $invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1,
        'date_query' => [['after' => $start, 'before' => $end, 'inclusive' => true]]]);
    $gross_income = 0;
    $tax_collected = 0;
    $tax_by_jurisdiction = [];
    foreach ($invoices as $inv) {
        $total = floatval(get_post_meta($inv->ID, '_total', true));
        $tax = floatval(get_post_meta($inv->ID, '_tax_collected', true));
        $jurisdiction = get_post_meta($inv->ID, '_tax_jurisdiction', true) ?: 'Unknown';
        if (get_post_meta($inv->ID, '_deposit_paid', true) || get_post_meta($inv->ID, '_status', true) === 'Paid') {
            $gross_income += $total;
        }
        $tax_collected += $tax;
        $tax_by_jurisdiction[$jurisdiction] = ($tax_by_jurisdiction[$jurisdiction] ?? 0) + $tax;
    }

    $expenses = get_posts(['post_type' => 'studio_expense', 'post_status' => 'publish', 'numberposts' => -1,
        'date_query' => [['after' => $start, 'before' => $end, 'inclusive' => true]]]);
    $total_expenses = 0;
    $deductible_expenses = 0;
    $expenses_by_category = [];
    foreach ($expenses as $ex) {
        $amt = floatval(get_post_meta($ex->ID, '_expense_amount', true));
        $cat = get_post_meta($ex->ID, '_tax_category', true) ?: 'Uncategorized';
        $is_deductible = get_post_meta($ex->ID, '_tax_deductible', true);
        $total_expenses += $amt;
        if ($is_deductible) $deductible_expenses += $amt;
        $expenses_by_category[$cat] = ($expenses_by_category[$cat] ?? 0) + $amt;
    }

    $net_income = $gross_income - $total_expenses;
    $ob = '<div class="glass">';
    $ob .= '<h3>Tax Summary \u2014 ' . $year . '</h3>';
    $ob .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin:24px 0;">';
    $ob .= '<div><span style="color:var(--text-muted);font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;display:block;">Gross Income</span><span style="font-size:1.4rem;color:var(--green);font-weight:600;">$' . number_format($gross_income, 2) . '</span></div>';
    $ob .= '<div><span style="color:var(--text-muted);font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;display:block;">Total Expenses</span><span style="font-size:1.4rem;color:var(--red);font-weight:600;">$' . number_format($total_expenses, 2) . '</span></div>';
    $ob .= '<div><span style="color:var(--text-muted);font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;display:block;">Deductible Expenses</span><span style="font-size:1.4rem;color:var(--blue);font-weight:600;">$' . number_format($deductible_expenses, 2) . '</span></div>';
    $ob .= '<div><span style="color:var(--text-muted);font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;display:block;">Net Income</span><span style="font-size:1.4rem;color:' . ($net_income >= 0 ? 'var(--green)' : 'var(--red)') . ';font-weight:600;">$' . number_format($net_income, 2) . '</span></div>';
    $ob .= '</div>';

    if ($tax_collected > 0) {
        $ob .= '<h4>Sales Tax Collected</h4>';
        $ob .= '<div style="padding:12px 0;border-bottom:1px solid var(--surface-border);display:flex;justify-content:space-between;"><span>Total Collected</span><span>$' . number_format($tax_collected, 2) . '</span></div>';
        foreach ($tax_by_jurisdiction as $jur => $amt) {
            $ob .= '<div style="padding:8px 0;font-size:0.9rem;display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">' . esc_html($jur) . '</span><span>$' . number_format($amt, 2) . '</span></div>';
        }
    }

    if (!empty($expenses_by_category)) {
        arsort($expenses_by_category);
        $ob .= '<h4 style="margin-top:24px;">Expense Categories</h4>';
        foreach ($expenses_by_category as $cat => $amt) {
            $ob .= '<div style="padding:8px 0;font-size:0.9rem;display:flex;justify-content:space-between;"><span style="color:var(--text-muted);">' . esc_html($cat) . '</span><span>$' . number_format($amt, 2) . '</span></div>';
        }
    }

    $ob .= '<p style="font-size:0.75rem;color:var(--text-dim);margin-top:24px;">Generated: ' . current_time('F j, Y') . ' \u2014 For informational purposes. Consult a tax professional for filing.</p>';
    $ob .= '</div>';
    return $ob;
}
add_shortcode('studio_tax_report', 'studio_tax_report_shortcode');

/* ----------------------------------------------
   INVOICE MANAGER ADMIN PAGE
   ---------------------------------------------- */
function studio_invoice_admin_page() {
    add_submenu_page(
        'edit.php?post_type=studio_invoice',
        'Invoice Manager',
        'Invoice Manager',
        'manage_options',
        'studio-invoice-manager',
        'studio_render_invoice_manager'
    );
}
add_action('admin_menu', 'studio_invoice_admin_page');

function studio_render_invoice_manager() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    // Handle create
    if (isset($_POST['studio_create_invoice']) && wp_verify_nonce($_POST['_invoice_nonce'] ?? '', 'studio_create_invoice')) {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $client_name = sanitize_text_field($_POST['client_name'] ?? '');
        $client_email = sanitize_email($_POST['client_email'] ?? '');
        $service = sanitize_text_field($_POST['service'] ?? '');
        $rate = floatval($_POST['rate'] ?? 0);
        $hours = floatval($_POST['hours'] ?? 1);
        $discount = floatval($_POST['discount'] ?? 0);
        $discount_type = sanitize_text_field($_POST['discount_type'] ?? 'percent');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $subtotal = $rate * $hours;
        $discount_amount = ($discount_type === 'percent') ? $subtotal * ($discount / 100) : $discount;
        $total = max(0, $subtotal - $discount_amount);
        $deposit = round($total * 0.5, 2);

        $invoice_id = wp_insert_post([
            'post_type' => 'studio_invoice',
            'post_title' => "Invoice for $client_name",
            'post_status' => 'publish',
        ]);

        if ($invoice_id) {
            $meta = [
                '_booking_id' => $booking_id, '_client_email' => $client_email,
                '_client_name' => $client_name, '_service' => $service,
                '_rate' => $rate, '_total_hours' => $hours,
                '_subtotal' => $subtotal, '_discount' => $discount,
                '_discount_type' => $discount_type, '_discount_amount' => $discount_amount,
                '_total' => $total, '_deposit' => $deposit, '_deposit_paid' => 0,
                '_status' => 'Draft',
                '_due_date' => sanitize_text_field($_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'))),
                '_created_at' => current_time('mysql'),
                '_notes' => $notes,
            ];
            foreach ($meta as $k => $v) update_post_meta($invoice_id, $k, $v);
            if ($booking_id) update_post_meta($booking_id, '_invoice_id', $invoice_id);
            echo '<div class="notice notice-success"><p>Invoice #' . $invoice_id . ' created.</p></div>';
        }
    }

    // Handle send
    if (isset($_GET['send_invoice']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'studio_send_invoice')) {
        $inv_id = intval($_GET['send_invoice']);
        $client_email = get_post_meta($inv_id, '_client_email', true);
        $client_name = get_post_meta($inv_id, '_client_name', true);
        $total = floatval(get_post_meta($inv_id, '_total', true));
        $deposit = floatval(get_post_meta($inv_id, '_deposit', true));
        $due = get_post_meta($inv_id, '_due_date', true);
        $service = get_post_meta($inv_id, '_service', true);
        $booking_id = intval(get_post_meta($inv_id, '_booking_id', true));
        $token = $booking_id ? (get_post_meta($booking_id, '_access_token', true) ?: '') : '';

        if ($client_email) {
            $pay_url = home_url("/booking-action/?action=pay_deposit&invoice_id=$inv_id&token=$token");
            studio_send_email($client_email, "Invoice #{$inv_id} - Roux's Audio Production",
                '<p>Hi ' . esc_html($client_name) . ',</p>' .
                '<p>Please find your invoice for <strong>' . esc_html($service) . '</strong>.</p>' .
                '<div style="background:rgba(255,255,255,0.05);padding:20px;border-radius:8px;margin:20px 0;">' .
                '<p><strong>Total:</strong> $' . number_format($total, 2) . '</p>' .
                '<p><strong>Deposit Required:</strong> $' . number_format($deposit, 2) . '</p>' .
                '<p><strong>Due Date:</strong> ' . esc_html($due) . '</p>' .
                '</div>' .
                '<p><a href="' . $pay_url . '" style="display:inline-block;padding:12px 32px;background:#d4a574;color:#0a0a0a;text-decoration:none;border-radius:8px;font-weight:bold;">Pay Deposit via PayPal</a></p>');
            update_post_meta($inv_id, '_status', 'Sent');
            echo '<div class="notice notice-success"><p>Invoice #' . $inv_id . ' sent to ' . esc_html($client_email) . '.</p></div>';
        }
    }

    // Handle mark paid
    if (isset($_GET['mark_paid']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'studio_mark_paid')) {
        $inv_id = intval($_GET['mark_paid']);
        update_post_meta($inv_id, '_status', 'Paid');
        update_post_meta($inv_id, '_deposit_paid', 1);
        echo '<div class="notice notice-success"><p>Invoice #' . $inv_id . ' marked as paid.</p></div>';
    }

    $invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']);
    $bookings = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']);
    ?>
    <div class="wrap">
        <h1>Invoice Manager</h1>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px;">
        <div style="background:#fff;padding:24px;border-radius:8px;border:1px solid #ccc;">
            <h2 style="margin-top:0;">Create Invoice</h2>
            <form method="post">
                <?php wp_nonce_field('studio_create_invoice', '_invoice_nonce'); ?>
                <input type="hidden" name="studio_create_invoice" value="1">
                <table class="form-table">
                    <tr><th><label for="booking_id">Link to Booking</label></th><td><select name="booking_id" id="booking_id" style="width:100%;"><option value="0">— None (Standalone) —</option><?php foreach ($bookings as $bk) : ?><option value="<?php echo $bk->ID; ?>"><?php echo esc_html("#{$bk->ID} - {$bk->post_title} (" . get_post_meta($bk->ID, '_service', true) . ")"); ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th><label for="client_name">Client Name</label></th><td><input type="text" name="client_name" id="client_name" required style="width:100%;"></td></tr>
                    <tr><th><label for="client_email">Client Email</label></th><td><input type="email" name="client_email" id="client_email" required style="width:100%;"></td></tr>
                    <tr><th><label for="service">Service</label></th><td><input type="text" name="service" id="service" required style="width:100%;"></td></tr>
                    <tr><th><label for="rate">Rate ($)</label></th><td><input type="number" step="0.01" name="rate" id="rate" required style="width:100%;"></td></tr>
                    <tr><th><label for="hours">Hours</label></th><td><input type="number" step="0.25" name="hours" id="hours" value="1" required style="width:100%;"></td></tr>
                    <tr><th><label for="discount">Discount</label></th><td><div style="display:flex;gap:8px;"><input type="number" step="0.01" name="discount" id="discount" value="0" style="width:60%;"><select name="discount_type" style="width:40%;"><option value="percent">%</option><option value="fixed">$</option></select></div></td></tr>
                    <tr><th><label for="due_date">Due Date</label></th><td><input type="date" name="due_date" id="due_date" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" style="width:100%;"></td></tr>
                    <tr><th><label for="notes">Notes</label></th><td><textarea name="notes" id="notes" rows="3" style="width:100%;"></textarea></td></tr>
                </table>
                <?php submit_button('Create Invoice', 'primary', 'submit', false); ?>
            </form>
        </div>
        <div style="background:#fff;padding:24px;border-radius:8px;border:1px solid #ccc;">
            <h2 style="margin-top:0;">Existing Invoices</h2>
            <?php if (empty($invoices)) : ?>
                <p>No invoices yet.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Status</th><th>Due</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($invoices as $inv) :
                            $status = get_post_meta($inv->ID, '_status', true) ?: 'Draft';
                            $total = floatval(get_post_meta($inv->ID, '_total', true));
                            $due = get_post_meta($inv->ID, '_due_date', true);
                            $client = get_post_meta($inv->ID, '_client_name', true);
                        ?>
                            <tr>
                                <td><?php echo $inv->ID; ?></td>
                                <td><?php echo esc_html($client); ?></td>
                                <td>$<?php echo number_format($total, 2); ?></td>
                                <td><?php echo studio_badge_html($status); ?></td>
                                <td><?php echo esc_html($due); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($inv->ID); ?>">Edit</a>
                                    <?php if ($status === 'Draft') : ?>
                                        | <a href="<?php echo wp_nonce_url(add_query_arg('send_invoice', $inv->ID), 'studio_send_invoice'); ?>">Send</a>
                                    <?php endif; ?>
                                    <?php if ($status !== 'Paid') : ?>
                                        | <a href="<?php echo wp_nonce_url(add_query_arg('mark_paid', $inv->ID), 'studio_mark_paid'); ?>" style="color:green;">Mark Paid</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        </div>
    </div>
    <?php
}

/* ----------------------------------------------
   REST API ENDPOINTS
   ---------------------------------------------- */
function studio_register_rest_routes() {
    register_rest_route('studio/v1', '/health', [
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response(studio_get_health_data(), 200);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    register_rest_route('studio/v1', '/stats', [
        'methods' => 'GET',
        'callback' => function() {
            $stats = [
                'bookings' => (int)(wp_count_posts('studio_booking')->publish ?? 0),
                'invoices' => (int)(wp_count_posts('studio_invoice')->publish ?? 0),
                'expenses' => (int)(wp_count_posts('studio_expense')->publish ?? 0),
                'equipment' => (int)(wp_count_posts('studio_equipment')->publish ?? 0),
                'gigs' => (int)(wp_count_posts('studio_gig')->publish ?? 0),
            ];
            return new WP_REST_Response($stats, 200);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    register_rest_route('studio/v1', '/bookings', [
        'methods' => 'GET',
        'callback' => function($request) {
            $args = ['post_type' => 'studio_booking', 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC'];
            $status = $request->get_param('status');
            if ($status) $args['meta_query'] = [['key' => '_status', 'value' => sanitize_text_field($status)]];
            $bookings = get_posts($args);
            $results = [];
            foreach ($bookings as $bk) {
                $results[] = [
                    'id' => $bk->ID,
                    'client' => $bk->post_title,
                    'service' => get_post_meta($bk->ID, '_service', true),
                    'status' => get_post_meta($bk->ID, '_status', true) ?: 'New',
                    'hours' => floatval(get_post_meta($bk->ID, '_total_hours', true)),
                    'date' => get_the_date('Y-m-d', $bk->ID),
                ];
            }
            return new WP_REST_Response($results, 200);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);
}
add_action('rest_api_init', 'studio_register_rest_routes');

/* ----------------------------------------------
   BULK ACTIONS
   ---------------------------------------------- */
function studio_booking_bulk_actions($actions) {
    $actions['studio_bulk_approve'] = 'Approve';
    $actions['studio_bulk_decline'] = 'Decline';
    return $actions;
}
add_filter('bulk_actions-edit-studio_booking', 'studio_booking_bulk_actions');

function studio_booking_bulk_handler($redirect_to, $doaction, $post_ids) {
    if (!current_user_can('manage_options')) return $redirect_to;

    if ($doaction === 'studio_bulk_approve') {
        foreach ($post_ids as $id) {
            if (get_post_meta($id, '_status', true) !== 'Approved') {
                studio_do_approve($id, 0, 'percent');
            }
        }
        return add_query_arg('bulk_approved', count($post_ids), $redirect_to);
    }

    if ($doaction === 'studio_bulk_decline') {
        foreach ($post_ids as $id) {
            update_post_meta($id, '_status', 'Declined');
        }
        return add_query_arg('bulk_declined', count($post_ids), $redirect_to);
    }

    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-studio_booking', 'studio_booking_bulk_handler', 10, 3);

function studio_bulk_action_notices() {
    if (isset($_GET['bulk_approved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . intval($_GET['bulk_approved']) . ' booking(s) approved.</p></div>';
    }
    if (isset($_GET['bulk_declined'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . intval($_GET['bulk_declined']) . ' booking(s) declined.</p></div>';
    }
}
add_action('admin_notices', 'studio_bulk_action_notices');

/* ----------------------------------------------
   PERFORMANCE: Cache invalidation on data changes
   ---------------------------------------------- */
function studio_invalidate_caches($post_id = 0, $post = null) {
    if (!$post) return;
    $cpt = $post->post_type ?? get_post_type($post_id);
    if (in_array($cpt, ['studio_booking', 'studio_invoice', 'studio_expense', 'studio_equipment'])) {
        studio_cache_flush('studio_dashboard_overview');
        studio_cache_flush('studio_health_data');
    }
}
add_action('save_post', 'studio_invalidate_caches', 10, 2);

/* ----------------------------------------------
    ADMIN MENU CUSTOMIZATION
    ---------------------------------------------- */
function studio_admin_menu_order($menu) {
    $reorder = [];
    foreach ($menu as $item) {
        $reorder[] = $item;
        if ($item[2] === 'edit.php?post_type=studio_booking') {
            $reorder[] = ['', '', ''];
        }
    }
    return $reorder;
}
add_filter('global_menu_order', 'studio_admin_menu_order');
