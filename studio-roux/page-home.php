<?php
/**
 * Template Name: Home
 */
get_header();

$hero_image = get_post_meta(get_the_ID(), '_hero_image_url', true);
$hero_class = $hero_image ? ' hero bg-image' : '';
$hero_style = $hero_image ? ' style="background-image:url(' . esc_url($hero_image) . ')"' : '';
?>

<?php if ($hero_image) : ?>
<div class="fixed-hero-bg" style="background-image:url(<?php echo esc_url($hero_image); ?>);" aria-hidden="true"></div>
<?php endif; ?>

<main class="page-content" style="position:relative;z-index:1;">

  <!-- Hero content scrolls with the page -->
  <section class="hero hero-content">
    <div class="container">
      <h1>Roux's Audio Production</h1>
      <p style="font-size:1.25rem;color:var(--text);max-width:640px;margin:0 auto 20px;line-height:1.8;">Professional-grade engineering, mixing, and mastering. Focused on signal integrity, technical precision, and high-fidelity results.</p>
      <p style="color:var(--text-muted);max-width:520px;margin:0 auto 40px;">From tracking to final master, every session is built around clean signal chains, intentional gain staging, and a deep understanding of what makes a mix translate.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
        <a href="<?php echo home_url('/booking/'); ?>" class="hero-cta">Book a Session</a>
      </div>
    </div>
  </section>

  <section>
    <div class="container">
      <div class="section-title">
        <h2>What We Do</h2>
        <p>Full-spectrum audio services for artists, bands, and venues.</p>
      </div>
      <div class="grid grid-3">
        <div class="glass">
          <h4 style="color:var(--gold);margin-bottom:8px;">Recording</h4>
          <p style="color:var(--text-muted);font-size:0.9rem;">Multi-track recording with quality preamps and carefully chosen microphones. Every source gets the attention it deserves from the first take.</p>
        </div>
        <div class="glass">
          <h4 style="color:var(--gold);margin-bottom:8px;">Mixing &amp; Mastering</h4>
          <p style="color:var(--text-muted);font-size:0.9rem;">Mixing with purpose and mastering for translation. Balanced low end, clear dynamics, and a finished product that sounds right everywhere.</p>
        </div>
        <div class="glass">
          <h4 style="color:var(--gold);margin-bottom:8px;">Live Sound</h4>
          <p style="color:var(--text-muted);font-size:0.9rem;">FOH engineering for live events. Fast problem-solving, reliable setups, and a mix that serves the room and the performers.</p>
        </div>
      </div>
    </div>
  </section>

  <section style="background:linear-gradient(180deg,transparent,rgba(212,165,116,0.03),transparent);">
    <div class="container">
      <div class="glass" style="max-width:720px;margin:0 auto;text-align:center;">
        <h3 style="margin-bottom:16px;">Free Consultation</h3>
        <p style="color:var(--text-muted);margin-bottom:24px;">Not sure which service fits your project? Let's talk about what you need. No commitment, no pressure.</p>
        <a href="<?php echo home_url('/booking/'); ?>" class="btn btn-primary">Get in Touch</a>
      </div>
    </div>
  </section>

  <!-- Live Financial Overview (Admin-visible section) -->
  <?php if (current_user_can('manage_options')) : ?>
  <section style="background:linear-gradient(180deg,transparent,rgba(212,165,116,0.03),transparent);">
    <div class="container">
      <div class="section-title">
        <h2>Studio Financials — <?php echo date('F Y'); ?></h2>
        <p style="color:var(--text-muted);">Live dashboard refreshed every 10 minutes.</p>
      </div>
      <div class="glass" style="overflow:hidden;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:20px;margin-bottom:32px;">
          <?php
          $fd = studio_financial_svg_data();
          ?>
          <div style="text-align:center;">
            <p style="font-size:2rem;font-weight:600;color:var(--green);">$<?php echo number_format($fd['revenue'], 0); ?></p>
            <p style="color:var(--text-muted);font-size:0.75rem;letter-spacing:1px;text-transform:uppercase;">Revenue</p>
          </div>
          <div style="text-align:center;">
            <p style="font-size:2rem;font-weight:600;color:var(--red);">$<?php echo number_format($fd['expenses'], 0); ?></p>
            <p style="color:var(--text-muted);font-size:0.75rem;letter-spacing:1px;text-transform:uppercase;">Expenses</p>
          </div>
          <div style="text-align:center;">
            <p style="font-size:2rem;font-weight:600;color:<?php echo $fd['net'] >= 0 ? 'var(--green)' : 'var(--red)'; ?>;">$<?php echo number_format($fd['net'], 0); ?></p>
            <p style="color:var(--text-muted);font-size:0.75rem;letter-spacing:1px;text-transform:uppercase;">Net</p>
          </div>
          <div style="text-align:center;">
            <p style="font-size:2rem;font-weight:600;color:var(--gold);"><?php echo $fd['approved']; ?></p>
            <p style="color:var(--text-muted);font-size:0.75rem;letter-spacing:1px;text-transform:uppercase;">Sessions</p>
          </div>
        </div>

        <!-- Revenue vs Expense Bar Chart -->
        <div style="display:flex;justify-content:center;padding:24px 0;">
          <?php echo studio_render_finance_svg(); ?>
        </div>

        <div style="display:flex;justify-content:center;gap:24px;font-size:0.8rem;color:var(--text-muted);margin-top:-8px;">
          <span><span style="display:inline-block;width:10px;height:10px;background:#d4a574;border-radius:2px;"></span> Revenue</span>
          <span><span style="display:inline-block;width:10px;height:10px;background:#dc3545;border-radius:2px;"></span> Expenses</span>
        </div>

        <p style="text-align:center;font-size:0.7rem;color:var(--text-dim);margin-top:20px;">For operational overview. File [studio_invoice_dashboard] on any page for detailed tax view.</p>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php echo do_shortcode('[studio_promos]'); ?>

</main>
<?php get_footer(); ?>
