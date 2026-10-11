<?php
// One-time timezone setup to avoid write on every request
if (get_option('studio_timezone_initialized') !== '1') {
	update_option('timezone_string', 'America/Chicago');
	update_option('studio_timezone_initialized', '1');
}
add_theme_support('title-tag');
add_theme_support('post-thumbnails');
add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
add_theme_support('customize-selective-refresh-widgets');

register_nav_menus(['primary' => 'Primary Navigation']);

/* Hide the WordPress admin bar on frontend — custom nav handles auth */
add_filter('show_admin_bar', '__return_false');

/* ----------------------------------------------
   SERVICE DEFINITIONS (machine-readable slugs)
   ---------------------------------------------- */
function studio_default_service_rates() {
    return [
        'recording'    => ['label' => 'Tier 1 - Recording',           'rate' => 75,  'hourly' => true],
        'mixing'       => ['label' => 'Tier 2 - Mixing',              'rate' => 150, 'hourly' => false],
        'mix-master'   => ['label' => 'Tier 3 - Mixing + Mastering',  'rate' => 200, 'hourly' => false],
        'full-prod'    => ['label' => 'Tier 4 - Full Production',     'rate' => 250, 'hourly' => false],
        'foh'          => ['label' => 'FOH Engineer - Live Sound',     'rate' => 50,  'hourly' => true],
        'consultation' => ['label' => 'Consultation',                  'rate' => 0,   'hourly' => false],
    ];
}

function studio_service_rates() {
    $defaults = studio_default_service_rates();
    $saved = get_option('studio_rates', []);
    if (!is_array($saved)) {
        $saved = [];
    }
    $rates = [];
    foreach ($defaults as $key => $entry) {
        $rate = isset($saved[$key]) && is_numeric($saved[$key]) ? (int) $saved[$key] : $entry['rate'];
        $hourly = isset($saved[$key . '_hourly']) ? (bool) $saved[$key . '_hourly'] : (bool) $entry['hourly'];
        if ($rate <= 0) {
            $desc = 'Free';
        } elseif ($hourly) {
            $desc = '$' . $rate . '/hr';
        } else {
            $desc = '$' . $rate . ' flat';
        }
        $rates[$key] = ['label' => $entry['label'], 'rate' => $rate, 'hourly' => $hourly, 'desc' => $desc];
    }
    return $rates;
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
    wp_enqueue_style('studio-roux', get_stylesheet_uri(), [], filemtime(get_template_directory() . '/style.css'));
    wp_enqueue_script('jquery');
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
    $timestamp  = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $line = "[$timestamp] STUDIO_ERROR: $message$context_str\n";
    // Write directly to debug.log so it is visible in the mounted volume
    @file_put_contents(ABSPATH . 'wp-content/debug.log', $line, FILE_APPEND | LOCK_EX);
}

