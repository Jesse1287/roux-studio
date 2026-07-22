<?php
/**
 * Template Name: Booking Form
 */
get_header();

if (isset($_GET['booking']) && $_GET['booking'] === 'sent') : ?>
<main class="page-content">
  <div class="container container-narrow" style="padding:120px 24px;text-align:center;">
    <div class="glass">
      <h2>Booking Request Sent</h2>
      <p style="color:var(--text-muted);margin:20px 0;">Thank you! We've received your booking request and will review it within 24 hours. You'll receive a confirmation email shortly.</p>
      <a href="<?php echo home_url('/booking/'); ?>" class="btn">Submit Another</a>
    </div>
  </div>
</main>
<?php else : ?>
<main class="page-content">
  <div class="container container-narrow">
    <div class="section-title">
      <h2>Book a Session</h2>
      <p>Select your service, choose a date and time, and we'll get back to you within 24 hours.</p>
    </div>

    <?php if (isset($_GET['booking']) && $_GET['booking'] === 'error') : ?>
      <div class="alert alert-error">There was an error submitting your booking. Please try again.</div>
    <?php endif; ?>

    <?php if (isset($_GET['booking']) && $_GET['booking'] === 'rate_limited') : ?>
      <div class="alert alert-error">Too many booking attempts. Please try again later.</div>
    <?php endif; ?>

    <div class="glass">
      <form method="post" action="">
        <?php wp_nonce_field('studio_booking_submit', '_booking_nonce'); ?>
        <input type="hidden" name="studio_booking" value="1">

        <div class="form-group">
          <label for="name">Full Name *</label>
          <input type="text" id="name" name="name" required placeholder="Your name">
        </div>

        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required placeholder="you@example.com">
        </div>

        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" placeholder="(555) 555-5555">
        </div>

        <div class="form-group">
          <label for="service">Service *</label>
          <select id="service" name="service" required>
            <option value="">Select a service...</option>
            <option value="Tier 1 - Recording ($75/hr)" data-hourly="true">Tier 1 - Recording ($75/hr)</option>
            <option value="Tier 2 - Mixing ($150)">Tier 2 - Mixing ($150)</option>
            <option value="Tier 3 - Mixing + Mastering ($200)">Tier 3 - Mixing + Mastering ($200)</option>
            <option value="Tier 4 - Full Production ($250)">Tier 4 - Full Production ($250)</option>
            <option value="FOH Engineer ($50/hr)" data-hourly="true">FOH Engineer - Live Sound ($50/hr)</option>
            <option value="Consultation (Free)">Consultation (Free)</option>
          </select>
        </div>

        <div id="hourly-slots" style="display:none;">
          <div class="form-group">
            <label>Sessions (Date + Time)</label>
            <div id="slot-container">
              <div class="slot-row">
                <input type="date" name="slot_date[]" required>
                <input type="time" name="slot_start[]" placeholder="Start" required>
                <input type="time" name="slot_end[]" placeholder="End" required>
              </div>
            </div>
            <button type="button" class="btn btn-sm" id="add-slot-btn" style="margin-top:8px;">+ Add Another Session</button>
          </div>
        </div>

        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" placeholder="Studio or venue address">
        </div>

        <div class="form-group">
          <label for="notes">Additional Notes</label>
          <textarea id="notes" name="notes" rows="4" placeholder="Tell us about your project..."></textarea>
        </div>

        <div class="cf-turnstile" style="margin-bottom:24px;" data-sitekey="<?php echo defined('STUDIO_TURNSTILE_SITEKEY') ? esc_attr(STUDIO_TURNSTILE_SITEKEY) : ''; ?>"></div>

        <button type="submit" class="btn btn-primary btn-block" style="padding:16px;">Submit Booking Request</button>
      </form>
    </div>
  </div>
</main>
<?php endif; ?>
<?php get_footer(); ?>
