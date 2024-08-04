<?php
/*
Template Name: Custom Login
*/

// Redirect if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/athlete-dashboard'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_nonce']) && wp_verify_nonce($_POST['login_nonce'], 'custom_login_nonce')) {
    $username = sanitize_user($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    $creds = array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember
    );

    $user = wp_signon($creds, is_ssl());

    if (!is_wp_error($user)) {
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        wp_safe_redirect(home_url('/athlete-dashboard'));
        exit;
    } else {
        $error = $user->get_error_message();
    }
}

get_header();
?>

<div class="athlete-dashboard login-page">
    <div class="auth-form login-form">
        <h2><?php echo esc_html__('Login', 'athlete-dashboard'); ?></h2>
        <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
            <div class="form-group">
                <label for="username"><?php echo esc_html__('Username or Email', 'athlete-dashboard'); ?></label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password"><?php echo esc_html__('Password', 'athlete-dashboard'); ?></label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="remember" id="remember">
                    <?php echo esc_html__('Remember Me', 'athlete-dashboard'); ?>
                </label>
            </div>
            <?php wp_nonce_field('custom_login_nonce', 'login_nonce'); ?>
            <div class="form-group">
                <input type="submit" value="<?php echo esc_attr__('Login', 'athlete-dashboard'); ?>" class="custom-button">
            </div>
        </form>
        <?php if (isset($error)) : ?>
            <p class="error"><?php echo esc_html($error); ?></p>
        <?php endif; ?>
        <p class="register-link">
            <?php echo esc_html__('Don\'t have an account?', 'athlete-dashboard'); ?> 
            <a href="<?php echo esc_url(home_url('/register')); ?>"><?php echo esc_html__('Register here', 'athlete-dashboard'); ?></a>
        </p>
    </div>
</div>

<?php get_footer(); ?>