function studio_send_email($to, $subject, $body_html, $attachments = []) {
    $holder  = ['pm' => null];
    $capture = function ($phpmailer) use (&$holder) {
        $holder['pm'] = $phpmailer;
    };
    add_action('phpmailer_init', $capture);

    $result = wp_mail($to, $subject, studio_email_html($body_html), ['Content-Type: text/html; charset=UTF-8'], $attachments);

    remove_action('phpmailer_init', $capture);

    if (is_wp_error($result)) {
        studio_log_error('wp_mail() returned WP_Error', ['to' => $to, 'subject' => $subject, 'error' => $result->get_error_message()]);
        return $result;
    }

    if (!$result) {
        $pm          = $holder['pm'];
        $info        = $pm
            ? ($pm->ErrorInfo !== '' ? $pm->ErrorInfo : ('ErrorNo=' . $pm->ErrorNo))
            : 'no phpmailer instance attached';
        $smtp_active = defined('STUDIO_SMTP_PASSWORD') ? !empty(STUDIO_SMTP_PASSWORD) : false;
        studio_log_error('wp_mail() FAILED - email NOT delivered', [
            'to'          => $to,
            'subject'     => $subject,
            'smtp_active' => $smtp_active,
            'last_error'  => $info,
        ]);
    }

    return $result;
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
    FRONTEND INVOICE CREATION HANDLER
    ---------------------------------------------- */
/* ----------------------------------------------
    AJAX: Autopopulate invoice from booking
    ---------------------------------------------- */
function studio_ajax_autopopulate_booking() {
    check_ajax_referer('studio_autopopulate', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    $id = intval($_POST['id']);
    $raw_service = get_post_meta($id, '_service', true);
    $service_map = [
        'Tier 1' => 'Recording', 'Recording' => 'Recording',
        'Tier 2' => 'Mixing', 'Mixing' => 'Mixing',
        'Tier 3' => 'Mixing + Mastering', 'Mixing + Mastering' => 'Mixing + Mastering',
        'Tier 4' => 'Full Production', 'Full Production' => 'Full Production',
        'FOH Engineer' => 'FOH Engineer', 'Monitor Engineer' => 'Monitor Engineer',
        'Sound Design' => 'Sound Design', 'Consultation' => 'Consultation',
        'Travel' => 'Travel/Transport', 'Equipment' => 'Equipment Rental',
    ];
    $service_type = '';
    foreach ($service_map as $key => $val) {
        if (stripos($raw_service, $key) !== false) { $service_type = $val; break; }
    }
    $rate_info = studio_rate_for_service($raw_service);
    $stored_rate = get_post_meta($id, '_rate', true);
    $rate = $stored_rate !== '' ? floatval($stored_rate) : floatval($rate_info['rate']);
    $line_item_qty = floatval(get_post_meta($id, '_total_hours', true));
    if (!$rate_info['hourly'] || $rate === 0) $line_item_qty = 1;
    $discount_type = get_post_meta($id, '_discount_type', true) ?: 'percent';
    $data = [
        'client_name'  => get_post_meta($id, '_client_name', true) ?: get_the_title($id),
        'client_email' => get_post_meta($id, '_client_email', true),
        'client_phone' => get_post_meta($id, '_client_phone', true),
        'event_date'   => '',
        'start_time'   => '',
        'end_time'     => '',
        'venue'        => get_post_meta($id, '_location', true),
        'service_type' => $service_type,
        'line_item_desc' => $raw_service ?: $service_type,
        'line_item_qty' => $line_item_qty,
        'line_item_rate' => $rate,
        'discount' => floatval(get_post_meta($id, '_discount', true)),
        'discount_type' => $discount_type === 'flat' ? 'fixed' : 'percent',
    ];

    // First slot date/time from booking
    $slots = get_post_meta($id, '_booking_slots', true);
    if (is_array($slots) && !empty($slots)) {
        $s = reset($slots);
        $data['event_date'] = isset($s['date']) ? $s['date'] : '';
        $data['start_time'] = $s['start'] ?? '';
        $data['end_time']   = $s['end'] ?? '';
    }

    wp_send_json_success($data);
}
add_action('wp_ajax_studio_invoice_autopopulate_booking', 'studio_ajax_autopopulate_booking');

/* ----------------------------------------------
    INVOICE EMAIL HELPERS
    ---------------------------------------------- */
function studio_invoice_email_body($inv_id) {
    $client_name = get_post_meta($inv_id, '_client_name', true);
    $total = floatval(get_post_meta($inv_id, '_total', true));
    $deposit = floatval(get_post_meta($inv_id, '_deposit', true));
    $due = get_post_meta($inv_id, '_due_date', true);
    $line_items = get_post_meta($inv_id, '_line_items', true);
    $event_date = get_post_meta($inv_id, '_event_date', true);
    $start_time = get_post_meta($inv_id, '_start_time', true);
    $end_time = get_post_meta($inv_id, '_end_time', true);
    $venue = get_post_meta($inv_id, '_venue', true);
    $service_type = get_post_meta($inv_id, '_service_type', true) ?: get_post_meta($inv_id, '_service', true);
    $subtotal = floatval(get_post_meta($inv_id, '_subtotal', true));
    if ($subtotal <= 0 && is_array($line_items)) {
        foreach ($line_items as $item) $subtotal += floatval($item['qty'] ?? 0) * floatval($item['rate'] ?? 0);
    }
    $discount_amount = floatval(get_post_meta($inv_id, '_discount_amount', true));
    $tax_amount = floatval(get_post_meta($inv_id, '_tax_collected', true));
    $paid = studio_invoice_paid_amount($inv_id);
    $balance = max(0, $total - $paid);

    $body = '<h2>Invoice #' . $inv_id . ' — Roux Audio Production</h2>';
    if ($event_date) $body .= '<p><strong>Event:</strong> ' . esc_html(date('F j, Y', strtotime($event_date))) . '</p>';
    if ($start_time || $end_time) $body .= '<p><strong>Time:</strong> ' . esc_html($start_time ?: '—') . ' – ' . esc_html($end_time ?: '—') . '</p>';
    if ($venue) $body .= '<p><strong>Venue:</strong> ' . esc_html($venue) . '</p>';

    if (is_array($line_items) && !empty($line_items)) {
        $body .= '<table style="width:100%;border-collapse:collapse;margin:16px 0;"><tr><th style="text-align:left;border-bottom:2px solid #d4a574;padding:8px;">Description</th><th style="border-bottom:2px solid #d4a574;padding:8px;">Qty</th><th style="border-bottom:2px solid #d4a574;padding:8px;">Rate</th><th style="text-align:right;border-bottom:2px solid #d4a574;padding:8px;">Total</th></tr>';
        foreach ($line_items as $li) {
            $qt = floatval($li['qty'] ?? 0);
            $rt = floatval($li['rate'] ?? 0);
            $body .= '<tr><td style="padding:6px;">' . esc_html($li['desc'] ?? '') . '</td><td style="padding:6px;">' . $qt . '</td><td style="padding:6px;">$' . number_format($rt, 2) . '</td><td style="text-align:right;padding:6px;">$' . number_format($qt * $rt, 2) . '</td></tr>';
        }
        $body .= '</table>';
    } else {
        $rate = floatval(get_post_meta($inv_id, '_rate', true));
        $hours = floatval(get_post_meta($inv_id, '_total_hours', true));
        if ($service_type) $body .= '<p><strong>Service:</strong> ' . esc_html($service_type) . '</p>';
        if ($rate && $hours) $body .= '<p>' . esc_html($hours) . ' hours = <strong>$' . number_format($rate * $hours, 2) . '</strong></p>';
    }

    $body .= '<table style="width:100%;max-width:360px;margin:20px 0 20px auto;border-collapse:collapse;">';
    $body .= '<tr><td style="padding:5px 0;">Subtotal</td><td style="padding:5px 0;text-align:right;">$' . number_format($subtotal, 2) . '</td></tr>';
    if ($discount_amount > 0) $body .= '<tr><td style="padding:5px 0;">Discount</td><td style="padding:5px 0;text-align:right;">-$' . number_format($discount_amount, 2) . '</td></tr>';
    if ($tax_amount > 0) $body .= '<tr><td style="padding:5px 0;">Tax</td><td style="padding:5px 0;text-align:right;">$' . number_format($tax_amount, 2) . '</td></tr>';
    $body .= '<tr><td style="padding:7px 0;border-top:1px solid #d4a574;font-weight:bold;">Total</td><td style="padding:7px 0;border-top:1px solid #d4a574;text-align:right;font-weight:bold;">$' . number_format($total, 2) . '</td></tr>';
    $body .= '<tr><td style="padding:5px 0;">Deposit</td><td style="padding:5px 0;text-align:right;">$' . number_format($deposit, 2) . '</td></tr>';
    $body .= '<tr><td style="padding:5px 0;">Paid to date</td><td style="padding:5px 0;text-align:right;">$' . number_format($paid, 2) . '</td></tr>';
    $body .= '<tr><td style="padding:5px 0;font-weight:bold;">Balance due</td><td style="padding:5px 0;text-align:right;font-weight:bold;">$' . number_format($balance, 2) . '</td></tr>';
    $body .= '</table>';
    if ($due) $body .= '<p><strong>Due Date:</strong> ' . esc_html(date('F j, Y', strtotime($due))) . '</p>';

    return $body;
}

function studio_mark_invoice_paid_manually($invoice_id) {
    $total = floatval(get_post_meta($invoice_id, '_total', true));
    update_post_meta($invoice_id, '_status', 'Paid');
    update_post_meta($invoice_id, '_paid_amount', $total);
    update_post_meta($invoice_id, '_paid_date', current_time('mysql'));
    update_post_meta($invoice_id, '_deposit_paid', '1');
    $booking_id = intval(get_post_meta($invoice_id, '_booking_id', true));
    if ($booking_id) {
        update_post_meta($booking_id, '_deposit_paid', '1');
        if (get_post_meta($booking_id, '_job_status', true) === 'Completed') {
            studio_set_booking_job_status($booking_id, 'Closed', false);
        }
    }
}

function studio_invoice_paid_amount($invoice_id) {
    $paid = get_post_meta($invoice_id, '_paid_amount', true);
    if ($paid !== '') return max(0, floatval($paid));
    if (get_post_meta($invoice_id, '_status', true) === 'Paid') {
        return max(0, floatval(get_post_meta($invoice_id, '_total', true)));
    }
    if (get_post_meta($invoice_id, '_deposit_paid', true)) {
        return min(
            max(0, floatval(get_post_meta($invoice_id, '_deposit', true))),
            max(0, floatval(get_post_meta($invoice_id, '_total', true)))
        );
    }
    return 0;
}

function studio_set_booking_job_status($booking_id, $new_status, $notify_client = true) {
    if (!in_array($new_status, ['Scheduled', 'In Progress', 'Completed', 'Closed'], true)) return false;
    if (get_post_meta($booking_id, '_job_status', true) === $new_status) return false;

    update_post_meta($booking_id, '_job_status', $new_status);
    update_post_meta($booking_id, '_job_status_changed_at', current_time('mysql'));
    $history = get_post_meta($booking_id, '_job_status_history', true);
    if (!is_array($history)) $history = [];
    $history[] = ['status' => $new_status, 'at' => current_time('mysql')];
    update_post_meta($booking_id, '_job_status_history', $history);

    $client_email = $notify_client ? get_post_meta($booking_id, '_client_email', true) : '';
    if ($client_email) {
        $client_name = get_the_title($booking_id);
        $token = get_post_meta($booking_id, '_access_token', true) ?: studio_generate_token($booking_id);
        $portal_url = add_query_arg('token', $token, home_url('/client-area/'));
        studio_send_email($client_email, 'Booking update — ' . $client_name,
            '<p>Hi ' . esc_html($client_name) . ',</p>'
            . '<p>Your session status is now <strong>' . esc_html($new_status) . '</strong>.</p>'
            . '<p><a href="' . esc_url($portal_url) . '">View your booking and invoice</a></p>');
    }
    return true;
}

function studio_invoice_pdf_text($value) {
    $value = wp_strip_all_tags((string) $value);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        if ($converted !== false) return $converted;
    }
    return preg_replace('/[^\x20-\x7E]/', '?', $value);
}

function studio_invoice_pdf_temp_file($invoice_id) {
    if (!class_exists('FPDF')) require_once __DIR__ . '/vendor/fpdf/fpdf.php';
    if (!class_exists('Studio_Invoice_PDF')) {
        class Studio_Invoice_PDF extends FPDF {
            public $invoice_id = 0;
            function Footer() {
                $this->SetY(-15);
                $this->SetDrawColor(212, 165, 116);
                $this->Line(18, $this->GetY(), 198, $this->GetY());
                $this->SetY(-12);
                $this->SetFont('Helvetica', '', 8);
                $this->SetTextColor(110, 110, 110);
                $this->Cell(90, 6, "Roux's Audio Production", 0, 0, 'L');
                $this->Cell(90, 6, 'Invoice #' . $this->invoice_id . '  |  Page ' . $this->PageNo(), 0, 0, 'R');
            }
        }
    }

    $client_name = get_post_meta($invoice_id, '_client_name', true);
    $client_email = get_post_meta($invoice_id, '_client_email', true);
    $service = get_post_meta($invoice_id, '_service_type', true) ?: get_post_meta($invoice_id, '_service', true);
    $event_date = get_post_meta($invoice_id, '_event_date', true);
    $start_time = get_post_meta($invoice_id, '_start_time', true);
    $end_time = get_post_meta($invoice_id, '_end_time', true);
    $venue = get_post_meta($invoice_id, '_venue', true);
    $due_date = get_post_meta($invoice_id, '_due_date', true);
    $line_items = get_post_meta($invoice_id, '_line_items', true);
    if (!is_array($line_items) || empty($line_items)) {
        $quantity = floatval(get_post_meta($invoice_id, '_total_hours', true));
        $rate = floatval(get_post_meta($invoice_id, '_rate', true));
        if ($quantity <= 0 && $rate > 0) $quantity = 1;
        $line_items = [['desc' => $service ?: 'Services', 'qty' => $quantity, 'rate' => $rate]];
    }

    $subtotal = 0;
    foreach ($line_items as $item) $subtotal += floatval($item['qty'] ?? 0) * floatval($item['rate'] ?? 0);
    $discount = floatval(get_post_meta($invoice_id, '_discount', true));
    $discount_type = get_post_meta($invoice_id, '_discount_type', true) ?: 'percent';
    $discount_amount = $discount_type === 'flat' || $discount_type === 'fixed'
        ? min($subtotal, $discount)
        : min($subtotal, $subtotal * $discount / 100);
    $tax_rate = floatval(get_post_meta($invoice_id, '_tax_rate', true));
    $tax_amount = max(0, $subtotal - $discount_amount) * $tax_rate / 100;
    $total = floatval(get_post_meta($invoice_id, '_total', true));
    if ($total <= 0 && $subtotal > 0) $total = max(0, $subtotal - $discount_amount) + $tax_amount;
    $deposit = floatval(get_post_meta($invoice_id, '_deposit', true));
    if ($deposit <= 0 && $total > 0) $deposit = round($total * 0.5, 2);
    $paid = studio_invoice_paid_amount($invoice_id);
    $balance = max(0, $total - $paid);

    $pdf = new Studio_Invoice_PDF('P', 'mm', 'Letter');
    $pdf->invoice_id = $invoice_id;
    $pdf->SetTitle('Invoice #' . $invoice_id);
    $pdf->SetAuthor("Roux's Audio Production");
    $pdf->SetMargins(18, 18, 18);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    $pdf->SetFillColor(28, 28, 28);
    $pdf->Rect(0, 0, 216, 39, 'F');
    $pdf->SetXY(18, 13);
    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->SetTextColor(212, 165, 116);
    $pdf->Cell(105, 10, "Roux's Audio Production", 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 17);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(75, 10, 'INVOICE', 0, 1, 'R');
    $pdf->SetXY(18, 28);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(235, 235, 235);
    $pdf->Cell(105, 5, 'Invoice #' . $invoice_id, 0, 0, 'L');
    $pdf->Cell(75, 5, 'Due: ' . ($due_date ? date('M j, Y', strtotime($due_date)) : 'Upon receipt'), 0, 1, 'R');

    $pdf->SetY(49);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(105, 105, 105);
    $pdf->Cell(90, 6, 'BILL TO', 0, 0, 'L');
    $pdf->Cell(90, 6, 'SESSION DETAILS', 0, 1, 'L');
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(35, 35, 35);
    $pdf->Cell(90, 7, studio_invoice_pdf_text($client_name ?: 'Client'), 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(90, 7, studio_invoice_pdf_text($service ?: 'Audio services'), 0, 1, 'L');
    $pdf->SetFont('Helvetica', '', 9);
    if ($client_email) $pdf->Cell(90, 6, studio_invoice_pdf_text($client_email), 0, 0, 'L');
    else $pdf->Cell(90, 6, '', 0, 0, 'L');
    $session_line = $event_date ? date('M j, Y', strtotime($event_date)) : '';
    if ($start_time || $end_time) $session_line .= ($session_line ? '  |  ' : '') . trim($start_time . ' - ' . $end_time);
    $pdf->Cell(90, 6, studio_invoice_pdf_text($session_line), 0, 1, 'L');
    if ($venue) {
        $pdf->Cell(90, 6, '', 0, 0, 'L');
        $pdf->MultiCell(90, 6, studio_invoice_pdf_text('Location: ' . $venue), 0, 'L');
    }

    $pdf->Ln(8);
    $pdf->SetFillColor(28, 28, 28);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(180, 8, '  SERVICES AND CHARGES', 0, 1, 'L', true);
    foreach ($line_items as $item) {
        if ($pdf->GetY() > 245) $pdf->AddPage();
        $quantity = floatval($item['qty'] ?? 0);
        $rate = floatval($item['rate'] ?? 0);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(35, 35, 35);
        $pdf->MultiCell(180, 6, studio_invoice_pdf_text($item['desc'] ?? 'Service'), 0, 'L');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(120, 6, studio_invoice_pdf_text('Qty: ' . $quantity . '  x  $' . number_format($rate, 2)), 0, 0, 'L');
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(35, 35, 35);
        $pdf->Cell(60, 6, '$' . number_format($quantity * $rate, 2), 0, 1, 'R');
        $pdf->SetDrawColor(225, 225, 225);
        $pdf->Line(18, $pdf->GetY() + 2, 198, $pdf->GetY() + 2);
        $pdf->Ln(5);
    }

    if ($pdf->GetY() > 225) $pdf->AddPage();
    $pdf->Ln(2);
    $pdf->SetX(108);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(70, 70, 70);
    $pdf->Cell(45, 7, 'Subtotal', 0, 0, 'L');
    $pdf->Cell(45, 7, '$' . number_format($subtotal, 2), 0, 1, 'R');
    if ($discount_amount > 0) {
        $discount_label = $discount_type === 'percent' ? 'Discount (' . $discount . '%)' : 'Discount';
        $pdf->SetX(108);
        $pdf->Cell(45, 7, studio_invoice_pdf_text($discount_label), 0, 0, 'L');
        $pdf->Cell(45, 7, '-$' . number_format($discount_amount, 2), 0, 1, 'R');
    }
    if ($tax_amount > 0) {
        $pdf->SetX(108);
        $pdf->Cell(45, 7, 'Tax (' . number_format($tax_rate, 3) . '%)', 0, 0, 'L');
        $pdf->Cell(45, 7, '$' . number_format($tax_amount, 2), 0, 1, 'R');
    }
    $pdf->SetX(108);
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetTextColor(35, 35, 35);
    $pdf->Cell(45, 9, 'Total', 0, 0, 'L');
    $pdf->Cell(45, 9, '$' . number_format($total, 2), 0, 1, 'R');
    $pdf->SetX(108);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->Cell(45, 7, 'Deposit due', 0, 0, 'L');
    $pdf->Cell(45, 7, '$' . number_format($deposit, 2), 0, 1, 'R');
    $pdf->SetX(108);
    $pdf->Cell(45, 7, 'Paid to date', 0, 0, 'L');
    $pdf->Cell(45, 7, '$' . number_format($paid, 2), 0, 1, 'R');
    $pdf->SetDrawColor(212, 165, 116);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(108, $pdf->GetY() + 1, 198, $pdf->GetY() + 1);
    $pdf->Ln(3);
    $pdf->SetX(108);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(45, 8, 'Balance due', 0, 0, 'L');
    $pdf->Cell(45, 8, '$' . number_format($balance, 2), 0, 1, 'R');

    $temp_path = tempnam(sys_get_temp_dir(), 'roux-invoice-');
    if (!$temp_path) return false;
    $path = $temp_path . '.pdf';
    if (!rename($temp_path, $path)) {
        @unlink($temp_path);
        return false;
    }
    $pdf->Output('F', $path);
    if (is_file($path) && filesize($path) > 0) return $path;
    @unlink($path);
    return false;
}

function studio_send_invoice_email($invoice_id, $to, $subject, $intro_html = '', $payment_url = '') {
    $pdf_path = studio_invoice_pdf_temp_file($invoice_id);
    if (!$pdf_path) {
        studio_log_error('Invoice PDF generation failed', ['invoice_id' => $invoice_id]);
        return new WP_Error('invoice_pdf_failed', 'Unable to generate the invoice PDF.');
    }
    $body = $intro_html . studio_invoice_email_body($invoice_id);
    if ($payment_url) {
        $body .= '<p><a href="' . esc_url($payment_url) . '" style="display:inline-block;padding:12px 32px;background:#d4a574;color:#0a0a0a;text-decoration:none;border-radius:8px;font-weight:bold;">View and Pay Invoice</a></p>';
    }
    $result = studio_send_email($to, $subject, $body, [$pdf_path]);
    @unlink($pdf_path);
    return $result;
}

/* ----------------------------------------------
    BOOKING FORM HANDLER
    ---------------------------------------------- */
function studio_booking_handler() {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['studio_booking'])) return;

    studio_log_error('BOOKING: handler called', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

    // Verify nonce
    if (!isset($_POST['_booking_nonce']) || !wp_verify_nonce($_POST['_booking_nonce'], 'studio_booking_submit')) {
        studio_log_error('BOOKING: failed - nonce invalid or missing');
        wp_safe_redirect(add_query_arg('booking', 'error', home_url('/booking/')));
        exit;
    }

    // Rate limiting: max 3 submissions per IP per hour (logged-in admins are exempt for testing)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rate_key = 'studio_booking_rate_' . md5($ip);
    $count = (is_user_logged_in() && current_user_can('manage_options'))
        ? 0
        : intval(get_transient($rate_key) ?: 0);
    if ($count >= 3) {
        studio_log_error('BOOKING: failed - rate limit exceeded', ['ip' => $ip, 'count' => $count]);
        wp_safe_redirect(add_query_arg('booking', 'error', home_url('/booking/')));
        exit;
    }
    set_transient($rate_key, $count + 1, HOUR_IN_SECONDS);

    // Server-side Turnstile verification
    if (defined('STUDIO_TURNSTILE_SECRET') && !empty(STUDIO_TURNSTILE_SECRET)) {
        $turnstile_response = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');
        if (empty($turnstile_response)) {
            studio_log_error('BOOKING: failed - Turnstile response empty');
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
            studio_log_error('BOOKING: failed - Turnstile verification rejected', ['turnstile_raw' => json_encode($result)]);
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

    $post_id = wp_insert_post([
        'post_type'   => 'studio_booking',
        'post_title'  => $name,
        'post_status' => 'publish',
    ]);

    if ($post_id) {
        studio_log_error('BOOKING: post created, about to send emails', ['post_id' => $post_id, 'admin_email' => get_option('admin_email'), 'client_email' => $email, 'smtp_password_set' => (defined('STUDIO_SMTP_PASSWORD') && !empty(STUDIO_SMTP_PASSWORD)) || (defined('STUDIO_SMTP_EMAIL') && !empty(STUDIO_SMTP_EMAIL))]);
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
        $client_portal_url = add_query_arg('token', $token, home_url('/client-area/'));

        studio_send_email(get_option('admin_email'), "New Booking Request from $name",
            '<p>New booking request received for <strong>' . esc_html($service) . '</strong>.</p>' .
            '<p><strong>Client:</strong> ' . esc_html($name) . '</p>' .
            '<p><strong>Email:</strong> ' . esc_html($email) . '</p>' .
            ($phone ? '<p><strong>Phone:</strong> ' . esc_html($phone) . '</p>' : '') .
            ($total_hours ? '<p><strong>Hours:</strong> ' . $total_hours . '</p>' : '') .
            ($notes ? '<p><strong>Notes:</strong> ' . esc_html($notes) . '</p>' : ''));

        studio_send_email($email, "Booking Request Received",
            '<p>Hi ' . esc_html($name) . ', we have received your booking request for <strong>' . esc_html($service) . '</strong>.</p><p>We will review it within 24 hours.</p>' .
            '<p><a href="' . esc_url($client_portal_url) . '">Track your booking status and session details</a></p>');

        wp_safe_redirect(add_query_arg('booking', 'sent', home_url('/booking/')));
    }
    exit;
}
add_action('init', 'studio_booking_handler', 99);

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
        $did = intval($_GET['studio_booking_decline']);
        update_post_meta($did, '_status', 'Declined');

        $d_name = get_the_title($did);
        $d_email = get_post_meta($did, '_client_email', true);
        if ($d_email) {
            studio_send_email($d_email, "Booking Request Declined",
                '<p>Hi ' . esc_html($d_name) . ',</p>' .
                '<p>We regret that we are unable to accommodate your booking request at this time.</p>');
        }
        studio_send_email(get_option('admin_email'), "Booking Declined — {$d_name}",
            '<p>Booking for <strong>' . esc_html($d_name) . '</strong> has been declined.</p>');

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
    echo '<input type="hidden" name="studio_counter_offer" value="1">';
    submit_button('Send Counter Offer', 'secondary', 'submit_counter', false);
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
    $bookings = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']);
    $booking_id = intval(get_post_meta($post->ID, '_booking_id', true));
    $unbilled_expenses = $booking_id ? get_posts([
        'post_type' => 'studio_expense',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_query' => [
            ['key' => '_booking_id', 'value' => $booking_id],
            ['key' => '_billable_to_client', 'value' => '1'],
            ['key' => '_invoice_id', 'compare' => 'NOT EXISTS'],
        ],
    ]) : [];
    $status = get_post_meta($post->ID, '_status', true) ?: 'Draft';
    $deposit_paid = floatval(get_post_meta($post->ID, '_deposit_paid', true));
    $due = get_post_meta($post->ID, '_due_date', true);
    $line_items = get_post_meta($post->ID, '_line_items', true);
    if (!is_array($line_items) || empty($line_items)) {
        $service = get_post_meta($post->ID, '_service_type', true) ?: get_post_meta($post->ID, '_service', true);
        $quantity = floatval(get_post_meta($post->ID, '_total_hours', true));
        $rate = floatval(get_post_meta($post->ID, '_rate', true));
        if ($quantity <= 0 && $rate > 0) $quantity = 1;
        $line_items = [['desc' => $service, 'qty' => $quantity, 'rate' => $rate]];
    }
    $subtotal = 0;
    foreach ($line_items as $item) $subtotal += floatval($item['qty'] ?? 0) * floatval($item['rate'] ?? 0);
    $discount = floatval(get_post_meta($post->ID, '_discount', true));
    $discount_type = get_post_meta($post->ID, '_discount_type', true) ?: 'percent';
    if ($discount_type === 'fixed') $discount_type = 'flat';
    $discount_amount = $discount_type === 'flat' ? min($subtotal, $discount) : $subtotal * $discount / 100;
    $tax_rate = floatval(get_post_meta($post->ID, '_tax_rate', true));
    $tax_amount = max(0, $subtotal - $discount_amount) * $tax_rate / 100;
    $total = max(0, $subtotal - $discount_amount) + $tax_amount;
    $deposit = round($total * 0.5, 2);
    $paid_amount = studio_invoice_paid_amount($post->ID);
    $balance_due = max(0, $total - $paid_amount);
    ?>
    <p><label for="studio-invoice-booking"><strong>Booking</strong></label><br>
        <select id="studio-invoice-booking" name="_booking_id" style="width:100%;max-width:520px;">
            <option value="0">Standalone invoice</option>
            <?php foreach ($bookings as $booking) : ?>
            <option value="<?php echo esc_attr($booking->ID); ?>" <?php selected($booking_id, $booking->ID); ?>><?php echo esc_html('#' . $booking->ID . ' - ' . get_the_title($booking->ID) . ' (' . get_post_meta($booking->ID, '_service', true) . ')'); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <h3>Invoice Line Items</h3>
    <table class="widefat striped" id="studio-invoice-line-items">
        <thead><tr><th>Description</th><th>Qty / Hours</th><th>Rate</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($line_items as $index => $item) : ?>
            <tr>
                <td><input type="text" name="_line_items[<?php echo esc_attr($index); ?>][desc]" value="<?php echo esc_attr($item['desc'] ?? ''); ?>" class="studio-invoice-desc" style="width:100%;"></td>
                <td><input type="number" step="0.25" min="0" name="_line_items[<?php echo esc_attr($index); ?>][qty]" value="<?php echo esc_attr($item['qty'] ?? 0); ?>" class="studio-invoice-qty" style="width:100%;"></td>
                <td><input type="number" step="0.01" min="0" name="_line_items[<?php echo esc_attr($index); ?>][rate]" value="<?php echo esc_attr($item['rate'] ?? 0); ?>" class="studio-invoice-rate" style="width:100%;"></td>
                <td><button type="button" class="button studio-invoice-remove-line">Remove</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><button type="button" class="button" id="studio-invoice-add-line">Add line item</button></p>
    <?php if (!empty($unbilled_expenses)) : ?>
    <p><button type="submit" class="button" name="_import_billable_expenses" value="1">Import <?php echo count($unbilled_expenses); ?> billable expense<?php echo count($unbilled_expenses) === 1 ? '' : 's'; ?></button></p>
    <?php endif; ?>
    <div style="display:grid;grid-template-columns:minmax(180px,260px) minmax(220px,1fr);gap:16px;max-width:680px;align-items:end;">
        <p><label>Discount<br><input type="number" step="0.01" min="0" name="_discount" value="<?php echo esc_attr($discount); ?>" id="studio-invoice-discount" style="width:100%;"></label></p>
        <p><label>Discount type<br><select name="_discount_type" id="studio-invoice-discount-type" style="width:100%;"><option value="percent" <?php selected($discount_type, 'percent'); ?>>Percent (%)</option><option value="flat" <?php selected($discount_type, 'flat'); ?>>Fixed amount ($)</option></select></label></p>
    </div>
    <p><label>Invoice due date<br><input type="date" name="_due_date" value="<?php echo esc_attr($due ?: date('Y-m-d', strtotime('+7 days'))); ?>"></label></p>
    <div style="max-width:680px;border-top:1px solid #c3c4c7;padding-top:10px;">
        <p>Subtotal: <strong id="studio-invoice-subtotal">$<?php echo number_format($subtotal, 2); ?></strong></p>
        <p>Discount: <strong id="studio-invoice-discount-total">-$<?php echo number_format($discount_amount, 2); ?></strong></p>
        <p>Tax: <strong id="studio-invoice-tax-total">$<?php echo number_format($tax_amount, 2); ?></strong></p>
        <p>Total: <strong id="studio-invoice-total">$<?php echo number_format($total, 2); ?></strong></p>
        <p>50% deposit: <strong id="studio-invoice-deposit">$<?php echo number_format($deposit, 2); ?></strong></p>
        <p>Status: <strong><?php echo esc_html($status); ?></strong> | Deposit: <strong><?php echo $deposit_paid ? 'Paid' : 'Unpaid'; ?></strong></p>
        <p>Paid to date: <strong>$<?php echo number_format($paid_amount, 2); ?></strong> | Balance due: <strong>$<?php echo number_format($balance_due, 2); ?></strong></p>
    </div>
    <script>
    jQuery(function($) {
        var table = $('#studio-invoice-line-items tbody');
        var nextIndex = table.find('tr').length;
        var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        var nonce = <?php echo wp_json_encode(wp_create_nonce('studio_autopopulate')); ?>;

        function recalculate() {
            var subtotal = 0;
            table.find('tr').each(function() {
                subtotal += (parseFloat($(this).find('.studio-invoice-qty').val()) || 0) * (parseFloat($(this).find('.studio-invoice-rate').val()) || 0);
            });
            var discount = parseFloat($('#studio-invoice-discount').val()) || 0;
            var discountAmount = $('#studio-invoice-discount-type').val() === 'flat' ? Math.min(subtotal, discount) : subtotal * discount / 100;
            var afterDiscount = Math.max(0, subtotal - discountAmount);
            var taxAmount = afterDiscount * (parseFloat($('input[name="_tax_rate"]').val()) || 0) / 100;
            var total = afterDiscount + taxAmount;
            $('#studio-invoice-subtotal').text('$' + subtotal.toFixed(2));
            $('#studio-invoice-discount-total').text('-$' + discountAmount.toFixed(2));
            $('#studio-invoice-tax-total').text('$' + taxAmount.toFixed(2));
            $('#studio-invoice-tax-collected').text(taxAmount.toFixed(2));
            $('#studio-invoice-total').text('$' + total.toFixed(2));
            $('#studio-invoice-deposit').text('$' + (Math.round(total * 50) / 100).toFixed(2));
        }

        table.on('input', 'input', recalculate);
        $('#studio-invoice-discount, #studio-invoice-discount-type, input[name="_tax_rate"]').on('input change', recalculate);
        $('#studio-invoice-add-line').on('click', function() {
            var row = $('<tr><td><input type="text" class="studio-invoice-desc" style="width:100%;"></td><td><input type="number" step="0.25" min="0" class="studio-invoice-qty" style="width:100%;"></td><td><input type="number" step="0.01" min="0" class="studio-invoice-rate" style="width:100%;"></td><td><button type="button" class="button studio-invoice-remove-line">Remove</button></td></tr>');
            row.find('.studio-invoice-desc').attr('name', '_line_items[' + nextIndex + '][desc]');
            row.find('.studio-invoice-qty').attr('name', '_line_items[' + nextIndex + '][qty]').val(1);
            row.find('.studio-invoice-rate').attr('name', '_line_items[' + nextIndex + '][rate]').val(0);
            nextIndex++;
            table.append(row);
        });
        table.on('click', '.studio-invoice-remove-line', function() {
            if (table.find('tr').length > 1) $(this).closest('tr').remove();
            recalculate();
        });
        $('#studio-invoice-booking').on('change', function() {
            var bookingId = $(this).val();
            if (!bookingId || bookingId === '0') return;
            $.post(ajaxUrl, {action: 'studio_invoice_autopopulate_booking', id: bookingId, nonce: nonce}, function(response) {
                if (!response.success || !response.data) return;
                var data = response.data;
                $('input[name="_client_name"]').val(data.client_name || '');
                $('input[name="_client_email"]').val(data.client_email || '');
                $('input[name="_client_phone"]').val(data.client_phone || '');
                $('input[name="_event_date"]').val(data.event_date || '');
                $('input[name="_start_time"]').val(data.start_time || '');
                $('input[name="_end_time"]').val(data.end_time || '');
                $('input[name="_venue"]').val(data.venue || '');
                $('input[name="_service_type"]').val(data.service_type || '');
                if (data.line_item_desc) table.find('.studio-invoice-desc').first().val(data.line_item_desc);
                table.find('.studio-invoice-qty').first().val(data.line_item_qty || 0);
                table.find('.studio-invoice-rate').first().val(data.line_item_rate || 0);
                $('#studio-invoice-discount').val(data.discount || 0);
                $('#studio-invoice-discount-type').val(data.discount_type === 'fixed' ? 'flat' : 'percent');
                if (data.client_name) $('#title').val('Invoice for ' + data.client_name);
                recalculate();
            }, 'json');
        });
    });
    </script>
    <?php
}
function studio_invoice_meta_boxes() {
    add_meta_box('studio_invoice_summary_display', 'Summary', 'studio_invoice_adjustments_callback', 'studio_invoice', 'normal', 'high');
    add_meta_box('studio_invoice_gig_details', 'Gig / Event Details', 'studio_invoice_gig_details_meta', 'studio_invoice', 'normal', 'default');
    add_meta_box('studio_invoice_tax', 'Tax Information', 'studio_invoice_tax_meta', 'studio_invoice', 'side', 'default');
    add_meta_box('studio_invoice_client_info', 'Client Info & Actions', 'studio_invoice_client_info_meta', 'studio_invoice', 'side', 'high');
}
add_action('add_meta_boxes', 'studio_invoice_meta_boxes');

// Hero image field for Home page
function studio_page_meta_boxes() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'page') {
        add_meta_box('studio_hero_image', 'Hero Image', function($post) {
            wp_nonce_field('studio_save_hero', '_hero_nonce');
            $url = get_post_meta($post->ID, '_hero_image_url', true);
            ?>
            <p>Paste the full URL of the hero background image:</p>
            <p><input type="url" name="_hero_image_url" value="<?php echo esc_attr($url); ?>" style="width:100%;" placeholder="https://example.com/image.jpg"></p>
            <?php if ($url) : ?>
                <p><img src="<?php echo esc_url($url); ?>" style="max-width:100%;height:auto;border-radius:8px;margin-top:8px;"></p>
            <?php endif; ?>
            <?php
        }, 'page', 'side', 'default');
    }
}
add_action('add_meta_boxes', 'studio_page_meta_boxes');

