<?php
/**
 * Template Name: Gigs
 */
get_header(); ?>
<main class="page-content">
  <div class="container">
    <div class="section-title">
      <h2>Upcoming Gigs</h2>
      <p>Catch us live or book us for your next event.</p>
    </div>

    <?php
    $gigs = new WP_Query([
      'post_type' => 'studio_gig',
      'posts_per_page' => 12,
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
          $ticket_url = get_post_meta(get_the_ID(), '_gig_ticket_url', true);
        ?>
          <div class="glass">
            <p class="gig-date"><?php echo $date ? date('M j, Y', strtotime($date)) : ''; ?></p>
            <h3 style="margin-bottom:8px;"><?php the_title(); ?></h3>
            <?php if ($venue) : ?><p style="color:var(--text-muted);margin-bottom:4px;"><?php echo esc_html($venue); ?></p><?php endif; ?>
            <?php if ($time) : ?><p style="color:var(--text-muted);margin-bottom:16px;">Doors: <?php echo esc_html($time); ?></p><?php endif; ?>
            <div style="color:var(--text);font-size:0.95rem;"><?php the_content(); ?></div>
            <?php if ($ticket_url) : ?>
              <a href="<?php echo esc_url($ticket_url); ?>" class="btn btn-sm" target="_blank" style="margin-top:16px;">Get Tickets</a>
            <?php endif; ?>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php else : ?>
      <div class="glass" style="text-align:center;">
        <p style="color:var(--text-muted);">No upcoming gigs at this time. Check back soon!</p>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
