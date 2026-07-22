<?php get_header(); ?>
<main class="page-content">
  <div class="container">
    <?php if (have_posts()) : ?>
      <div class="section-title">
        <h2><?php is_home() ? the_title() : esc_html_e('Latest Posts', 'studio-roux'); ?></h2>
      </div>
      <div class="grid grid-2">
        <?php while (have_posts()) : the_post(); ?>
          <article class="glass">
            <?php if (has_post_thumbnail()) : ?>
              <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium', ['style' => 'border-radius:var(--radius-sm);margin-bottom:20px;width:100%']); ?></a>
            <?php endif; ?>
            <p class="gig-date"><?php echo get_the_date(); ?></p>
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <div style="color:var(--text);font-size:0.95rem;"><?php the_excerpt(); ?></div>
            <a href="<?php the_permalink(); ?>" class="btn btn-sm" style="margin-top:16px;">Read More</a>
          </article>
        <?php endwhile; ?>
      </div>
      <div style="text-align:center;margin-top:40px;">
        <?php
        the_posts_pagination([
          'prev_text' => '&laquo;',
          'next_text' => '&raquo;',
          'class' => '',
        ]);
        ?>
      </div>
    <?php else : ?>
      <div class="glass" style="text-align:center;padding:80px 40px;">
        <h3>No content found.</h3>
        <p style="color:var(--text-muted);margin-top:12px;">Nothing here yet. Check back soon.</p>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