function studio_save_page_meta($post_id) {
    if (!isset($_POST['_hero_nonce']) || !wp_verify_nonce($_POST['_hero_nonce'], 'studio_save_hero')) return;
    if (isset($_POST['_hero_image_url'])) update_post_meta($post_id, '_hero_image_url', esc_url_raw($_POST['_hero_image_url']));
}
add_action('save_post', 'studio_save_page_meta');

function studio_invoice_client_info_meta($post) {
    wp_nonce_field('studio_save_invoice', '_studio_invoice_nonce');
    $client_name = get_post_meta($post->ID, '_client_name', true);
    $client_email = get_post_meta($post->ID, '_client_email', true);
    $status = get_post_meta($post->ID, '_status', true) ?: 'Draft';

    echo '<p><label><strong>Client Name:</strong><br><input type="text" name="_client_name" value="' . esc_attr($client_name) . '" style="width:100%;"></label></p>';
    echo '<p><label><strong>Client Email:</strong><br><input type="email" name="_client_email" value="' . esc_attr($client_email) . '" placeholder="client@example.com" style="width:100%;"></label></p>';

    $actions_html = '<div style="margin-top:12px;">';
    if ($status === 'Draft') {
        $nonce = wp_create_nonce('studio_invoice_submit_' . $post->ID);
        $url = admin_url("admin-post.php?action=studio_invoice_quick_submit&post_id={$post->ID}&_wpnonce=$nonce");
        $actions_html .= '<a href="' . esc_url($url) . '" class="button button-primary button-hero" style="width:100%;margin-bottom:8px;text-align:center;">Send to Client</a>';
    }
    if ($status !== 'Paid') {
        $nonce = wp_create_nonce('studio_invoice_mark_paid_' . $post->ID);
        $url = admin_url("admin-post.php?action=studio_invoice_quick_paid&post_id={$post->ID}&_wpnonce=$nonce");
        $actions_html .= '<a href="' . esc_url($url) . '" class="button button-secondary button-hero" style="width:100%;text-align:center;">Mark Paid</a>';
    }
    $actions_html .= '</div>';
    echo $actions_html;
}

function studio_invoice_gig_details_meta($post) {
    $event_date = get_post_meta($post->ID, '_event_date', true);
    $start_time = get_post_meta($post->ID, '_start_time', true);
    $end_time = get_post_meta($post->ID, '_end_time', true);
    $venue = get_post_meta($post->ID, '_venue', true);
    $service_type = get_post_meta($post->ID, '_service_type', true);
    $client_phone = get_post_meta($post->ID, '_client_phone', true);
    ?>
    <p><label>Event Date: <input type="date" name="_event_date" value="<?php echo esc_attr($event_date); ?>" style="width:100%;"></label></p>
    <p><label>Start Time: <input type="time" name="_start_time" value="<?php echo esc_attr($start_time); ?>" style="width:100%;"></label></p>
    <p><label>End Time: <input type="time" name="_end_time" value="<?php echo esc_attr($end_time); ?>" style="width:100%;"></label></p>
    <p><label>Venue / Location: <input type="text" name="_venue" value="<?php echo esc_attr($venue); ?>" placeholder="Studio or venue address" style="width:100%;"></label></p>
    <p><label>Service Type: <input type="text" name="_service_type" value="<?php echo esc_attr($service_type); ?>" placeholder="e.g. FOH Engineer, Recording" style="width:100%;"></label></p>
    <p><label>Client Phone: <input type="tel" name="_client_phone" value="<?php echo esc_attr($client_phone); ?>" placeholder="(555) 555-5555" style="width:100%;"></label></p>
    <?php
}

function studio_invoice_tax_meta($post) {
    $tax_rate = get_post_meta($post->ID, '_tax_rate', true) ?: '0';
    $tax_collected = get_post_meta($post->ID, '_tax_collected', true) ?: '0';
    $tax_jurisdiction = get_post_meta($post->ID, '_tax_jurisdiction', true);
    ?>
    <p><label>Tax Rate (%): <input type="number" step="0.001" name="_tax_rate" value="<?php echo esc_attr($tax_rate); ?>" style="width:100%;"></label></p>
    <p><strong>Calculated tax:</strong> $<span id="studio-invoice-tax-collected"><?php echo number_format(floatval($tax_collected), 2); ?></span></p>
    <p><label>Tax Jurisdiction: <input type="text" name="_tax_jurisdiction" value="<?php echo esc_attr($tax_jurisdiction); ?>" placeholder="e.g. Texas" style="width:100%;"></label></p>
    <?php
}

function studio_import_billable_expenses($invoice_id, $booking_id, $line_items) {
    $expense_ids = get_posts([
        'post_type' => 'studio_expense',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_query' => [
            ['key' => '_booking_id', 'value' => $booking_id],
            ['key' => '_billable_to_client', 'value' => '1'],
            ['key' => '_invoice_id', 'compare' => 'NOT EXISTS'],
        ],
    ]);
    $imported = 0;
    foreach ($expense_ids as $expense_id) {
        $amount = max(0, floatval(get_post_meta($expense_id, '_expense_amount', true)));
        if ($amount <= 0) continue;
        $category = get_post_meta($expense_id, '_expense_category', true);
        $description = ($category ? $category . ': ' : '') . get_the_title($expense_id);
        $line_items[] = ['desc' => sanitize_text_field($description), 'qty' => 1, 'rate' => $amount];
        update_post_meta($expense_id, '_invoice_id', $invoice_id);
        $imported++;
    }
    return [$line_items, $imported];
}

function studio_finalize_booking_invoice($booking_id) {
    $invoice_id = intval(get_post_meta($booking_id, '_invoice_id', true));
    if (!$invoice_id || get_post_type($invoice_id) !== 'studio_invoice') return 0;
    $line_items = get_post_meta($invoice_id, '_line_items', true);
    if (!is_array($line_items)) $line_items = [];
    if (empty($line_items)) {
        $service = get_post_meta($invoice_id, '_service_type', true) ?: get_post_meta($invoice_id, '_service', true);
        $quantity = floatval(get_post_meta($invoice_id, '_total_hours', true));
        $rate = floatval(get_post_meta($invoice_id, '_rate', true));
        if ($quantity <= 0 && $rate > 0) $quantity = 1;
        if ($service && $quantity > 0) $line_items[] = ['desc' => $service, 'qty' => $quantity, 'rate' => $rate];
    }
    $paid_amount = studio_invoice_paid_amount($invoice_id);
    [$line_items, $imported] = studio_import_billable_expenses($invoice_id, $booking_id, $line_items);
    if (!$imported) return 0;

    $subtotal = 0;
    foreach ($line_items as $item) $subtotal += floatval($item['qty'] ?? 0) * floatval($item['rate'] ?? 0);
    $discount = max(0, floatval(get_post_meta($invoice_id, '_discount', true)));
    $discount_type = get_post_meta($invoice_id, '_discount_type', true) ?: 'percent';
    $discount_amount = $discount_type === 'flat' || $discount_type === 'fixed'
        ? min($subtotal, $discount)
        : min($subtotal, $subtotal * $discount / 100);
    $tax_rate = max(0, floatval(get_post_meta($invoice_id, '_tax_rate', true)));
    $tax_amount = max(0, $subtotal - $discount_amount) * $tax_rate / 100;
    $total = max(0, $subtotal - $discount_amount) + $tax_amount;
    $deposit = round($total * 0.5, 2);
    $balance = max(0, $total - $paid_amount);

    update_post_meta($invoice_id, '_line_items', $line_items);
    update_post_meta($invoice_id, '_subtotal', $subtotal);
    update_post_meta($invoice_id, '_discount_amount', $discount_amount);
    update_post_meta($invoice_id, '_tax_collected', $tax_amount);
    update_post_meta($invoice_id, '_total', $total);
    update_post_meta($invoice_id, '_deposit', $deposit);
    update_post_meta($invoice_id, '_paid_amount', $paid_amount);
    update_post_meta($invoice_id, '_deposit_paid', $paid_amount >= $deposit ? '1' : '0');
    update_post_meta($invoice_id, '_status', $balance <= 0 ? 'Paid' : 'Draft');
    if ($balance > 0) delete_post_meta($invoice_id, '_paid_date');
    update_post_meta($booking_id, '_deposit_paid', $paid_amount >= $deposit ? '1' : '0');
    studio_cache_flush('studio_dashboard_overview');
    return $imported;
}

