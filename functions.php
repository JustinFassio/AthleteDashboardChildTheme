<?php
// functions.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load core files
require_once get_stylesheet_directory() . '/functions/autoloader.php';
require_once get_stylesheet_directory() . '/functions/enqueue-scripts.php';
require_once get_stylesheet_directory() . '/functions/custom-post-types.php';
require_once get_stylesheet_directory() . '/functions/ajax-handlers.php';
require_once get_stylesheet_directory() . '/functions/user-profile.php';
require_once get_stylesheet_directory() . '/functions/shortcodes.php';
require_once get_stylesheet_directory() . '/functions/progress-tracking.php';
require_once get_stylesheet_directory() . '/functions/dashboard-rendering.php';
require_once get_stylesheet_directory() . '/functions/debug.php';
require_once get_stylesheet_directory() . '/functions/exercise-ajax-handlers.php';
require_once get_stylesheet_directory() . '/functions/exercise-data.php';
require_once get_stylesheet_directory() . '/functions/user-data.php';
require_once get_stylesheet_directory() . '/functions/utilities.php';
require_once get_stylesheet_directory() . '/functions/messaging-functions.php';
require_once get_stylesheet_directory() . '/functions/database-setup.php';


// Include the existing data-processing.php
require_once get_stylesheet_directory() . '/includes/data-processing.php';

/**
 * Add any additional functions that don't fit into the other categories here.
 */

// Example of an additional function:
/**
 * Get the user's most recent weight entry.
 *
 * @param int $user_id The ID of the user.
 * @return string|bool The most recent weight entry or false if not found.
 */
