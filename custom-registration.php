<?php
/*
Template Name: Custom Registration
*/
// Redirect if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/athlete-dashboard'));
    exit;
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_nonce']) && wp_verify_nonce($_POST['register_nonce'], 'custom_register_nonce')) {
    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $user_id = register_new_user($username, $email);
    if (!is_wp_error($user_id)) {
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name
        ));
        wp_set_password($password, $user_id);
        // Log the user in
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true
        );
        $user = wp_signon($creds, is_ssl());
        if (!is_wp_error($user)) {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            wp_safe_redirect(home_url('/athlete-dashboard'));
            exit;
        }
    } else {
        $error = $user_id->get_error_message();
    }
}
get_header();
?>
<div class="athlete-dashboard registration-page">
    <div class="auth-form registration-form">
        <h2><?php echo esc_html__('Register', 'athlete-dashboard'); ?></h2>
        <form action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
            <div class="form-group">
                <label for="username"><?php echo esc_html__('Username', 'athlete-dashboard'); ?></label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="email"><?php echo esc_html__('Email', 'athlete-dashboard'); ?></label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="password"><?php echo esc_html__('Password', 'athlete-dashboard'); ?></label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label for="first_name"><?php echo esc_html__('First Name', 'athlete-dashboard'); ?></label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name"><?php echo esc_html__('Last Name', 'athlete-dashboard'); ?></label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            <?php wp_nonce_field('custom_register_nonce', 'register_nonce'); ?>
            <div class="form-group">
                <input type="submit" value="<?php echo esc_attr__('Register', 'athlete-dashboard'); ?>" class="custom-button">
            </div>
        </form>
        <?php if (isset($error)) : ?>
            <p class="error"><?php echo esc_html($error); ?></p>
        <?php endif; ?>
        <p class="login-link">
            <?php echo esc_html__('Already have an account?', 'athlete-dashboard'); ?> 
            <a href="<?php echo esc_url(wp_login_url()); ?>"><?php echo esc_html__('Login here', 'athlete-dashboard'); ?></a>
        </p>
    </div>
</div>
<?php get_footer(); ?>