function studio_save_invoice_tax_meta($post_id) {
    if (!isset($_POST['_studio_invoice_nonce']) || !wp_verify_nonce($_POST['_studio_invoice_nonce'], 'studio_save_invoice')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $old_line_items = get_post_meta($post_id, '_line_items', true);
    if (!is_array($old_line_items)) $old_line_items = [];
    $old_discount = floatval(get_post_meta($post_id, '_discount', true));
    $old_discount_type = get_post_meta($post_id, '_discount_type', true) ?: 'percent';
    $old_tax_rate = floatval(get_post_meta($post_id, '_tax_rate', true));
    $old_booking_id = intval(get_post_meta($post_id, '_booking_id', true));
    $old_invoice_status = get_post_meta($post_id, '_status', true) ?: 'Draft';
    $paid_before_edit = studio_invoice_paid_amount($post_id);
    $invoice_fields = ['_client_name', '_client_email', '_event_date', '_start_time', '_end_time', '_venue', '_service_type', '_client_phone', '_tax_jurisdiction', '_due_date'];
    $old_invoice_fields = [];
    foreach ($invoice_fields as $field) $old_invoice_fields[$field] = get_post_meta($post_id, $field, true);
    // Client info
    if (isset($_POST['_client_name'])) update_post_meta($post_id, '_client_name', sanitize_text_field($_POST['_client_name']));
    if (isset($_POST['_client_email'])) update_post_meta($post_id, '_client_email', sanitize_email($_POST['_client_email']));
    $booking_id = intval($_POST['_booking_id'] ?? 0);
    if ($booking_id && get_post_type($booking_id) === 'studio_booking') update_post_meta($post_id, '_booking_id', $booking_id);
    else delete_post_meta($post_id, '_booking_id');
    // Gig / event details
    $gig_fields = ['_event_date', '_start_time', '_end_time', '_venue', '_service_type', '_client_phone'];
    foreach ($gig_fields as $f) {
        if (isset($_POST[$f])) update_post_meta($post_id, $f, sanitize_text_field($_POST[$f]));
    }
    // Tax fields
    if (isset($_POST['_tax_rate'])) update_post_meta($post_id, '_tax_rate', max(0, floatval($_POST['_tax_rate'])));
    if (isset($_POST['_tax_jurisdiction'])) update_post_meta($post_id, '_tax_jurisdiction', sanitize_text_field($_POST['_tax_jurisdiction']));
    if (isset($_POST['_line_items']) && is_array($_POST['_line_items'])) {
        $line_items = [];
        foreach (wp_unslash($_POST['_line_items']) as $item) {
            $description = sanitize_text_field($item['desc'] ?? '');
            $quantity = max(0, floatval($item['qty'] ?? 0));
            $rate = max(0, floatval($item['rate'] ?? 0));
            if ($description !== '' && $quantity > 0) $line_items[] = ['desc' => $description, 'qty' => $quantity, 'rate' => $rate];
        }
        $booking_id = intval($_POST['_booking_id'] ?? 0);
        if (isset($_POST['_import_billable_expenses']) && $booking_id && get_post_type($booking_id) === 'studio_booking') {
            [$line_items] = studio_import_billable_expenses($post_id, $booking_id, $line_items);
        }
        update_post_meta($post_id, '_line_items', $line_items);
        $subtotal = 0;
        foreach ($line_items as $item) $subtotal += $item['qty'] * $item['rate'];
        $discount = max(0, floatval($_POST['_discount'] ?? 0));
        $discount_type = sanitize_text_field($_POST['_discount_type'] ?? 'percent');
        if (!in_array($discount_type, ['percent', 'flat'], true)) $discount_type = 'percent';
        $discount_amount = $discount_type === 'flat' ? min($subtotal, $discount) : min($subtotal, $subtotal * $discount / 100);
        $tax_rate = max(0, floatval($_POST['_tax_rate'] ?? 0));
        $tax_amount = max(0, $subtotal - $discount_amount) * $tax_rate / 100;
        $total = max(0, $subtotal - $discount_amount) + $tax_amount;
        update_post_meta($post_id, '_subtotal', $subtotal);
        update_post_meta($post_id, '_discount', $discount);
        update_post_meta($post_id, '_discount_type', $discount_type);
        update_post_meta($post_id, '_discount_amount', $discount_amount);
        update_post_meta($post_id, '_tax_collected', $tax_amount);
        update_post_meta($post_id, '_total', $total);
        update_post_meta($post_id, '_deposit', round($total * 0.5, 2));

        $invoice_changed = serialize($old_line_items) !== serialize($line_items)
            || $old_discount !== $discount
            || $old_discount_type !== $discount_type
            || $old_tax_rate !== $tax_rate
            || $old_booking_id !== $booking_id;
        foreach ($old_invoice_fields as $field => $old_value) {
            if (isset($_POST[$field]) && $old_value !== sanitize_text_field(wp_unslash($_POST[$field]))) $invoice_changed = true;
        }
        if ($invoice_changed && in_array($old_invoice_status, ['Sent', 'Partially Paid', 'Paid'], true)) {
            $new_status = $total > 0 && $paid_before_edit >= $total ? 'Paid' : 'Draft';
            update_post_meta($post_id, '_status', $new_status);
            if ($new_status !== 'Paid') delete_post_meta($post_id, '_paid_date');
        }
    }
    if (isset($_POST['_due_date'])) update_post_meta($post_id, '_due_date', sanitize_text_field($_POST['_due_date']));
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
        wp_nonce_field('studio_save_expense', '_studio_expense_nonce');
        $cats = ['Equipment', 'Software', 'Travel', 'Venue', 'Marketing', 'Supplies', 'Utilities', 'Maintenance', 'Other'];
        $current = get_post_meta($post->ID, '_expense_category', true);
        $tax_cats = ['Operating Expense', 'Capital Expenditure', 'Home Office', 'Vehicle', 'Professional Development', 'Insurance', 'Not Deductible'];
        $tax_cat = get_post_meta($post->ID, '_tax_category', true);
        $deductible = get_post_meta($post->ID, '_tax_deductible', true);
        $receipt_url = get_post_meta($post->ID, '_receipt_url', true);
        $booking_id = intval(get_post_meta($post->ID, '_booking_id', true));
        $billable = get_post_meta($post->ID, '_billable_to_client', true);
        $bookings = get_posts(['post_type' => 'studio_booking', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']);
        ?>
        <p><label>Related Booking: <select name="_booking_id" style="width:100%;">
            <option value="0">Studio / not job-related</option>
            <?php foreach ($bookings as $booking) : ?>
                <option value="<?php echo esc_attr($booking->ID); ?>" <?php selected($booking_id, $booking->ID); ?>><?php echo esc_html('#' . $booking->ID . ' - ' . get_the_title($booking->ID)); ?></option>
            <?php endforeach; ?>
        </select></label></p>
        <p><label><input type="checkbox" name="_billable_to_client" value="1" <?php checked($billable, '1'); ?>> Billable to client</label></p>
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
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $booking_id = intval($_POST['_booking_id'] ?? 0);
    if ($booking_id && get_post_type($booking_id) === 'studio_booking') update_post_meta($post_id, '_booking_id', $booking_id);
    else delete_post_meta($post_id, '_booking_id');
    if (isset($_POST['_billable_to_client'])) update_post_meta($post_id, '_billable_to_client', '1');
    else delete_post_meta($post_id, '_billable_to_client');
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
        'public' => false, 'show_ui' => true, 'show_in_menu' => true,
        'publicly_queryable' => false,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => false,
    ]);
}
add_action('init', 'studio_gig_cpt');

function studio_gig_meta_boxes() {
    add_meta_box('studio_gig_details', 'Gig Details', function($post) {
        wp_nonce_field('studio_save_gig', '_studio_gig_nonce');
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
        wp_nonce_field('studio_save_promo', '_studio_promo_nonce');
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
    if (!current_user_can('manage_options')) return;
    $status = get_post_meta($post->ID, '_status', true) ?: 'New';
    echo '<p><strong>Status:</strong> ' . esc_html($status) . '</p>';
    $invoice_id = intval(get_post_meta($post->ID, '_invoice_id', true));
    if ($invoice_id) echo '<p><a href="' . esc_url(get_edit_post_link($invoice_id)) . '" class="button" style="width:100%;text-align:center;">Review Invoice #' . $invoice_id . '</a></p>';
    if ($status === 'New' || $status === 'Pending') {
        $approve_nonce = wp_create_nonce('studio_booking_approve_' . $post->ID);
        $decline_nonce = wp_create_nonce('studio_booking_decline_' . $post->ID);
        $approve_url = admin_url("admin-post.php?action=studio_booking_quick_approve&post_id={$post->ID}&_wpnonce={$approve_nonce}");
        $decline_url = admin_url("admin-post.php?action=studio_booking_quick_decline&post_id={$post->ID}&_wpnonce={$decline_nonce}");
        echo '<p><a href="' . esc_url($approve_url) . '" class="button button-primary" style="width:100%;text-align:center;">Approve</a></p>';
        echo '<p><a href="' . esc_url($decline_url) . '" class="button" style="width:100%;text-align:center;">Decline</a></p>';

        $disc = floatval(get_post_meta($post->ID, '_discount', true));
        $dtype = get_post_meta($post->ID, '_discount_type', true) ?: 'percent';
        $disc_nonce = wp_create_nonce('studio_booking_approve_discount_' . $post->ID);
        echo '<hr style="margin:12px 0;border:none;border-top:1px solid #e0e0e0;">';
        echo '<p style="margin-bottom:6px;"><a href="#" onclick="return confirm(\'Approve this booking now? An invoice will be created and the client will be emailed a PayPal deposit link.\')"><strong style="font-size:12px;">Approve with Discount</strong></a></p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="studio_booking_approve_discount">';
        echo '<input type="hidden" name="post_id" value="' . esc_attr($post->ID) . '">';
        echo '<input type="hidden" name="approve_discount_nonce" value="' . esc_attr($disc_nonce) . '">';
        echo '<p style="margin:2px 0;"><label style="font-size:11px;">Type<br><select name="approve_discount_type" style="width:100%;">
            <option value="percent" ' . selected($dtype, 'percent', false) . '>Percent (%)</option>
            <option value="flat" ' . selected($dtype, 'flat', false) . '>Flat ($)</option>
        </select></label></p>';
        echo '<p style="margin:2px 0;"><label style="font-size:11px;">Value<br><input type="number" step="0.01" min="0" name="approve_discount" value="' . esc_attr($disc ?: '') . '" placeholder="e.g. 10" style="width:100%;"></label></p>';
        echo '<p><button type="submit" class="button button-primary" style="width:100%;min-width:0;padding:6px 10px;font-size:12px;">Approve Now</button></p>';
        echo '<p><em style="font-size:10px;color:#888;">Leave value blank for full price.</em></p>';
        echo '</form>';
    } elseif ($status === 'Approved') {
        echo '<p style="margin-bottom:6px;"><strong style="font-size:12px;">Save &amp; Resend</strong></p>';
        echo '<p><em style="font-size:11px;color:#666;">In Booking Details, select “Email updated booking details” and click Update to save and send the client a fresh deposit link.</em></p>';
        echo '<hr style="margin:12px 0;border:none;border-top:1px solid #e0e0e0;">';
        $reset_nonce = wp_create_nonce('studio_booking_reset_' . $post->ID);
        $reset_url = admin_url("admin-post.php?action=studio_booking_reset&post_id={$post->ID}&_wpnonce=$reset_nonce");
        echo '<p><a href="' . esc_url($reset_url) . '" class="button" style="width:100%;text-align:center;">Reset to Pending</a></p>';
    } elseif ($status === 'Declined') {
        $reset_nonce = wp_create_nonce('studio_booking_reset_' . $post->ID);
        $reset_url = admin_url("admin-post.php?action=studio_booking_reset&post_id={$post->ID}&_wpnonce=$reset_nonce");
        echo '<p><a href="' . esc_url($reset_url) . '" class="button" style="width:100%;text-align:center;">Reset to Pending</a></p>';
    }
}

/* ----------------------------------------------
   EQUIPMENT CPT + TAXONOMY
   ---------------------------------------------- */
function studio_equipment_cpt() {
    register_post_type('studio_equipment', [
        'labels' => ['name' => 'Equipment', 'singular_name' => 'Equipment', 'menu_name' => 'Equipment', 'add_new_item' => 'Add Equipment'],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-slides',
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => false,
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
// Flush rewrite rules once then auto-remove (fixes /equipment/ collision)
function studio_flush_rewrite_equipment() {
    if ('1' === get_option('studio_rewrite_flush_done')) return;
    flush_rewrite_rules();
    update_option('siteurl', get_option('siteurl')); // mark done via separate hook below
}
add_action('after_setup_theme', 'studio_flush_rewrite_equipment');

function studio_mark_rewrite_flush_done() {
    update_option('studio_rewrite_flush_done', '1');
}
add_action('shutdown', 'studio_mark_rewrite_flush_done');

function studio_equipment_meta_boxes() {
    add_meta_box('studio_equipment_details', 'Equipment Details', function($post) {
        wp_nonce_field('studio_save_equipment', '_studio_equipment_nonce');
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
	if (!$booking_id) return;

	$new_rate   = floatval($_POST['co_rate'] ?? 0);
	$new_hours  = floatval($_POST['co_hours'] ?? get_post_meta($booking_id, '_total_hours', true));
	$message    = sanitize_textarea_field($_POST['co_message'] ?? '');

	update_post_meta($booking_id, '_status', 'Counter-Offer');
	update_post_meta($booking_id, '_counter_rate', $new_rate);
	update_post_meta($booking_id, '_counter_hours', $new_hours);
	update_post_meta($booking_id, '_counter_message', $message);

	studio_cache_flush('studio_dashboard_overview');

	$client_email = get_post_meta($booking_id, '_client_email', true);
	$name = get_the_title($booking_id);
    $token = get_post_meta($booking_id, '_access_token', true) ?: studio_generate_token($booking_id);

    if ($client_email) {
        $accept_url = home_url("/booking-action/?action=accept_counter&booking_id=$booking_id&token=$token");
        $decline_url = home_url("/booking-action/?action=decline_counter&booking_id=$booking_id&token=$token");
        studio_send_email($client_email, "Counter Offer for Your Booking",
            '<p>Hi ' . esc_html($name) . ', we have a counter offer for your booking:</p>' .
            '<p><strong>New total:</strong> $' . number_format((float) $new_rate * (float) $new_hours, 2) . '</p>' .
            ($message ? '<p><strong>Note:</strong> ' . esc_html($message) . '</p>' : '') .
            '<p><a href="' . $accept_url . '" style="display:inline-block;padding:12px 32px;background:#d4a574;color:#0a0a0a;text-decoration:none;border-radius:8px;font-weight:bold;">Accept Counter Offer</a></p>' .
            '<p style="margin-top:12px;"><a href="' . $decline_url . '" style="color:#888;">Decline Counter Offer</a></p>');

        studio_send_email(get_option('admin_email'), "Counter Offer Sent — Booking #{$booking_id}",
            '<p>Counter offer for <strong>' . esc_html($name) . '</strong>: $' . number_format($new_rate, 2) . '/hr × ' . $new_hours . ' hrs.</p>');
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

    if ($action === 'download_invoice_pdf') {
        $invoice_id = intval($_GET['invoice_id'] ?? 0);
        $token = sanitize_text_field($_GET['token'] ?? '');
        $booking_id = intval(get_post_meta($invoice_id, '_booking_id', true));
        $valid = $booking_id && studio_verify_token($booking_id, $token);
        if (!$valid) $valid = studio_verify_token($invoice_id, $token);
        if (!$invoice_id || get_post_type($invoice_id) !== 'studio_invoice' || !$valid) {
            wp_die('Invalid or expired access token.');
        }
        $pdf_path = studio_invoice_pdf_temp_file($invoice_id);
        if (!$pdf_path) wp_die('The invoice PDF could not be generated. Please contact the studio.');
        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Invoice-' . $invoice_id . '.pdf"');
        header('Content-Length: ' . filesize($pdf_path));
        readfile($pdf_path);
        @unlink($pdf_path);
        exit;
    }

    if ($action === 'pay_deposit') {
        $invoice_id = intval($_GET['invoice_id'] ?? 0);
        $token = sanitize_text_field($_GET['token'] ?? '');
        if ($invoice_id && $token) {
            $booking_id = intval(get_post_meta($invoice_id, '_booking_id', true));
            $valid = false;
            if ($booking_id) {
                $valid = studio_verify_token($booking_id, $token);
            }
            if (!$valid) {
                $valid = studio_verify_token($invoice_id, $token);
            }
            if (!$valid) {
                wp_die('Invalid or expired access token.');
            }
            $total = floatval(get_post_meta($invoice_id, '_total', true));
            $deposit = floatval(get_post_meta($invoice_id, '_deposit', true));
            $paid = floatval(get_post_meta($invoice_id, '_paid_amount', true));
            if (get_post_meta($invoice_id, '_status', true) === 'Paid' && $paid <= 0) $paid = $total;
            $deposit_paid = get_post_meta($invoice_id, '_deposit_paid', true);
            $amount_due = $deposit_paid ? max(0, $total - $paid) : max(0, min($deposit ?: $total * 0.5, $total) - $paid);
            $amount_due = round($amount_due, 2);
            if ($amount_due <= 0) wp_die('There is no outstanding balance on this invoice.');
            $invoice_name = 'Roux Audio Production Invoice #' . $invoice_id;
            if ($deposit_paid) $invoice_name .= ' - Balance';
            $redirect = add_query_arg([
                'cmd' => '_xclick',
                'business' => 'jesseroux87@gmail.com',
                'amount' => number_format($amount_due, 2, '.', ''),
                'currency_code' => 'USD',
                'item_name' => $invoice_name,
                'item_number' => $invoice_id,
                'invoice' => $invoice_id,
                'custom' => $invoice_id,
                'no_note' => '1',
                'notify_url' => home_url('/?studio_paypal_ipn=1'),
            ], 'https://www.paypal.com/cgi-bin/webscr');
            wp_redirect($redirect);
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
            $name = get_the_title($booking_id);
            $email = get_post_meta($booking_id, '_client_email', true);
            if ($email) {
                studio_send_email($email, "Counter Offer Declined",
                    '<p>Hi ' . esc_html($name) . ',</p>' .
                    '<p>We were unable to reach an agreement on the counter offer. Your booking request has been declined.</p>');
            }
            studio_send_email(get_option('admin_email'), "Counter Offer Declined — " . esc_html($name),
                '<p>The counter offer for <strong>' . esc_html($name) . '</strong> has been declined by the client.</p>');
            wp_redirect(home_url('/booking/?booking=sent'));
            exit;
        }
        wp_die('Invalid or expired access token.');
    }
}
add_action('template_redirect', 'studio_booking_action_handler');

function studio_paypal_ipn_handler() {
    if (!isset($_GET['studio_paypal_ipn'])) return;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        status_header(405);
        exit;
    }

    $raw_body = file_get_contents('php://input');
    $verification = wp_remote_post('https://ipnpb.paypal.com/cgi-bin/webscr', [
        'body' => 'cmd=_notify-validate&' . $raw_body,
        'timeout' => 20,
    ]);
    if (is_wp_error($verification)) {
        studio_log_error('PayPal IPN verification request failed', ['error' => $verification->get_error_message()]);
        status_header(503);
        exit;
    }
    if (trim(wp_remote_retrieve_body($verification)) !== 'VERIFIED') {
        status_header(200);
        exit;
    }

    parse_str($raw_body, $payment);
    $receiver = strtolower(sanitize_email($payment['receiver_email'] ?? $payment['business'] ?? ''));
    $transaction_id = sanitize_text_field($payment['txn_id'] ?? '');
    $invoice_id = intval($payment['custom'] ?? 0);
    $amount = floatval($payment['mc_gross'] ?? $payment['payment_gross'] ?? 0);
    if ($receiver !== 'jesseroux87@gmail.com' || ($payment['payment_status'] ?? '') !== 'Completed'
        || strtoupper($payment['mc_currency'] ?? '') !== 'USD' || !$transaction_id || !$invoice_id || $amount <= 0
        || get_post_type($invoice_id) !== 'studio_invoice') {
        studio_log_error('PayPal IPN ignored after verification', ['invoice_id' => $invoice_id, 'payment_status' => $payment['payment_status'] ?? '', 'currency' => $payment['mc_currency'] ?? '']);
        status_header(200);
        exit;
    }

    $transaction_key = '_paypal_txn_' . md5($transaction_id);
    if (!add_post_meta($invoice_id, $transaction_key, $amount, true)) {
        status_header(200);
        exit;
    }

    $paid_amount = floatval(get_post_meta($invoice_id, '_paid_amount', true)) + $amount;
    $total = floatval(get_post_meta($invoice_id, '_total', true));
    $deposit = floatval(get_post_meta($invoice_id, '_deposit', true));
    $balance = max(0, $total - $paid_amount);
    $deposit_paid = $paid_amount >= $deposit;
    update_post_meta($invoice_id, '_paid_amount', $paid_amount);
    update_post_meta($invoice_id, '_deposit_paid', $deposit_paid ? '1' : '0');
    update_post_meta($invoice_id, '_status', $balance <= 0 ? 'Paid' : 'Partially Paid');
    if ($balance <= 0) update_post_meta($invoice_id, '_paid_date', current_time('mysql'));

    $booking_id = intval(get_post_meta($invoice_id, '_booking_id', true));
    if ($booking_id) {
        update_post_meta($booking_id, '_deposit_paid', $deposit_paid ? '1' : '0');
        if ($balance <= 0 && get_post_meta($booking_id, '_job_status', true) === 'Completed') {
            studio_set_booking_job_status($booking_id, 'Closed', false);
        }
    }
    studio_cache_flush('studio_dashboard_overview');

    $client_email = get_post_meta($invoice_id, '_client_email', true);
    $client_name = get_post_meta($invoice_id, '_client_name', true);
    $receipt = '<p>Hi ' . esc_html($client_name) . ',</p>'
        . '<p>We received your payment of $' . number_format($amount, 2) . ' for invoice #' . $invoice_id . '.</p>'
        . '<p>Paid to date: $' . number_format($paid_amount, 2) . '. Balance due: $' . number_format($balance, 2) . '.</p>';
    if ($client_email) studio_send_email($client_email, 'Payment received — Invoice #' . $invoice_id, $receipt);
    studio_send_email(get_option('admin_email'), 'PayPal payment received — Invoice #' . $invoice_id, $receipt);
    status_header(200);
    exit;
}
add_action('init', 'studio_paypal_ipn_handler', 1);

/* ----------------------------------------------
   BOOKING APPROVAL + INVOICE GENERATION
   ---------------------------------------------- */
function studio_do_approve($post_id, $discount = 0, $discount_type = 'percent', $override_rate = null) {
    update_post_meta($post_id, '_status', 'Approved');
    update_post_meta($post_id, '_discount', $discount);
    update_post_meta($post_id, '_discount_type', $discount_type);
    if (!get_post_meta($post_id, '_job_status', true)) studio_set_booking_job_status($post_id, 'Scheduled', false);

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

    update_post_meta($post_id, '_rate', $rate);

    $slots = get_post_meta($post_id, '_booking_slots', true);
    $first_slot = is_array($slots) && !empty($slots[0]) ? $slots[0] : null;
    $location = get_post_meta($post_id, '_location', true);
    $gig_id = intval(get_post_meta($post_id, '_gig_id', true));
    if ($first_slot && !empty($first_slot['date'])) {
        if (!$gig_id || get_post_type($gig_id) !== 'studio_gig') {
            $gig_id = wp_insert_post([
                'post_type' => 'studio_gig',
                'post_title' => $service . ' - ' . $name,
                'post_status' => 'publish',
            ]);
        } else {
            wp_update_post(['ID' => $gig_id, 'post_title' => $service . ' - ' . $name]);
        }
        if ($gig_id && !is_wp_error($gig_id)) {
            update_post_meta($gig_id, '_gig_date', $first_slot['date']);
            update_post_meta($gig_id, '_gig_venue', $location);
            update_post_meta($gig_id, '_gig_time', ($first_slot['start'] ?? '') . '–' . ($first_slot['end'] ?? ''));
            update_post_meta($gig_id, '_booking_id', $post_id);
            update_post_meta($post_id, '_gig_id', $gig_id);
        }
    }

    $subtotal = $rate * $total_hours;
    $discount_amount = 0;
    if ($discount > 0) {
        $discount_amount = ($discount_type === 'percent') ? $subtotal * ($discount / 100) : $discount;
    }
    $total = max(0, $subtotal - $discount_amount);
    $deposit = round($total * 0.5, 2);
    $token = get_post_meta($post_id, '_access_token', true) ?: studio_generate_token($post_id);
    $client_portal_url = add_query_arg('token', $token, home_url('/client-area/'));

    // Skip invoice generation for free services
    if ($total <= 0) {
        if ($client_email) {
            studio_send_email($client_email, "Booking Approved!",
                '<p>Great news, ' . esc_html($name) . '! Your booking for <strong>' . esc_html($service) . '</strong> has been approved.</p>' .
                '<p>This is a complimentary session. No payment required.</p>' .
                '<p><a href="' . esc_url($client_portal_url) . '">View your booking status and session details</a></p>');
        }
        return;
    }

    $invoice_id = wp_insert_post(['post_type' => 'studio_invoice', 'post_title' => "Invoice for $name", 'post_status' => 'publish']);
    if ($invoice_id) {
        $meta = [
            '_booking_id' => $post_id, '_client_email' => $client_email, '_client_name' => $name,
            '_client_phone' => get_post_meta($post_id, '_client_phone', true),
            '_event_date' => $first_slot['date'] ?? '', '_start_time' => $first_slot['start'] ?? '',
            '_end_time' => $first_slot['end'] ?? '', '_venue' => $location,
            '_service_type' => $service, '_service' => $service, '_rate' => $rate, '_total_hours' => $total_hours,
            '_line_items' => [['desc' => $service, 'qty' => $total_hours, 'rate' => $rate]],
            '_subtotal' => $subtotal, '_discount' => $discount, '_discount_type' => $discount_type,
            '_discount_amount' => $discount_amount, '_total' => $total, '_deposit' => $deposit,
            '_deposit_paid' => 0, '_paid_amount' => 0, '_status' => 'Draft',
            '_due_date' => date('Y-m-d', strtotime('+7 days')),
            '_created_at' => current_time('mysql'),
        ];
        foreach ($meta as $k => $v) update_post_meta($invoice_id, $k, $v);
        update_post_meta($post_id, '_invoice_id', $invoice_id);
        if ($gig_id && !is_wp_error($gig_id)) update_post_meta($gig_id, '_invoice_id', $invoice_id);
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
            '<p><a href="' . esc_url($client_portal_url) . '">View your booking and invoice status</a></p>' .
            $calendar_section);

        studio_send_email(get_option('admin_email'), "Booking Approved — {$name}",
            '<p>Booking for <strong>' . esc_html($name) . '</strong> (<strong>' . esc_html($service) . '</strong>) has been approved.</p>' .
            '<p><strong>Total:</strong> $' . number_format($total, 2) . ' | <strong>Deposit:</strong> $' . number_format($deposit, 2) . '</p>');
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

    // N+1 fix: bulk meta queries with get_post_meta() called once per post via object cache
    $revenue = 0;
    $paid_invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1, 'date_query' => [['after' => $month_start, 'before' => $month_end, 'inclusive' => true]], 'fields' => 'ids']);
    foreach ($paid_invoices as $inv_id) $revenue += studio_invoice_paid_amount($inv_id);

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

function studio_financial_svg_data() {
    static $data = null;
    if ($data !== null) return $data;

    $month_start = date('Y-m-01');
    $month_end = date('Y-m-t');
    $revenue = 0;
    $paid_invoices = get_posts([
        'post_type' => 'studio_invoice',
        'post_status' => 'publish',
        'numberposts' => -1,
        'date_query' => [['after' => $month_start, 'before' => $month_end, 'inclusive' => true]],
        'fields' => 'ids',
    ]);
    foreach ($paid_invoices as $invoice_id) {
        $revenue += studio_invoice_paid_amount($invoice_id);
    }

    $expenses = 0;
    $month_expenses = get_posts([
        'post_type' => 'studio_expense',
        'post_status' => 'publish',
        'numberposts' => -1,
        'date_query' => [['after' => $month_start, 'before' => $month_end, 'inclusive' => true]],
        'fields' => 'ids',
    ]);
    foreach ($month_expenses as $expense_id) {
        $expenses += floatval(get_post_meta($expense_id, '_expense_amount', true));
    }

    $approved = 0;
    $approved_bookings = get_posts([
        'post_type' => 'studio_booking',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_key' => '_status',
        'meta_value' => 'Approved',
        'fields' => 'ids',
    ]);
    foreach ($approved_bookings as $booking_id) {
        $slots = get_post_meta($booking_id, '_booking_slots', true);
        if (!is_array($slots)) continue;
        foreach ($slots as $slot) {
            $slot_date = $slot['date'] ?? '';
            if ($slot_date >= $month_start && $slot_date <= $month_end) $approved++;
        }
    }

    $data = [
        'revenue' => $revenue,
        'expenses' => $expenses,
        'net' => $revenue - $expenses,
        'approved' => $approved,
    ];
    return $data;
}

function studio_render_finance_svg($data = null) {
    $data = is_array($data) ? $data : studio_financial_svg_data();
    $revenue = max(0, floatval($data['revenue'] ?? 0));
    $expenses = max(0, floatval($data['expenses'] ?? 0));
    $maximum = max($revenue, $expenses, 1);
    $revenue_width = round(210 * $revenue / $maximum, 2);
    $expenses_width = round(210 * $expenses / $maximum, 2);

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 76" width="360" height="76" role="img" aria-label="Monthly revenue and expense comparison">'
        . '<title>Monthly revenue and expenses</title>'
        . '<text x="0" y="24" font-size="12" fill="#555">Revenue</text>'
        . '<rect x="78" y="10" width="210" height="18" rx="3" fill="#e8e8e8"/>'
        . '<rect x="78" y="10" width="' . $revenue_width . '" height="18" rx="3" fill="#d4a574"/>'
        . '<text x="356" y="24" text-anchor="end" font-size="12" fill="#333">$' . number_format($revenue, 0) . '</text>'
        . '<text x="0" y="60" font-size="12" fill="#555">Expenses</text>'
        . '<rect x="78" y="46" width="210" height="18" rx="3" fill="#e8e8e8"/>'
        . '<rect x="78" y="46" width="' . $expenses_width . '" height="18" rx="3" fill="#dc3545"/>'
        . '<text x="356" y="60" text-anchor="end" font-size="12" fill="#333">$' . number_format($expenses, 0) . '</text>'
        . '</svg>';
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
   ROW ACTIONS — BOOKINGS (inline Approve/Decline)
   ---------------------------------------------- */
function studio_booking_row_actions($actions, $post) {
    if (!current_user_can('manage_options')) return $actions;
    $status = get_post_meta($post->ID, '_status', true) ?: 'New';

    unset($actions['inline hide-if-no-js']);

    if ($status === 'Pending' || $status === 'New') {
        $approve_nonce = wp_create_nonce('studio_booking_approve_' . $post->ID);
        $approve_url = admin_url("admin-post.php?action=studio_booking_quick_approve&post_id={$post->ID}&_wpnonce=$approve_nonce");
        $actions['quick_approve'] = '<a href="' . esc_url($approve_url) . '" style="color:#46b450;font-weight:600;">Approve</a>';

        $decline_nonce = wp_create_nonce('studio_booking_decline_' . $post->ID);
        $decline_url = admin_url("admin-post.php?action=studio_booking_quick_decline&post_id={$post->ID}&_wpnonce=$decline_nonce");
        $actions['quick_decline'] = '<a href="' . esc_url($decline_url) . '" style="color:#cf2e5e;font-weight:600;">Decline</a>';
    }

    if ($status === 'Approved') {
        $invoice_id = get_post_meta($post->ID, '_invoice_id', true);
        if ($invoice_id && get_post($invoice_id)) {
            $actions['view_invoice'] = '<a href="' . get_edit_post_link($invoice_id) . '" style="font-weight:600;">View Invoice</a>';
        }
        $reset_nonce = wp_create_nonce('studio_booking_reset_' . $post->ID);
        $reset_url = admin_url("admin-post.php?action=studio_booking_reset&post_id={$post->ID}&_wpnonce=$reset_nonce");
        $actions['reset_approved'] = '<a href="' . esc_url($reset_url) . '" style="color:#dba617;font-weight:600;">Reset to Pending</a>';
    }

    if ($status === 'Declined') {
        $reset_nonce = wp_create_nonce('studio_booking_reset_' . $post->ID);
        $reset_url = admin_url("admin-post.php?action=studio_booking_reset&post_id={$post->ID}&_wpnonce=$reset_nonce");
        $actions['reset_declined'] = '<a href="' . esc_url($reset_url) . '" style="color:#dba617;font-weight:600;">Reset to Pending</a>';
    }

    return $actions;
}
add_filter('post_row_actions', 'studio_booking_row_actions', 10, 2);

function studio_booking_quick_approve_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_GET['post_id'] ?? 0);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'studio_booking_approve_' . $post_id)) wp_die('Invalid nonce');
    studio_do_approve($post_id, 0, 'percent');
    wp_redirect(admin_url("edit.php?post_type=studio_booking&quick_approved=$post_id"));
    exit;
}
add_action('admin_post_studio_booking_quick_approve', 'studio_booking_quick_approve_handler');

function studio_booking_approve_discount_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!isset($_POST['approve_discount_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['approve_discount_nonce']), 'studio_booking_approve_discount_' . $post_id)) wp_die('Invalid nonce');
    $status = get_post_meta($post_id, '_status', true) ?: 'New';
    $redirect = admin_url('edit.php?post_type=studio_booking');
    if ($post_id <= 0 || !in_array($status, ['New', 'Pending'], true)) {
        wp_redirect(add_query_arg('booking_action_error', 'not_pending', $redirect));
        exit;
    }
    $discount = floatval($_POST['approve_discount'] ?? 0);
    $type     = (($_POST['approve_discount_type'] ?? 'percent') === 'flat') ? 'flat' : 'percent';
    studio_do_approve($post_id, $discount, $type);
    $msg = $discount > 0 ? ('Booking approved with ' . $type . ' discount applied.') : 'Booking approved.';
    wp_redirect(add_query_arg(['booking_approved_discount' => $post_id, 'booking_action_msg' => rawurlencode($msg)], $redirect));
    exit;
}
add_action('admin_post_studio_booking_approve_discount', 'studio_booking_approve_discount_handler');

function studio_booking_resend_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!isset($_POST['resend_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['resend_nonce']), 'studio_booking_resend_' . $post_id)) wp_die('Invalid nonce');
    $redirect = admin_url('edit.php?post_type=studio_booking');
    $post = get_post($post_id);
    $status = get_post_meta($post_id, '_status', true) ?: 'New';
    if (!$post || $status !== 'Approved') {
        wp_redirect(add_query_arg('booking_action_error', 'not_approved', $redirect));
        exit;
    }

    $name = get_the_title($post_id);
    $service = get_post_meta($post_id, '_service', true);
    $client_email = get_post_meta($post_id, '_client_email', true);
    $location = get_post_meta($post_id, '_location', true);
    $notes = get_post_meta($post_id, '_notes', true);
    $slots = get_post_meta($post_id, '_booking_slots', true);
    $total_hours = floatval(get_post_meta($post_id, '_total_hours', true));

    // Recompute totals from stored discount meta (matches approval math)
    $rate_info = studio_rate_for_service($service);
    $rate = $rate_info['rate'];
    if (!$rate_info['hourly'] && $rate > 0) $total_hours = 1;
    if ($rate === 0) $total_hours = 0;
    $subtotal = $rate * $total_hours;
    $discount = floatval(get_post_meta($post_id, '_discount', true));
    $discount_type = get_post_meta($post_id, '_discount_type', true) ?: 'percent';
    $discount_amount = 0;
    if ($discount > 0) $discount_amount = ($discount_type === 'percent') ? $subtotal * ($discount / 100) : $discount;
    $total = max(0, $subtotal - $discount_amount);
    $deposit = round($total * 0.5, 2);

    update_post_meta($post_id, '_rate', $rate);
    update_post_meta($post_id, '_total_hours', $total_hours);
    update_post_meta($post_id, '_subtotal', $subtotal);
    update_post_meta($post_id, '_total', $total);
    update_post_meta($post_id, '_deposit', $deposit);

    // Sync the linked invoice if present
    $invoice_id = intval(get_post_meta($post_id, '_invoice_id', true));
    if ($invoice_id) {
        update_post_meta($invoice_id, '_service', $service);
        update_post_meta($invoice_id, '_rate', $rate);
        update_post_meta($invoice_id, '_total_hours', $total_hours);
        update_post_meta($invoice_id, '_subtotal', $subtotal);
        update_post_meta($invoice_id, '_discount_amount', $discount_amount);
        update_post_meta($invoice_id, '_total', $total);
        update_post_meta($invoice_id, '_deposit', $deposit);
    }

    if (!$client_email) {
        wp_redirect(add_query_arg('booking_action_error', 'no_email', $redirect));
        exit;
    }

    $token = get_post_meta($post_id, '_access_token', true) ?: studio_generate_token($post_id);
    $pay_ref = $invoice_id ? $invoice_id : $post_id;
    $deposit_url = home_url("/booking-action/?action=pay_deposit&invoice_id=$pay_ref&token=$token");

    $slot_rows = '';
    if (is_array($slots) && !empty($slots)) {
        foreach ($slots as $s) {
            $slot_rows .= '<tr><td style="padding:4px 10px;border-bottom:1px solid #eee;">' . esc_html($s['date'] ?? '') . '</td><td style="padding:4px 10px;border-bottom:1px solid #eee;">' . esc_html($s['start'] ?? '') . ' &ndash; ' . esc_html($s['end'] ?? '') . '</td></tr>';
        }
    }

    $discount_line = '';
    if ($discount_amount > 0) {
        $discount_line = '<tr><td style="padding:4px 0;">' . ($discount_type === 'percent' ? $discount . '% discount' : 'Flat discount') . '</td><td style="padding:4px 0;text-align:right;color:#c0392b;">-$' . number_format($discount_amount, 2) . '</td></tr>';
    }

    $location_line = $location ? '<p><strong>Location:</strong> ' . esc_html($location) . '</p>' : '';
    $notes_line = $notes ? '<p><strong>Notes:</strong> ' . nl2br(esc_html($notes)) . '</p>' : '';

    $calendar_section = '';
    $first_slot = (is_array($slots) && !empty($slots[0])) ? $slots[0] : null;
    if ($first_slot && !empty($first_slot['date'])) {
        $date_str = $first_slot['date'];
        $start_time = str_replace(':', '', $first_slot['start'] ?? '0900');
        $end_time = str_replace(':', '', $first_slot['end'] ?? '1700');
        $gcal_start = $date_str . 'T' . $start_time . '00';
        $gcal_end = $date_str . 'T' . $end_time . '00';
        $gcal_url = "https://calendar.google.com/calendar/render?action=TEMPLATE"
            . "&text=" . urlencode($service . " - " . $name)
            . "&dates=" . $date_str . 'T' . $start_time . '00/' . $date_str . 'T' . $end_time . '00'
            . "&details=" . urlencode("Session with Roux's Audio Production");
        $calendar_section = '<p style="margin-top:16px;"><a href="' . $gcal_url . '" style="display:inline-block;padding:10px 24px;border:1px solid #d4a574;color:#d4a574;text-decoration:none;border-radius:8px;margin-right:8px;">Add to Google Calendar</a></p>';
    }

    $pay_btn = $deposit > 0
        ? '<p><a href="' . $deposit_url . '" style="display:inline-block;padding:12px 32px;background:#d4a574;color:#0a0a0a;text-decoration:none;border-radius:8px;font-weight:bold;">Pay Deposit via PayPal</a></p>'
        : '';

    $slot_label = count((array) $slots) > 1 ? 'Sessions' : 'Session';

    studio_send_email($client_email, 'Updated Booking Details — ' . esc_html($name),
        '<p>Hi ' . esc_html($name) . ',</p>'
        . '<p>Your booking for <strong>' . esc_html($service) . '</strong> has been updated. Here are your current details:</p>'
        . $location_line . $notes_line
        . '<p style="margin-top:12px;"><strong>' . $slot_label . ':</strong></p>'
        . '<table style="border-collapse:collapse;max-width:400px;">' . $slot_rows . '</table>'
        . '<table style="margin-top:14px;max-width:320px;">'
        . '<tr><td style="padding:4px 0;">Subtotal</td><td style="padding:4px 0;text-align:right;">$' . number_format($subtotal, 2) . '</td></tr>'
        . $discount_line
        . '<tr><td style="padding:4px 0;font-weight:bold;">Total</td><td style="padding:4px 0;text-align:right;font-weight:bold;">$' . number_format($total, 2) . '</td></tr>'
        . ($deposit > 0 ? '<tr><td style="padding:4px 0;">50% Deposit</td><td style="padding:4px 0;text-align:right;">$' . number_format($deposit, 2) . '</td></tr>' : '')
        . '</table>'
        . $pay_btn
        . $calendar_section
        . '<p style="margin-top:14px;font-size:12px;color:#888;">Questions, or need to make another change? Just reply to this email.</p>');

    studio_send_email(get_option('admin_email'), 'Booking Resent — ' . esc_html($name),
        '<p>Updated details + a fresh deposit link sent to <strong>' . esc_html($name) . '</strong> (<strong>' . esc_html($service) . '</strong>).</p>'
        . '<p><strong>Total:</strong> $' . number_format($total, 2) . ' | <strong>Deposit:</strong> $' . number_format($deposit, 2) . '</p>');

    studio_cache_flush('studio_dashboard_overview');
    wp_redirect(add_query_arg('booking_resent', $post_id, $redirect));
    exit;
}
add_action('admin_post_studio_booking_resend', 'studio_booking_resend_handler');

function studio_booking_quick_decline_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_GET['post_id'] ?? 0);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'studio_booking_decline_' . $post_id)) wp_die('Invalid nonce');
    update_post_meta($post_id, '_status', 'Declined');
    studio_cache_flush('studio_dashboard_overview');

    $name = get_the_title($post_id);
    $email = get_post_meta($post_id, '_client_email', true);
    if ($email) {
        studio_send_email($email, "Booking Request Declined",
            '<p>Hi ' . esc_html($name) . ',</p>' .
            '<p>We regret that we are unable to accommodate your booking request at this time.</p>');
    }
    studio_send_email(get_option('admin_email'), "Booking Declined — " . esc_html($name),
        '<p>Booking for <strong>' . esc_html($name) . '</strong> has been declined.</p>');

    wp_redirect(admin_url("edit.php?post_type=studio_booking&quick_declined=$post_id"));
    exit;
}
add_action('admin_post_studio_booking_quick_decline', 'studio_booking_quick_decline_handler');

function studio_booking_reset_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_GET['post_id'] ?? 0);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'studio_booking_reset_' . $post_id)) wp_die('Invalid nonce');
    update_post_meta($post_id, '_status', 'Pending');
    studio_cache_flush('studio_dashboard_overview');
    wp_redirect(admin_url("edit.php?post_type=studio_booking&booking_reset=$post_id"));
    exit;
}
add_action('admin_post_studio_booking_reset', 'studio_booking_reset_handler');

