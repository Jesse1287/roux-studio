<?php
/**
 * Template Name: Blog
 */
get_header();

$current_cat = isset($_GET['cat']) ? sanitize_text_field($_GET['cat']) : '';

$args = [
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'orderby' => 'date',
    'order' => 'DESC',
];

if ($current_cat && $current_cat !== 'all') {
    $args['cat_name'] = $current_cat;
}

$blog = new WP_Query($args);
$categories = get_categories(['hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC']);
?>
<main class="page-content">
  <div class="container">
    <div class="section-title">
      <h2>Blog</h2>
      <p>Thoughts on music, faith, and the craft of audio production.</p>
    </div>

    <?php if (!empty($categories)) : ?>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:40px;justify-content:center;">
        <a href="<?php echo get_permalink(); ?>" class="btn btn-sm <?php echo !$current_cat || $current_cat === 'all' ? 'btn-primary' : ''; ?>">All</a>
        <?php foreach ($categories as $cat) : ?>
          <a href="<?php echo add_query_arg('cat', $cat->slug, get_permalink()); ?>" class="btn btn-sm <?php echo $current_cat === $cat->slug ? 'btn-primary' : ''; ?>"><?php echo esc_html($cat->name); ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($blog->have_posts()) : ?>
      <div class="grid grid-2">
        <?php while ($blog->have_posts()) : $blog->the_post();
          $cats = get_the_category();
          $cat_name = !empty($cats) ? $cats[0]->name : '';
          $cat_slug = !empty($cats) ? $cats[0]->slug : '';
          $has_thumb = has_post_thumbnail();
          $date = get_the_date('M j, Y');
        ?>
          <article class="glass" style="padding:0;overflow:hidden;display:flex;flex-direction:column;">
            <?php if ($has_thumb) : ?>
              <a href="<?php the_permalink(); ?>" style="display:block;aspect-ratio:16/9;overflow:hidden;">
                <?php the_post_thumbnail('medium_large', ['style' => 'width:100%;height:100%;object-fit:cover;transition:transform 0.3s ease;', 'loading' => 'lazy']); ?>
              </a>
            <?php else : ?>
              <a href="<?php the_permalink(); ?>" class="img-placeholder" style="aspect-ratio:16/9;border-radius:0;">
                <span style="color:var(--gold);font-size:2rem;opacity:0.3;">&#9835;</span>
              </a>
            <?php endif; ?>

            <div style="padding:24px 28px 28px;flex:1;display:flex;flex-direction:column;">
              <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <?php if ($cat_name) : ?>
                  <a href="<?php echo add_query_arg('cat', $cat_slug, get_permalink()); ?>" class="badge badge-sent" style="text-decoration:none;"><?php echo esc_html($cat_name); ?></a>
                <?php endif; ?>
                <span style="color:var(--text-muted);font-size:0.8rem;"><?php echo $date; ?></span>
              </div>

              <h3 style="margin-bottom:10px;font-size:1.15rem;"><a href="<?php the_permalink(); ?>" style="color:var(--gold);text-decoration:none;"><?php the_title(); ?></a></h3>

              <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;flex:1;margin-bottom:16px;">
                <?php echo wp_trim_words(get_the_excerpt(), 30); ?>
              </p>

              <a href="<?php the_permalink(); ?>" style="color:var(--gold);font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;text-decoration:none;font-weight:600;">Read More &rarr;</a>
            </div>
          </article>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>

      <?php if ($blog->max_num_pages > 1) : ?>
        <div style="display:flex;justify-content:center;gap:12px;margin-top:40px;">
          <?php echo paginate_links(['prev_text' => '&laquo; Previous', 'next_text' => 'Next &raquo;', 'type' => 'plaintext']); ?>
        </div>
      <?php endif; ?>

    <?php else : ?>
      <div class="glass" style="text-align:center;">
        <p style="color:var(--text-muted);font-size:1.1rem;">No posts yet. Check back soon!</p>
      </div>
    <?php endif; ?>
  </div>
</main>
<?php get_footer(); ?>
