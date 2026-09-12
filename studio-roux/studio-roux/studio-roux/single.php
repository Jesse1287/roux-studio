<?php get_header(); ?>
<main class="page-content">
  <div class="container container-narrow">
    <?php if (have_posts()) : while (have_posts()) : the_post();
      $cats = get_the_category();
      $cat_name = !empty($cats) ? $cats[0]->name : '';
      $cat_slug = !empty($cats) ? $cats[0]->slug : '';
    ?>

    <a href="<?php echo home_url('/blog/'); ?>" style="color:var(--text-muted);font-size:0.85rem;text-decoration:none;">&laquo; Back to Blog</a>

    <article style="margin-top:24px;">
      <?php if ($cat_name) : ?>
        <a href="<?php echo add_query_arg('cat', $cat_slug, home_url('/blog/')); ?>" class="badge badge-sent" style="text-decoration:none;margin-bottom:16px;display:inline-block;"><?php echo esc_html($cat_name); ?></a>
      <?php endif; ?>

      <h1 style="margin-bottom:12px;font-size:2.2rem;"><?php the_title(); ?></h1>

      <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:32px;">
        By <?php echo esc_html(get_the_author()); ?> &middot; <?php echo get_the_date('F j, Y'); ?>
      </p>

      <?php if (has_post_thumbnail()) : ?>
        <div style="margin-bottom:32px;border-radius:var(--radius);overflow:hidden;">
          <?php the_post_thumbnail('large', ['style' => 'width:100%;height:auto;display:block;', 'loading' => 'lazy']); ?>
        </div>
      <?php endif; ?>

      <div class="blog-content" style="color:var(--text);line-height:1.9;font-size:1.05rem;">
        <?php the_content(); ?>
      </div>

      <?php if (!empty($cats)) : ?>
        <div style="margin-top:40px;padding-top:24px;border-top:1px solid var(--surface-border);">
          <p style="color:var(--text-muted);font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">Filed under</p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach ($cats as $cat) : ?>
              <a href="<?php echo add_query_arg('cat', $cat->slug, home_url('/blog/')); ?>" class="badge badge-draft" style="text-decoration:none;"><?php echo esc_html($cat->name); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </article>

    <?php
    // Post navigation
    $prev = get_previous_post();
    $next = get_next_post();
    if ($prev || $next) : ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:40px;">
      <?php if ($prev) : ?>
        <a href="<?php echo get_permalink($prev->ID); ?>" class="glass glass-compact" style="text-decoration:none;">
          <p style="color:var(--text-muted);font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">&laquo; Previous</p>
          <p style="color:var(--gold);font-weight:600;font-size:0.9rem;"><?php echo esc_html($prev->post_title); ?></p>
        </a>
      <?php else : ?>
        <div></div>
      <?php endif; ?>
      <?php if ($next) : ?>
        <a href="<?php echo get_permalink($next->ID); ?>" class="glass glass-compact" style="text-decoration:none;text-align:right;">
          <p style="color:var(--text-muted);font-size:0.7rem;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">Next &raquo;</p>
          <p style="color:var(--gold);font-weight:600;font-size:0.9rem;"><?php echo esc_html($next->post_title); ?></p>
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endwhile; endif; ?>
  </div>
</main>
<?php get_footer(); ?>