function studio_quick_action_notices() {
    $screen = get_current_screen();
    if ($screen && isset($_GET['quick_approved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Booking approved.</p></div>';
    }
    if ($screen && isset($_GET['quick_declined'])) {
        echo '<div class="notice notice-warning is-dismissible"><p>Booking declined.</p></div>';
    }
    if ($screen && isset($_GET['booking_reset'])) {
        echo '<div class="notice notice-info is-dismissible"><p>Booking reset to pending — you can approve or decline it again.</p></div>';
    }
    if ($screen && isset($_GET['booking_approved_discount'])) {
        $msg = isset($_GET['booking_action_msg']) ? html_entity_decode(sanitize_text_field(wp_unslash($_GET['booking_action_msg']))) : 'Booking approved.';
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }
    if ($screen && isset($_GET['approved_from_edit'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Booking approved — fresh price and a PayPal deposit link were emailed to the client.</p></div>';
    }
    if ($screen && isset($_GET['booking_resent'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Updated details and a fresh deposit link were re-sent to the client.</p></div>';
    }
    if ($screen && isset($_GET['booking_action_error'])) {
        $err = sanitize_text_field(wp_unslash($_GET['booking_action_error'] ?? 'error'));
        $msg = ($err === 'no_email') ? 'Booking action failed — no client email is set for this booking.' : 'Booking action failed — that booking is not in the expected state.';
        echo '<div class="notice notice-error is-dismissible"><p>' . $msg . '</p></div>';
    }
}
add_action('admin_notices', 'studio_quick_action_notices');

/* ----------------------------------------------
   ROW ACTIONS — INVOICES (Submit / Mark Paid) + Bulk
   ---------------------------------------------- */
function studio_invoice_row_actions($actions, $post) {
    if (!current_user_can('manage_options')) return $actions;
    $status = get_post_meta($post->ID, '_status', true) ?: 'Draft';

    unset($actions['inline hide-if-no-js']);

    if ($status === 'Draft') {
        $submit_nonce = wp_create_nonce('studio_invoice_submit_' . $post->ID);
        $submit_url = admin_url("admin-post.php?action=studio_invoice_quick_submit&post_id={$post->ID}&_wpnonce=$submit_nonce");
        $actions['quick_submit'] = '<a href="' . esc_url($submit_url) . '" style="color:#46b450;font-weight:600;">Submit to Client</a>';
    }

    if ($status === 'Sent' || $status === 'Draft') {
        $paid_nonce = wp_create_nonce('studio_invoice_mark_paid_' . $post->ID);
        $paid_url = admin_url("admin-post.php?action=studio_invoice_quick_paid&post_id={$post->ID}&_wpnonce=$paid_nonce");
        $actions['quick_paid'] = '<a href="' . esc_url($paid_url) . '" style="color:#2271b1;font-weight:600;">Mark Paid</a>';
    }

    return $actions;
}
add_filter('post_row_actions', 'studio_invoice_row_actions', 10, 2);

function studio_invoice_quick_submit_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_GET['post_id'] ?? 0);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'studio_invoice_submit_' . $post_id)) wp_die('Invalid nonce');
    $client_email = get_post_meta($post_id, '_client_email', true);
    $client_name = get_post_meta($post_id, '_client_name', true);
    $total = floatval(get_post_meta($post_id, '_total', true));
    if ($client_email && $total > 0) {
        $token = studio_generate_token($post_id);
        $pay_url = home_url("/booking-action/?action=pay_deposit&invoice_id=$post_id&token=$token");
        $mail_result = studio_send_invoice_email($post_id, $client_email, "Invoice #{$post_id} — Roux Audio Production", '<p>Hi ' . esc_html($client_name) . ',</p><p>Your invoice is ready.</p>', $pay_url);
        if (is_wp_error($mail_result) || !$mail_result) wp_die('The invoice email with PDF could not be sent. Check the site error log and try again.');
        update_post_meta($post_id, '_status', 'Sent');
        studio_cache_flush('studio_dashboard_overview');
        studio_send_email(get_option('admin_email'), "Invoice #{$post_id} Sent — " . esc_html($client_name),
            '<p>Invoice #' . $post_id . ' for <strong>' . esc_html($client_name) . '</strong> has been sent to ' . esc_html($client_email) . '.</p>');
    }
    wp_redirect(admin_url("edit.php?post_type=studio_invoice&invoice_submitted=$post_id"));
    exit;
}
add_action('admin_post_studio_invoice_quick_submit', 'studio_invoice_quick_submit_handler');

function studio_invoice_quick_paid_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_GET['post_id'] ?? 0);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'studio_invoice_mark_paid_' . $post_id)) wp_die('Invalid nonce');
    studio_mark_invoice_paid_manually($post_id);
    studio_cache_flush('studio_dashboard_overview');

    $client_email = get_post_meta($post_id, '_client_email', true);
    $client_name = get_post_meta($post_id, '_client_name', true);
    if ($client_email) {
        studio_send_email($client_email, "Payment Received — Invoice #{$post_id}",
            '<p>Hi ' . esc_html($client_name) . ',</p>' .
            '<p>Your payment for <strong>Invoice #' . $post_id . '</strong> has been received. Thank you!</p>');
    }
    studio_send_email(get_option('admin_email'), "Payment Received — Invoice #{$post_id}",
        '<p>Invoice #' . $post_id . ' for <strong>' . esc_html($client_name) . '</strong> has been marked as paid.</p>');

    wp_redirect(admin_url("edit.php?post_type=studio_invoice&invoice_paid=$post_id"));
    exit;
}
add_action('admin_post_studio_invoice_quick_paid', 'studio_invoice_quick_paid_handler');

