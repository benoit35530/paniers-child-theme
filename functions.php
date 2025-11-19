<?php

/**
 * @package Paniers
 *
 * @version 1.0.0
 */


// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Enqueue scripts and styles
 */
add_action('wp_enqueue_scripts', 'bootscore_child_enqueue_styles');
function bootscore_child_enqueue_styles() {

  // Compiled main.css
  $modified_bootscoreChildCss = date('YmdHi', filemtime(get_stylesheet_directory() . '/assets/css/main.css'));
  wp_enqueue_style('main', get_stylesheet_directory_uri() . '/assets/css/main.css', array('parent-style'), $modified_bootscoreChildCss);

  // style.css
  wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');

  // custom.js
  // Get modification time. Enqueue file with modification date to prevent browser from loading cached scripts when file content changes.
  $modificated_CustomJS = date('YmdHi', filemtime(get_stylesheet_directory() . '/assets/js/custom.js'));
  wp_enqueue_script('custom-js', get_stylesheet_directory_uri() . '/assets/js/custom.js', array('jquery'), $modificated_CustomJS, false, true);
}

add_action('init', function() {
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
});

add_action('after_setup_theme', function() {
	register_nav_menu( 'account-menu', __( 'Account Menu', 'theme-text-domain' ) );
    $user = wp_get_current_user();
    if ($user && $user->has_cap('consommateur') && !$user->has_cap('gestionnaire')) {
        add_filter( 'show_admin_bar', '__return_false' );
    }
});

add_action('bootscore_after_nav_toggler', function() {
    ?>
<button class="btn ms-1 ms-md-2 account-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-user" aria-controls="offcanvas-user">
    <i class="fa-solid fa-user"></i> <span class="visually-hidden-focusable">Mon compte</span>
</button>
<?php
});

add_action('bootscore_before_masthead_close', function() {
    if (is_user_logged_in()) {
    ?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-user">
    <div class="offcanvas-header">
        <span class="h5 offcanvas-title">Mon compte</span>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body position-relative">
        <?= wp_nav_menu(array(
                'theme_location' => 'account-menu',
                'container'      => false,
                'menu_class'     => '',
                'fallback_cb'    => '__return_false',
                'items_wrap'     => '<ul id="bootscore-navbar" class="navbar-nav ' . apply_filters('bootscore/class/header/navbar-nav', 'ms-auto') . ' %2$s">%3$s</ul>',
                'depth'          => 2,
                'walker'         => new bootstrap_5_wp_nav_menu_walker()
                )); ?>
    </div>
</div>
    <?php
    } else {
    ?>
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-user">
    <div class="offcanvas-header">
        <span class="h5 offcanvas-title">Connexion</span>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body position-relative">
        <form name="loginform" id="loginform" action="/wp-login.php" method="post">
			<div class="mb-3">
				<label for="user_login" class="form-label">Identifiant ou adresse e-mail</label>
				<input type="text" name="log" id="user_login" class="form-control" value="" size="20" autocapitalize="off" autocomplete="username" required="required">
            </div>
			<div class="mb-3">
				<label for="user_pass" class="form-label">Mot de passe</label>
                <input type="password" name="pwd" id="user_pass" class="form-control" value="" size="20" autocomplete="current-password" spellcheck="false" required="required">
			</div>
            <div class="mb-3 form-check">
                <input name="rememberme" type="checkbox" id="rememberme" value="forever" class="form-check-input" checked>
                <label for="rememberme" class="form-check-label">Se souvenir de moi</label>
            </div>
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="btn btn-primary" value="Se connecter">
                <input type="hidden" name="redirect_to" value="/">
				<input type="hidden" name="testcookie" value="1" />
			</p>
		</form>
        <p id="nav">
            <a class="wp-login-register" href="/inscription">Inscription</a> | <a class="wp-login-lost-password" href="/wp-login.php?action=lostpassword">Mot de passe oublié&nbsp;?</a>
        </p>
    </div>
</div>
    <?php
    }
});

add_filter( 'wp_calculate_image_srcset', function ($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    $remove = ['1536', '2048'];
    $sources = array_diff_key($sources, array_flip($remove));
    return $sources;
}, 10, 5);

add_filter('intermediate_image_sizes_advanced', function($new_sizes, $image_meta, $attachment_id) {
    unset($new_sizes['1536x1536']);
    unset($new_sizes['2048x2048']);
    return $new_sizes;
}, 10, 3);

add_filter('wp_get_nav_menu_items', function($items, $menu) {
    if ($menu->slug == 'account') {
        foreach ($items as $key => $value) {
            if ($value->title == "Déconnexion") {
                $value->url = wp_logout_url('/');
            }
        }
    }
    return $items;
}, 10, 2);

add_action( 'admin_bar_menu', function( \WP_Admin_Bar $bar )
{
    $bar->add_menu( array(
        'id'     => 'wpse',
        'parent' => null,
        'group'  => null,
        'title'  => __( 'Administration des Paniers', 'paniers-admin' ),
        'href'   => '/paniers/admin',
    ) );
}, 210);

add_filter('auth_cookie_expiration', function ($expirein, $userid, $rememberme) {
    if ($rememberme) {
        return MONTH_IN_SECONDS * 12;
    } else {
        return $expirein;
    }
}, 10, 3);

function block_table_class() {
  return "table-bordered table-striped table-hover";
}
add_filter('bootscore/class/block/table', 'block_table_class', 10, 2);

function block_table_content($block_content, $block) {
    return str_replace("table-responsive", "table-responsive-lg", $block_content);
}
add_filter('bootscore/block/table/content', 'block_table_content', 10, 2);

// Login page customization
add_filter('login_headerurl', function () { return home_url(); });
add_filter('login_headertext', function () { return 'Les Paniers d\'EDEN'; });
add_filter('login_display_language_dropdown', function() { return false; });

function paniers_login_enqueue_style() {
	wp_enqueue_style('bootscore-child', get_stylesheet_directory_uri() . '/assets/css/main.css', false);
}
add_action('login_enqueue_scripts', 'paniers_login_enqueue_style', 10);
