<?php
/*
Template Name: Custom Registration
*/
// Redirect if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/athlete-dashboard'));
    exit;
}

// Define reCAPTCHA v3 site key
$recaptcha_site_key = '6Lc1Ly0qAAAAAF37K-Y8vkcCJQsiPrGADWD4T137';

get_header();
?>

<div class="athlete-dashboard registration-page">
    <div class="auth-form registration-form">
        <h2><?php echo esc_html__('Register', 'athlete-dashboard'); ?></h2>
        <form id="registration-form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
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
            <button type="submit" class="custom-button" id="submit-button">
                <?php echo esc_html__('Register', 'athlete-dashboard'); ?>
            </button>
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

<script src="https://www.google.com/recaptcha/api.js?render=<?php echo esc_attr($recaptcha_site_key); ?>"></script>
<script>
grecaptcha.ready(function() {
    document.getElementById('registration-form').addEventListener('submit', function(e) {
        e.preventDefault();
        grecaptcha.execute('<?php echo esc_js($recaptcha_site_key); ?>', {action: 'register'}).then(function(token) {
            document.getElementById('registration-form').insertAdjacentHTML('beforeend', '<input type="hidden" name="recaptcha_token" value="' + token + '">');
            document.getElementById('registration-form').submit();
        });
    });
});
</script>

<?php get_footer(); ?>