function studio_invoice_bulk_actions($actions) {
    $actions['studio_bulk_submit'] = 'Submit to Client';
    $actions['studio_bulk_mark_paid'] = 'Mark Paid';
    return $actions;
}
add_filter('bulk_actions-edit-studio_invoice', 'studio_invoice_bulk_actions');

function studio_invoice_bulk_handler($redirect_to, $doaction, $post_ids) {
    if (!current_user_can('manage_options')) return $redirect_to;
    if ($doaction === 'studio_bulk_submit') {
        $sent_count = 0;
        $failed_count = 0;
        foreach ($post_ids as $id) {
            $ce = get_post_meta($id, '_client_email', true);
            $cn = get_post_meta($id, '_client_name', true);
            if ($ce) {
                $token = studio_generate_token($id);
                $pay_url = home_url("/booking-action/?action=pay_deposit&invoice_id=$id&token=$token");
                $mail_result = studio_send_invoice_email($id, $ce, "Invoice #{$id} — Roux Audio Production", '<p>Hi ' . esc_html($cn) . ',</p><p>Your invoice is ready.</p>', $pay_url);
                if (is_wp_error($mail_result) || !$mail_result) {
                    $failed_count++;
                    continue;
                }
                update_post_meta($id, '_status', 'Sent');
                $sent_count++;
            } else {
                $failed_count++;
                continue;
            }
            studio_send_email(get_option('admin_email'), "Invoice #{$id} Sent — " . esc_html($cn),
                '<p>Invoice #' . $id . ' for <strong>' . esc_html($cn) . '</strong> has been sent to ' . esc_html($ce) . '.</p>');
        }
        studio_cache_flush('studio_dashboard_overview');
        return add_query_arg(['invoices_submitted' => $sent_count, 'invoice_send_failed' => $failed_count], $redirect_to);
    }
    if ($doaction === 'studio_bulk_mark_paid') {
        foreach ($post_ids as $id) {
            studio_mark_invoice_paid_manually($id);
            $ce = get_post_meta($id, '_client_email', true);
            $cn = get_post_meta($id, '_client_name', true);
            if ($ce) {
                studio_send_email($ce, "Payment Received — Invoice #{$id}",
                    '<p>Hi ' . esc_html($cn) . ',</p><p>Your payment for <strong>Invoice #' . $id . '</strong> has been received. Thank you!</p>');
            }
            studio_send_email(get_option('admin_email'), "Payment Received — Invoice #{$id}",
                '<p>Invoice #' . $id . ' for <strong>' . esc_html($cn) . '</strong> has been marked as paid.</p>');
        }
        studio_cache_flush('studio_dashboard_overview');
        return add_query_arg('invoices_paid', count($post_ids), $redirect_to);
    }
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-studio_invoice', 'studio_invoice_bulk_handler', 10, 3);

function studio_invoice_notices() {
    if (isset($_GET['invoice_submitted'])) echo '<div class="notice notice-success is-dismissible"><p>Invoice submitted to client.</p></div>';
    if (isset($_GET['invoice_paid'])) echo '<div class="notice notice-success is-dismissible"><p>Invoice marked as paid.</p></div>';
    if (isset($_GET['invoices_submitted'])) echo '<div class="notice notice-success is-dismissible"><p>' . intval($_GET['invoices_submitted']) . ' invoice(s) submitted.</p></div>';
    if (isset($_GET['invoices_paid'])) echo '<div class="notice notice-success is-dismissible"><p>' . intval($_GET['invoices_paid']) . ' invoice(s) marked paid.</p></div>';
}
add_action('admin_notices', 'studio_invoice_notices');

/* ----------------------------------------------
   ADMIN COLUMNS + ROW ACTIONS — EXPENSES
   ---------------------------------------------- */
function studio_expense_columns($columns) {
    $new = [];
    foreach ($columns as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['exp_amount'] = 'Amount';
            $new['exp_date'] = 'Date';
            $new['exp_category'] = 'Category';
            $new['exp_tax_cat'] = 'Tax Category';
            $new['exp_deductible'] = 'Deductible';
        }
    }
    return $new;
}
add_filter('manage_studio_expense_posts_columns', 'studio_expense_columns');

