<?php
/**
 * Template Name: Home
 */
get_header(); ?>
<main class="page-content" style="padding-top:0;">

  <?php
  $hero_image = get_post_meta(get_the_ID(), '_hero_image_url', true);
  $hero_class = $hero_image ? ' hero bg-image' : '';
  $hero_style = $hero_image ? ' style="background-image:url(' . esc_url($hero_image) . ')"' : '';
  ?>
  <section class="hero<?php echo $hero_class; ?>"<?php echo $hero_style; ?>>
    <div class="container">
      <h1>Roux's Audio Production</h1>
      <p style="font-size:1.25rem;color:var(--text);max-width:640px;margin:0 auto 20px;line-height:1.8;">Professional-grade engineering, mixing, and mastering. Focused on signal integrity, technical precision, and high-fidelity results.</p>
      <p style="color:var(--text-muted);max-width:520px;margin:0 auto 40px;">From tracking to final master, every session is built around clean signal chains, intentional gain staging, and a deep understanding of what makes a mix translate.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
        <a href="<?php echo home_url('/booking/'); ?>" class="hero-cta">Book a Session</a>
        <a href="<?php echo home_url('/gigs/'); ?>" class="hero-cta" style="background:transparent;color:var(--gold);border:1px solid var(--gold);">Upcoming Gigs</a>
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

  <section>
    <div class="container">
      <div class="section-title">
        <h2>Upcoming Gigs</h2>
        <p>Catch us live or book us for your next event.</p>
      </div>
      <?php
      $gigs = new WP_Query([
        'post_type' => 'studio_gig',
        'posts_per_page' => 4,
        'meta_key' => '_gig_date',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
        'meta_query' => [['key' => '_gig_date', 'value' => date('Y-m-d'), 'compare' => '>=']]
      ]);
      if ($gigs->have_posts()) : ?>
        <div class="grid grid-2">
          <?php while ($gigs->have_posts()) : $gigs->the_post();
            $date = get_post_meta(get_the_ID(), '_gig_date', true);
            $venue = get_post_meta(get_the_ID(), '_gig_venue', true);
            $time = get_post_meta(get_the_ID(), '_gig_time', true);
            $ticket = get_post_meta(get_the_ID(), '_gig_ticket_url', true);
          ?>
            <div class="glass">
              <p class="gig-date"><?php echo $date ? date('M j, Y', strtotime($date)) : ''; ?></p>
              <h3 style="margin-bottom:4px;"><?php the_title(); ?></h3>
              <?php if ($venue) : ?><p style="color:var(--text-muted);font-size:0.9rem;"><?php echo esc_html($venue); ?></p><?php endif; ?>
              <?php if ($time) : ?><p style="color:var(--text-muted);font-size:0.85rem;">Doors: <?php echo esc_html($time); ?></p><?php endif; ?>
              <?php if ($ticket) : ?><a href="<?php echo esc_url($ticket); ?>" class="btn btn-sm" target="_blank" rel="noopener" style="margin-top:12px;">Tickets</a><?php endif; ?>
            </div>
          <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <div style="text-align:center;margin-top:32px;">
          <a href="<?php echo home_url('/gigs/'); ?>" class="btn">View All Gigs</a>
        </div>
      <?php else : ?>
        <div class="glass" style="text-align:center;"><p style="color:var(--text-muted);">No upcoming gigs scheduled right now. Check back soon.</p></div>
      <?php endif; ?>
    </div>
  </section>

  <?php echo do_shortcode('[studio_promos]'); ?>

</main>
<?php get_footer(); ?>
