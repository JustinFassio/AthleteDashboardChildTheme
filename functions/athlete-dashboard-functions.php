<?php
// functions/athlete-dashboard-functions.php

require_once get_stylesheet_directory() . '/vendor/autoload.php';

use Google\Cloud\RecaptchaEnterprise\V1\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;

// Set Google Application Credentials
putenv('GOOGLE_APPLICATION_CREDENTIALS=' . getenv('HOME') . '/.config/gcloud/application_default_credentials.json');

/**
 * Verify reCAPTCHA Enterprise token
 *
 * @param string $token The reCAPTCHA token to verify
 * @return boolean True if verification succeeds, false otherwise
 */
function verify_recaptcha_enterprise($token) {
    $project_id = 'postmaster-api-387717';
    $site_key = '6Lf36igqAAAAANb-NCkJfUlcmEE6JO0HSo5FNVuw';
    $threshold = 0.5; // Adjust this threshold as needed

    try {
        $client = new RecaptchaEnterpriseServiceClient();
        $project_name = $client->projectName($project_id);

        $event = (new Event())
            ->setSiteKey($site_key)
            ->setToken($token);

        $assessment = (new Assessment())
            ->setEvent($event);

        $response = $client->createAssessment($project_name, $assessment);

        if (!$response->getTokenProperties()->getValid()) {
            $invalid_reason = InvalidReason::name($response->getTokenProperties()->getInvalidReason());
            error_log("Invalid reCAPTCHA token. Reason: $invalid_reason");
            return false;
        }

        $score = $response->getRiskAnalysis()->getScore();
        error_log("reCAPTCHA score: $score");
        
        if ($score < $threshold) {
            error_log("reCAPTCHA score too low: $score");
            return false;
        }

        return true;
    } catch (Exception $exception) {
        error_log('Error verifying reCAPTCHA: ' . $exception->getMessage());
        error_log('Exception trace: ' . $exception->getTraceAsString());
        return false;
    }
}

/**
 * Handle user registration form submission
 */
function handle_registration_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_nonce']) && wp_verify_nonce($_POST['register_nonce'], 'custom_register_nonce')) {
        $recaptcha_token = isset($_POST['recaptcha_token']) ? $_POST['recaptcha_token'] : '';
        
        if (empty($recaptcha_token)) {
            wp_die('reCAPTCHA verification failed. Token is missing.');
        }
        
        if (!verify_recaptcha_v3($recaptcha_token)) {
            wp_die('reCAPTCHA verification failed. Please try again.');
        }
        
        // Proceed with user registration
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            // Handle registration error
            wp_die($user_id->get_error_message());
        } else {
            // Update user meta
            wp_update_user(array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name
            ));

            // Log the user in
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);

            // Redirect to dashboard
            wp_redirect(home_url('/athlete-dashboard'));
            exit;
        }
    }
}

// Hook the registration form handler to WordPress init
add_action('init', 'handle_registration_form');

/**
 * Verify reCAPTCHA v3 response
 *
 * @param string $token The reCAPTCHA v3 token to verify
 * @return boolean True if verification succeeds, false otherwise
 */
function verify_recaptcha_v3($token) {
    $secret_key = '6Lc1Ly0qAAAAAONPgobQXpe4duibuganLcCJFnjs';
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';

    $data = array(
        'secret' => $secret_key,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );

    $options = array(
        'body' => $data,
        'timeout' => 60,
    );

    $response = wp_remote_post($verify_url, $options);

    if (is_wp_error($response)) {
        error_log('Error verifying reCAPTCHA v3: ' . $response->get_error_message());
        return false;
    }

    $result = json_decode(wp_remote_retrieve_body($response));

    if ($result->success == true && $result->score >= 0.5) {
        return true;
    } else {
        error_log('reCAPTCHA v3 verification failed. Score: ' . ($result->score ?? 'N/A'));
        return false;
    }
}