function studio_expense_column_data($column, $post_id) {
    switch ($column) {
        case 'exp_amount':
            $amt = floatval(get_post_meta($post_id, '_expense_amount', true));
            echo $amt ? '$' . number_format($amt, 2) : '-';
            break;
        case 'exp_date':
            echo esc_html(get_post_meta($post_id, '_expense_date', true));
            break;
        case 'exp_category':
            echo esc_html(get_post_meta($post_id, '_expense_category', true));
            break;
        case 'exp_tax_cat':
            $tc = get_post_meta($post_id, '_tax_category', true);
            echo $tc ? studio_badge_html($tc) : '-';
            break;
        case 'exp_deductible':
            echo get_post_meta($post_id, '_tax_deductible', true) ? '<span style="color:#46b450;font-weight:600;">Yes</span>' : '<span style="color:#888;">No</span>';
            break;
    }
}
add_action('manage_studio_expense_posts_custom_column', 'studio_expense_column_data', 10, 2);

function studio_expense_row_actions($actions, $post) {
    if (!current_user_can('manage_options')) return $actions;
    $status = get_post_meta($post->ID, '_expense_status', true) ?: 'Draft';

    unset($actions['inline hide-if-no-js']);

    if ($status === 'Draft' || $status === '') {
        $submit_nonce = wp_create_nonce('studio_expense_submit_' . $post->ID);
        $submit_url = admin_url("admin-post.php?action=studio_expense_quick_submit&post_id={$post->ID}&_wpnonce=$submit_nonce");
        $actions['quick_submit'] = '<a href="' . esc_url($submit_url) . '" style="color:#46b450;font-weight:600;">Submit</a>';
    }

    return $actions;
}
add_filter('post_row_actions', 'studio_expense_row_actions', 10, 2);

function studio_expense_quick_submit_handler() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $post_id = intval($_GET['post_id'] ?? 0);
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'studio_expense_submit_' . $post_id)) wp_die('Invalid nonce');
    update_post_meta($post_id, '_expense_status', 'Approved');
    studio_cache_flush('studio_dashboard_overview');
    wp_redirect(admin_url("edit.php?post_type=studio_expense&expense_submitted=$post_id"));
    exit;
}
add_action('admin_post_studio_expense_quick_submit', 'studio_expense_quick_submit_handler');

function studio_expense_bulk_actions($actions) {
    $actions['studio_bulk_expense_submit'] = 'Submit';
    return $actions;
}
add_filter('bulk_actions-edit-studio_expense', 'studio_expense_bulk_actions');

