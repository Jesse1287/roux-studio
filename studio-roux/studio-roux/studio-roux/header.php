<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Georgia&display=swap" rel="stylesheet">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
  $current_user = wp_get_current_user();
  $logged_in = is_user_logged_in();
?>
<header class="site-header" id="site-header">
  <div class="header-inner">
    <a href="<?php echo home_url(); ?>" class="site-logo">Roux's Audio Production</a>

    <button class="nav-toggle" id="nav-toggle" aria-label="Menu" aria-expanded="false">
      <span class="nav-toggle-bar"></span>
      <span class="nav-toggle-bar"></span>
      <span class="nav-toggle-bar"></span>
    </button>

    <nav class="site-nav" id="site-nav" aria-hidden="true">
      <ul>
        <li><a href="<?php echo home_url('/'); ?>" <?php echo is_front_page() ? 'class="active"' : ''; ?>>Home</a></li>
        <li><a href="<?php echo home_url('/booking/'); ?>" <?php echo is_page('booking') ? 'class="active"' : ''; ?>>Book</a></li>
        <li><a href="<?php echo home_url('/gigs/'); ?>" <?php echo is_page('gigs') ? 'class="active"' : ''; ?>>Gigs</a></li>
        <li><a href="<?php echo home_url('/equipment/'); ?>" <?php echo is_page('equipment') ? 'class="active"' : ''; ?>>Equipment</a></li>
        <li><a href="<?php echo home_url('/blog/'); ?>" <?php echo is_page('blog') || is_singular('post') ? 'class="active"' : ''; ?>>Blog</a></li>
        <?php if ($logged_in) : ?>
          <li><a href="<?php echo home_url('/client-area/'); ?>" <?php echo is_page('client-area') ? 'class="active"' : ''; ?>>Clients</a></li>
          <li><a href="<?php echo home_url('/dashboard/'); ?>" <?php echo is_page('dashboard') ? 'class="active"' : ''; ?>>Dashboard</a></li>
        <?php endif; ?>
        <li class="nav-auth">
          <?php if ($logged_in) : ?>
            <span class="nav-user-greeting">Hi, <?php echo esc_html($current_user->display_name); ?></span>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="nav-login-btn">Logout</a>
          <?php else : ?>
            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="nav-login-btn">Login</a>
          <?php endif; ?>
        </li>
      </ul>
    </nav>
  </div>
</header>

<div class="nav-overlay" id="nav-overlay"></div>

<script>
(function(){
  var toggle = document.getElementById('nav-toggle');
  var nav = document.getElementById('site-nav');
  var overlay = document.getElementById('nav-overlay');
  var header = document.getElementById('site-header');
  if (!toggle || !nav) return;

  function openNav() {
    nav.classList.add('open');
    nav.setAttribute('aria-hidden', 'false');
    toggle.classList.add('active');
    toggle.setAttribute('aria-expanded', 'true');
    if (overlay) overlay.classList.add('open');
    document.body.classList.add('nav-open');
  }

  function closeNav() {
    nav.classList.remove('open');
    nav.setAttribute('aria-hidden', 'true');
    toggle.classList.remove('active');
    toggle.setAttribute('aria-expanded', 'false');
    if (overlay) overlay.classList.remove('open');
    document.body.classList.remove('nav-open');
  }

  toggle.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    if (nav.classList.contains('open')) {
      closeNav();
    } else {
      openNav();
    }
  });

  if (overlay) {
    overlay.addEventListener('click', closeNav);
  }

  nav.querySelectorAll('a').forEach(function(a) {
    a.addEventListener('click', closeNav);
  });

  if (header) {
    window.addEventListener('scroll', function() {
      header.classList.toggle('scrolled', window.scrollY > 20);
    });
  }
})();
</script>
