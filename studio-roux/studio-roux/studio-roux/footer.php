<footer class="site-footer">
  <div class="container">
    <p>&copy; <?php echo date('Y'); ?> Roux's Audio Production. All rights reserved.</p>
    <ul class="footer-links">
      <li><a href="<?php echo home_url('/booking/'); ?>">Book</a></li>
      <li><a href="<?php echo home_url('/gigs/'); ?>">Gigs</a></li>
      <li><a href="<?php echo home_url('/equipment/'); ?>">Gear</a></li>
      <li><a href="<?php echo home_url('/blog/'); ?>">Blog</a></li>
      <?php if (is_user_logged_in()) : ?>
        <li><a href="<?php echo home_url('/client-area/'); ?>">Clients</a></li>
        <li><a href="<?php echo home_url('/dashboard/'); ?>">Dashboard</a></li>
      <?php endif; ?>
      <li>
        <?php if (is_user_logged_in()) : ?>
          <a href="<?php echo wp_logout_url(home_url()); ?>">Logout</a>
        <?php else : ?>
          <a href="<?php echo wp_login_url(get_permalink()); ?>">Login</a>
        <?php endif; ?>
      </li>
    </ul>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