function studio_expense_bulk_handler($redirect_to, $doaction, $post_ids) {
    if (!current_user_can('manage_options')) return $redirect_to;
    if ($doaction === 'studio_bulk_expense_submit') {
        foreach ($post_ids as $id) {
            update_post_meta($id, '_expense_status', 'Approved');
        }
        studio_cache_flush('studio_dashboard_overview');
        return add_query_arg('expenses_submitted', count($post_ids), $redirect_to);
    }
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-studio_expense', 'studio_expense_bulk_handler', 10, 3);

function studio_expense_notices() {
    if (isset($_GET['expense_submitted'])) echo '<div class="notice notice-success is-dismissible"><p>Expense submitted.</p></div>';
    if (isset($_GET['expenses_submitted'])) echo '<div class="notice notice-success is-dismissible"><p>' . intval($_GET['expenses_submitted']) . ' expense(s) submitted.</p></div>';
}
add_action('admin_notices', 'studio_expense_notices');

/* ----------------------------------------------
   iCal EXPORT (require login or admin)
   ---------------------------------------------- */
 function studio_ical_export() {
     if (!isset($_GET['ical'])) return;
     if (!current_user_can('manage_options')) {
         wp_die('Access denied.');
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
    if (!current_user_can('manage_options')) return '';
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
    if (!current_user_can('manage_options')) return '';
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
    if (!current_user_can('manage_options')) return '';
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
   STUDIO SETTINGS — SERVICE RATES
   ---------------------------------------------- */
function studio_settings_admin_menu() {
    add_menu_page(
        'Studio Settings',
        'Studio',
        'manage_options',
        'studio-settings',
        'studio_render_service_rates_page',
        'dashicons-controls-playback',
        25.5
    );
}
add_action('admin_menu', 'studio_settings_admin_menu');

function studio_render_service_rates_page() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');

    // Handle save
    if (isset($_POST['studio_rates_nonce']) && wp_verify_nonce($_POST['studio_rates_nonce'], 'studio_save_rates')) {
        $current = get_option('studio_rates', []);
        if (!is_array($current)) {
            $current = [];
        }
        foreach (studio_default_service_rates() as $key => $entry) {
            if (isset($_POST['rate_' . $key]) && is_numeric($_POST['rate_' . $key])) {
                $current[$key] = max(0, (int) $_POST['rate_' . $key]);
            }
            $current[$key . '_hourly'] = isset($_POST['rate_mode_' . $key]) && $_POST['rate_mode_' . $key] === 'hourly';
        }
        update_option('studio_rates', $current);
        studio_cache_flush('studio_dashboard_overview');
        echo '<div class="notice notice-success is-dismissible"><p>Rates updated.</p></div>';
    }

    $rates = studio_service_rates();
    ?>
    <div class="wrap" style="max-width:760px;">
        <h1>Studio — Service Rates</h1>
        <p>These rates appear on the public booking page and are used for quotes, invoices, and the PDF. Changes take effect immediately for new bookings.</p>
        <form method="post">
            <?php wp_nonce_field('studio_save_rates', 'studio_rates_nonce'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                <?php foreach ($rates as $key => $info): ?>
                    <tr>
                        <th scope="row"><label for="rate_<?php echo esc_attr($key); ?>"><?php echo esc_html($info['label']); ?></label></th>
                        <td>
                            <span class="dashicons dashicons-money-alt" style="vertical-align:middle;margin-right:8px;font-size:18px;height:18px;width:18px;"></span>
                            <input type="number" min="0" step="5" id="rate_<?php echo esc_attr($key); ?>" name="rate_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($info['rate']); ?>" class="small-text" /><?php
                            if ($info['rate'] > 0): ?>
                                <label style="margin-left:12px;"><input type="radio" name="rate_mode_<?php echo esc_attr($key); ?>" value="hourly"<?php echo $info['hourly'] ? ' checked' : ''; ?>> Hourly</label> <label style="margin-left:8px;"><input type="radio" name="rate_mode_<?php echo esc_attr($key); ?>" value="flat"<?php echo !$info['hourly'] ? ' checked' : ''; ?>> Flat per booking</label><?php
                            else: ?><span style="margin-left:8px;color:#646970;">Free</span><?php
                            endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description">Set a rate to 0 to mark a service as free. Consultation is free by default.</p>
            <?php submit_button('Save Rates'); ?>
        </form>
    </div>
    <?php
}

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
            $mail_result = studio_send_invoice_email($inv_id, $client_email, "Invoice #{$inv_id} - Roux's Audio Production", '<p>Hi ' . esc_html($client_name) . ',</p><p>Please find your invoice for <strong>' . esc_html($service) . '</strong>.</p>', $pay_url);
            if (is_wp_error($mail_result) || !$mail_result) {
                echo '<div class="notice notice-error"><p>Invoice email and PDF could not be sent. Check the site error log.</p></div>';
            } else {
                update_post_meta($inv_id, '_status', 'Sent');
                echo '<div class="notice notice-success"><p>Invoice #' . intval($inv_id) . ' and its PDF were emailed to ' . esc_html($client_email) . '.</p></div>';
                studio_send_email(get_option('admin_email'), "Invoice #{$inv_id} Sent to {$client_name}",
                    '<p>Invoice #' . $inv_id . ' for <strong>' . esc_html($client_name) . '</strong> has been emailed.</p>');
            }
        }
    }

    // Handle mark paid
    if (isset($_GET['mark_paid']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'studio_mark_paid')) {
        $inv_id = intval($_GET['mark_paid']);
        studio_mark_invoice_paid_manually($inv_id);

        $client_email_m = get_post_meta($inv_id, '_client_email', true);
        $client_name_m = get_post_meta($inv_id, '_client_name', true);

        if ($client_email_m) {
            studio_send_email($client_email_m, "Payment Received — Invoice #{$inv_id}",
                '<p>Hi ' . esc_html($client_name_m) . ',</p>' .
                '<p>Your payment for <strong>Invoice #' . $inv_id . '</strong> has been received. Thank you!</p>');
        }

        $admin = get_option('admin_email');
        if ($admin) {
            studio_send_email($admin, "Payment Received — Invoice #{$inv_id}",
                '<p>Invoice #' . $inv_id . ' for <strong>' . esc_html($client_name_m) . '</strong> has been marked as paid.</p>');
        }

        echo '<div class="notice notice-success"><p>Invoice #' . $inv_id . ' marked as paid. Confirmation sent.</p></div>';
    }

    $invoices = get_posts(['post_type' => 'studio_invoice', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']);
    ?>
    <div class="wrap">
        <h1>Invoice Manager</h1>
        <p><a href="<?php echo esc_url(admin_url('post-new.php?post_type=studio_invoice')); ?>" class="page-title-action">Create Invoice</a></p>
        <div style="margin-top:24px;">
            <h2>Existing Invoices</h2>
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
    <?php
}

/*
   BOOKING DETAILS META BOX (full editable detail)
*/
function studio_booking_details_meta_callback($post) {
    wp_nonce_field('studio_save_booking', '_studio_booking_nonce');
    $status       = get_post_meta($post->ID, '_status', true) ?: 'New';
    $client_email = get_post_meta($post->ID, '_client_email', true);
    $client_phone = get_post_meta($post->ID, '_client_phone', true);
    $service      = get_post_meta($post->ID, '_service', true);
    $location     = get_post_meta($post->ID, '_location', true);
    $notes        = get_post_meta($post->ID, '_notes', true);
    $total_hours  = get_post_meta($post->ID, '_total_hours', true);
    $slots        = get_post_meta($post->ID, '_booking_slots', true);
    if (!is_array($slots)) $slots = [];

    $rates       = studio_service_rates();
    $rate_info   = studio_rate_for_service($service);
    $rate_map    = [];
    foreach ($rates as $rate_key => $rate) {
        $rate_entry = ['rate' => floatval($rate['rate']), 'hourly' => (bool) $rate['hourly']];
        $rate_map[$rate['label']] = $rate_entry;
        $rate_map[$rate_key] = $rate_entry;
    }
    if ($service !== '' && !isset($rate_map[$service])) {
        $rate_map[$service] = ['rate' => floatval($rate_info['rate']), 'hourly' => (bool) $rate_info['hourly']];
    }
    $discount     = floatval(get_post_meta($post->ID, '_discount', true));
    $disc_type    = get_post_meta($post->ID, '_discount_type', true) ?: 'percent';
    $subtotal     = $rate_info['rate'] * (floatval($total_hours) ?: (floatval($rate_info['rate']) > 0 && !$rate_info['hourly'] ? 1 : 0));
    $disc_amount  = $discount > 0 ? ($disc_type === 'percent' ? $subtotal * $discount / 100 : $discount) : 0;
    $total        = max(0, $subtotal - $disc_amount);
    $deposit      = round($total * 0.5, 2);
    ?>
    <style>
        .studio-bk-detail { max-width: 100%; }
        .studio-bk-detail .bk-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .studio-bk-detail .bk-slot-row { display:grid; grid-template-columns:140px 1fr 1fr 80px 32px; gap:6px; align-items:center; margin-bottom:6px; }
        .studio-bk-detail .bk-slot-row label { font-size:11px; margin:0; display:block; }
        .studio-bk-detail .bk-slot-row input { width:100%; }
        .studio-bk-detail .bk-slot-row .bk-rm { width:28px; height:28px; border:1px solid #ccc; background:#fff; border-radius:4px; cursor:pointer; font-size:14px; line-height:1; }
        .studio-bk-detail .bk-summary { background:#f6f7f7; border:1px solid #e0e0e0; border-radius:6px; padding:12px 16px; margin-top:14px; }
        .studio-bk-detail .bk-summary table { width:100%; border-collapse:collapse; }
        .studio-bk-detail .bk-summary td { padding:4px 0; font-size:0.9rem; }
        .studio-bk-detail .bk-summary .bk-amt { text-align:right; font-variant-numeric:tabular-nums; }
    </style>
    <div class="studio-bk-detail">
        <?php if ($status === 'Approved') : $job_status = get_post_meta($post->ID, '_job_status', true) ?: 'Scheduled'; ?>
        <h3>Job Progress</h3>
        <?php if ($job_status === 'Closed') : ?>
        <p><strong>Closed</strong> — completed and paid in full.</p>
        <?php else : ?>
        <p><label>Session Status<br>
            <select name="_job_status" style="width:100%;">
                <?php foreach (['Scheduled', 'In Progress', 'Completed'] as $option) : ?>
                <option value="<?php echo esc_attr($option); ?>" <?php selected($job_status, $option); ?>><?php echo esc_html($option); ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
        <p style="font-size:11px;color:#666;">Completed sessions close automatically once the invoice is paid in full.</p>
        <?php endif; ?>
        <?php endif; ?>
        <h3>Client</h3>
        <div class="bk-grid">
            <p><label>Client Name (post title controls this)&nbsp;&hellip;<br>
                <em style="font-size:11px;color:#888;">Edit the title field at the top of this page.</em>
            </label></p>
            <p><label>Email<br><input type="email" name="_client_email" value="<?php echo esc_attr($client_email); ?>" style="width:100%;"></label></p>
            <p><label>Phone<br><input type="tel" name="_client_phone" value="<?php echo esc_attr($client_phone); ?>" style="width:100%;"></label></p>
            <p><label>Service<br>
                <select name="_service" style="width:100%;">
                    <?php foreach ($rates as $key => $info) : ?>
                    <option value="<?php echo esc_attr($info['label']); ?>" <?php selected($service, $info['label']); ?>><?php echo esc_html($info['label'] . ' — ' . $info['desc']); ?></option>
                    <?php endforeach; ?>
                    <?php if ($service && !array_key_exists($service, $rates) && !in_array($service, array_column($rates, 'label'))) : ?>
                    <option value="<?php echo esc_attr($service); ?>" selected><?php echo esc_html($service); ?></option>
                    <?php endif; ?>
                </select>
            </label></p>
            <p><label>Location / Venue<br><input type="text" name="_location" value="<?php echo esc_attr($location); ?>" placeholder="Studio address or venue" style="width:100%;"></label></p>
            <p><label>Notes<br><textarea name="_notes" rows="3" style="width:100%;"><?php echo esc_textarea($notes); ?></textarea></label></p>
        </div>

        <h3 style="margin-top:18px;">Session Slots</h3>
        <p style="font-size:12px;color:#666;">Each row defines one session date with start &amp; end times. Hours are calculated automatically.</p>
        <div id="studio-slot-rows">
            <?php foreach ($slots as $i => $slot) : ?>
            <div class="bk-slot-row" data-idx="<?php echo $i; ?>">
                <div><label>Date</label><input type="date" name="bk_slot_date[]" value="<?php echo esc_attr($slot['date'] ?? ''); ?>"></div>
                <div><label>Start</label><input type="time" name="bk_slot_start[]" value="<?php echo esc_attr($slot['start'] ?? ''); ?>"></div>
                <div><label>End</label><input type="time" name="bk_slot_end[]" value="<?php echo esc_attr($slot['end'] ?? ''); ?>"></div>
                <div><label>Hours</label><input type="number" step="0.25" min="0" readonly value="<?php echo esc_attr($slot['hours'] ?? ''); ?>" class="bk-slot-hours" style="background:#f0f0f0;"></div>
                <button type="button" class="bk-rm" title="Remove slot">&times;</button>
            </div>
            <?php endforeach; ?>
            <?php if (empty($slots)) : ?>
            <div class="bk-slot-row">
                <div><label>Date</label><input type="date" name="bk_slot_date[]"></div>
                <div><label>Start</label><input type="time" name="bk_slot_start[]"></div>
                <div><label>End</label><input type="time" name="bk_slot_end[]"></div>
                <div><label>Hours</label><input type="number" step="0.25" min="0" readonly value="0" class="bk-slot-hours" style="background:#f0f0f0;"></div>
                <button type="button" class="bk-rm" title="Remove slot">&times;</button>
            </div>
            <?php endif; ?>
        </div>
        <p style="margin-top:6px;"><button type="button" id="bk-add-slot" class="button button-small" style="cursor:pointer;">+ Add Session</button></p>

        <h3 style="margin-top:18px;">Discount</h3>
        <?php if ($status === 'Approved') : ?>
        <p style="font-size:12px;color:#666;">Discount already applied at approval. Reset to edit.</p>
        <p style="font-size:12px;"><label><input type="checkbox" name="resend_to_client" value="1"> Email updated booking details and deposit link to the client after saving</label></p>
        <?php else : ?>
        <div class="bk-grid">
            <p><label>Discount Type<br>
                <select name="_discount_type">
                    <option value="percent" <?php selected($disc_type, 'percent'); ?>>Percent (%)</option>
                    <option value="flat" <?php selected($disc_type, 'flat'); ?>>Flat ($)</option>
                </select>
            </label></p>
            <p><label>Discount Value<br><input type="number" name="_discount" step="0.01" min="0" value="<?php echo esc_attr($discount ?: ''); ?>" placeholder="e.g. 10 for 10% or 50 for $50" style="width:100%;"></label></p>
        </div>
        <?php endif; ?>

        <?php if (in_array($status, ['New', 'Pending'], true)) : ?>
        <p style="margin-top:16px;">
            <label style="font-weight:bold;cursor:pointer;">
                <input type="checkbox" name="approve_now" style="margin-right:6px;"> Approve Now — apply the discount, compute the final price, create the invoice &amp; email the client a PayPal deposit link
            </label>
            <br><em style="font-size:11px;color:#888;">Uncheck to just save changes for later approval.</em>
        </p>
        <?php endif; ?>

        <div class="bk-summary">
            <table>
                <tr><td>Rate</td><td class="bk-amt" id="bk-rate"><?php echo $rate_info['hourly'] ? '$' . number_format($rate_info['rate'], 2) . '/hr' : '$' . number_format($rate_info['rate'], 2) . ' flat'; ?></td></tr>
                <tr><td>Total Hours</td><td class="bk-amt" id="bk-total-hours"><?php echo esc_html($total_hours ?: 0); ?></td></tr>
                <tr><td>Subtotal</td><td class="bk-amt" id="bk-subtotal">$<?php echo number_format($subtotal, 2); ?></td></tr>
                <?php if ($discount > 0) : ?>
                <tr><td>Discount (<?php echo esc_html($disc_type === 'percent' ? $discount . '%' : '$' . number_format($discount, 2)); ?>)</td><td class="bk-amt" id="bk-discount-line">-$<?php echo number_format($disc_amount, 2); ?></td></tr>
                <?php else : ?>
                <tr><td>Discount</td><td class="bk-amt" id="bk-discount-line">$0.00</td></tr>
                <?php endif; ?>
                <tr style="border-top:2px solid #d4a574;"><td><strong>Total</strong></td><td class="bk-amt"><strong id="bk-total">$<?php echo number_format($total, 2); ?></strong></td></tr>
                <tr><td>50% Deposit</td><td class="bk-amt" id="bk-deposit">$<?php echo number_format($deposit, 2); ?></td></tr>
            </table>
        </div>

        <script>
        (function(){
            var rows = document.getElementById('studio-slot-rows');
            var addBtn = document.getElementById('bk-add-slot');
            var serviceSelect = document.querySelector('select[name="_service"]');
            var serviceRates = <?php echo wp_json_encode($rate_map); ?>;
            var rateEl = serviceRates[serviceSelect.value] || <?php echo wp_json_encode(['rate' => floatval($rate_info['rate']), 'hourly' => (bool) $rate_info['hourly']]); ?>;
            var rateDisplay = document.getElementById('bk-rate');
            var discountInput = document.querySelector('input[name="_discount"]');
            var discountTypeSelect = document.querySelector('select[name="_discount_type"]');
            var storedDiscount = <?php echo wp_json_encode($discount); ?>;
            var storedDiscountType = <?php echo wp_json_encode($disc_type); ?>;

            function calcSlotHours(row){
                var startEl = row.querySelector('input[type="time"], input[name="bk_slot_start[]"]');
                var endEl = row.querySelector('input[name="bk_slot_end[]"]');
                var hEl = row.querySelector('.bk-slot-hours');
                var hrs = 0;
                if(startEl && endEl && startEl.value && endEl.value){
                    var sp = startEl.value.split(':');
                    var ep = endEl.value.split(':');
                    var diff = (parseInt(ep[0])*60+parseInt(ep[1])) - (parseInt(sp[0])*60+parseInt(sp[1]));
                    hrs = diff > 0 ? Math.round(diff/60*4)/4 : 0;
                }
                if(hEl) hEl.value = hrs;
                return hrs;
            }

            function recalc(){
                var totalHrs = 0;
                rows.querySelectorAll('.bk-slot-row').forEach(function(r){ totalHrs += calcSlotHours(r); });
                var isFlat = !rateEl.hourly && rateEl.rate > 0;
                var effectiveHrs = isFlat ? 1 : (rateEl.rate === 0 ? 0 : totalHrs);
                var subtotal = rateEl.rate * effectiveHrs;
                var discVal = discountInput ? (parseFloat(discountInput.value) || 0) : storedDiscount;
                var discType = discountTypeSelect ? discountTypeSelect.value : storedDiscountType;
                var discAmt = discVal > 0 ? (discType === 'percent' ? subtotal * discVal / 100 : discVal) : 0;
                var total = Math.max(0, subtotal - discAmt);
                var deposit = Math.round(total * 0.5 * 100) / 100;
                document.getElementById('bk-total-hours').textContent = totalHrs.toFixed(2).replace(/\.?0+$/,'') || '0';
                document.getElementById('bk-subtotal').textContent = '$' + subtotal.toFixed(2);
                document.getElementById('bk-discount-line').textContent = discAmt > 0 ? '-$' + discAmt.toFixed(2) : '$0.00';
                document.getElementById('bk-total').textContent = '$' + total.toFixed(2);
                document.getElementById('bk-deposit').textContent = '$' + deposit.toFixed(2);
            }

            function addSlot(){
                var idx = rows.querySelectorAll('.bk-slot-row').length;
                var div = document.createElement('div');
                div.className = 'bk-slot-row';
                div.setAttribute('data-idx', idx);
                div.innerHTML = '<div><label>Date</label><input type="date" name="bk_slot_date[]"></div>' +
                    '<div><label>Start</label><input type="time" name="bk_slot_start[]"></div>' +
                    '<div><label>End</label><input type="time" name="bk_slot_end[]"></div>' +
                    '<div><label>Hours</label><input type="number" step="0.25" min="0" readonly value="0" class="bk-slot-hours" style="background:#f0f0f0;"></div>' +
                    '<button type="button" class="bk-rm" title="Remove slot">&times;</button>';
                rows.appendChild(div);
            }

            rows.addEventListener('input', function(e){
                if(e.target.matches('input[type="time"], input[type="date"]')){
                    var row = e.target.closest('.bk-slot-row');
                    if(row) calcSlotHours(row);
                    recalc();
                }
            });
            rows.addEventListener('click', function(e){
                if(e.target.classList.contains('bk-rm') && rows.querySelectorAll('.bk-slot-row').length > 1){
                    e.target.closest('.bk-slot-row').remove();
                    recalc();
                }
            });
            serviceSelect.addEventListener('change', function(){
                rateEl = serviceRates[serviceSelect.value] || rateEl;
                rateDisplay.textContent = '$' + rateEl.rate.toFixed(2) + (rateEl.hourly ? '/hr' : ' flat');
                recalc();
            });
            if(discountInput) discountInput.addEventListener('input', recalc);
            if(discountTypeSelect) discountTypeSelect.addEventListener('input', recalc);
            addBtn.addEventListener('click', addSlot);
            recalc();
        })();
        </script>
    </div>
    <?php
}

/*
   SAVE BOOKING META (handles details box + counter-offer form)
*/
function studio_save_booking_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Counter-offer form (separate nonce)
    if (isset($_POST['studio_counter_offer']) && isset($_POST['_co_nonce']) && wp_verify_nonce($_POST['_co_nonce'], 'studio_counter_offer')) {
        $new_rate  = floatval($_POST['co_rate'] ?? 0);
        $new_hours = floatval($_POST['co_hours'] ?? get_post_meta($post_id, '_total_hours', true));
        $message   = sanitize_textarea_field($_POST['co_message'] ?? '');
        update_post_meta($post_id, '_status', 'Counter-Offer');
        update_post_meta($post_id, '_counter_rate', $new_rate);
        update_post_meta($post_id, '_counter_hours', $new_hours);
        update_post_meta($post_id, '_counter_message', $message);
        studio_cache_flush('studio_dashboard_overview');
        $client_email = get_post_meta($post_id, '_client_email', true);
        $name = get_the_title($post_id);
        $token = get_post_meta($post_id, '_access_token', true) ?: studio_generate_token($post_id);
        if ($client_email) {
            $accept_url  = home_url("/booking-action/?action=accept_counter&booking_id=$post_id&token=$token");
            $decline_url = home_url("/booking-action/?action=decline_counter&booking_id=$post_id&token=$token");
            studio_send_email($client_email, "Counter Offer for Your Booking",
                '<p>Hi ' . esc_html($name) . ', we have a counter offer for your booking:</p>' .
                '<p><strong>New total:</strong> $' . number_format((float) $new_rate * (float) $new_hours, 2) . '</p>' .
                ($message ? '<p><strong>Note:</strong> ' . esc_html($message) . '</p>' : '') .
                '<p><a href="' . $accept_url . '" style="display:inline-block;padding:12px 32px;background:#d4a574;color:#0a0a0a;text-decoration:none;border-radius:8px;font-weight:bold;">Accept Counter Offer</a></p>' .
                '<p style="margin-top:12px;"><a href="' . $decline_url . '" style="color:#888;">Decline Counter Offer</a></p>');
            studio_send_email(get_option('admin_email'), "Counter Offer Sent — Booking #{$post_id}",
                '<p>Counter offer for <strong>' . esc_html($name) . '</strong>: $' . number_format($new_rate, 2) . '/hr &times; ' . $new_hours . ' hrs.</p>');
        }
        return;
    }

    // Details box save
    if (!isset($_POST['_studio_booking_nonce']) || !wp_verify_nonce($_POST['_studio_booking_nonce'], 'studio_save_booking')) return;

    if (isset($_POST['_client_email']))  update_post_meta($post_id, '_client_email', sanitize_email($_POST['_client_email']));
    if (isset($_POST['_client_phone']))  update_post_meta($post_id, '_client_phone', sanitize_text_field($_POST['_client_phone']));
    if (isset($_POST['_service']))       update_post_meta($post_id, '_service', sanitize_text_field($_POST['_service']));
    if (isset($_POST['_location']))      update_post_meta($post_id, '_location', sanitize_text_field($_POST['_location']));
    if (isset($_POST['_notes']))         update_post_meta($post_id, '_notes', sanitize_textarea_field($_POST['_notes']));

    // Recalculate slots
    $slot_dates  = isset($_POST['bk_slot_date'])  ? array_map('sanitize_text_field', $_POST['bk_slot_date'])  : [];
    $slot_starts = isset($_POST['bk_slot_start']) ? array_map('sanitize_text_field', $_POST['bk_slot_start']) : [];
    $slot_ends   = isset($_POST['bk_slot_end'])   ? array_map('sanitize_text_field', $_POST['bk_slot_end'])   : [];
    $slots = [];
    $total_hours = 0;
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
    update_post_meta($post_id, '_booking_slots', $slots);
    update_post_meta($post_id, '_total_hours', $total_hours);

    // Discount fields (only if status allows editing)
    $status = get_post_meta($post_id, '_status', true) ?: 'New';
    if ($status === 'Approved' && isset($_POST['_job_status'])) {
        $current_job_status = get_post_meta($post_id, '_job_status', true) ?: 'Scheduled';
        $new_job_status = sanitize_text_field($_POST['_job_status']);
        if (in_array($new_job_status, ['Scheduled', 'In Progress', 'Completed'], true)) {
            if ($new_job_status === 'Completed') {
                studio_finalize_booking_invoice($post_id);
                $invoice_id = intval(get_post_meta($post_id, '_invoice_id', true));
                $invoice_total = $invoice_id ? floatval(get_post_meta($invoice_id, '_total', true)) : 0;
                $invoice_paid = $invoice_id ? studio_invoice_paid_amount($invoice_id) : 0;
                if (!$invoice_id || $invoice_total <= 0 || $invoice_paid >= $invoice_total) $new_job_status = 'Closed';
            }
            studio_set_booking_job_status($post_id, $new_job_status);
        }
    }
    if ($status !== 'Approved') {
        if (isset($_POST['_discount']) && $_POST['_discount'] !== '') {
            update_post_meta($post_id, '_discount', floatval($_POST['_discount']));
        }
        if (isset($_POST['_discount_type'])) {
            update_post_meta($post_id, '_discount_type', sanitize_text_field($_POST['_discount_type']));
        }
    }

    // "Approve Now" from the Details form: apply the freshly-saved service + discount, price it, create the invoice and email the client.
    if (isset($_POST['approve_now']) && ($status === 'New' || $status === 'Pending') && current_user_can('manage_options')) {
        $client_email_save = get_post_meta($post_id, '_client_email', true);
        if (!$client_email_save) {
            wp_die('A client email is required before approval. Save the email above, then tick Approve Now and Update again. <a class="button button-primary" href="' . esc_url(get_edit_post_link($post_id)) . '">Back to booking</a>');
        }
        $disc = floatval(get_post_meta($post_id, '_discount', true));
        $dtyp = get_post_meta($post_id, '_discount_type', true) ?: 'percent';
        studio_do_approve($post_id, $disc, $dtyp);
        studio_cache_flush('studio_dashboard_overview');
        wp_redirect(admin_url('edit.php?post_type=studio_booking&approved_from_edit=' . $post_id));
        exit;
    }

    if (isset($_POST['resend_to_client']) && $status === 'Approved' && current_user_can('manage_options')) {
        $_POST['post_id'] = $post_id;
        $_POST['resend_nonce'] = wp_create_nonce('studio_booking_resend_' . $post_id);
        studio_booking_resend_handler();
    }

    studio_cache_flush('studio_dashboard_overview');
}
add_action('save_post_studio_booking', 'studio_save_booking_meta');

function studio_booking_meta_boxes() {
    add_meta_box('studio_booking_details', 'Booking Details', 'studio_booking_details_meta_callback', 'studio_booking', 'normal', 'high');
    add_meta_box('studio_booking_actions', 'Booking Actions', 'studio_booking_actions_meta_callback', 'studio_booking', 'side', 'high');
    add_meta_box('studio_booking_counter', 'Counter Offer', 'studio_counter_offer_meta_callback', 'studio_booking', 'side', 'default');
}
add_action('add_meta_boxes', 'studio_booking_meta_boxes');
