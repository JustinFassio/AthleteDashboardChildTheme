<?php
/**
 * Template Name: Athlete Dashboard
 *
 * @package AthleteDashboard
 */

get_header();

if ( is_user_logged_in() ) :
    $current_user = wp_get_current_user();
    ?>
    <div class="athlete-dashboard-container">
        <?php athlete_dashboard_render_welcome_banner( $current_user ); ?>
        <div class="athlete-dashboard">
            <?php echo athlete_dashboard_render_all_sections(); ?>
        </div>
    </div>
    <?php
else :
    athlete_dashboard_render_login_message();
endif;

get_footer();

/**
 * Render the welcome banner.
 *
 * @param WP_User $current_user The current user object.
 */
function athlete_dashboard_render_welcome_banner( $current_user ) {
    ?>
    <div class="welcome-banner" id="welcomeBanner">
        <div class="welcome-content">
            <span class="user-icon" aria-hidden="true">&#128100;</span>
            <span class="welcome-message">
                <?php
                printf(
                    /* translators: %s: user display name */
                    esc_html__( 'Welcome back, %s', 'athlete-dashboard' ),
                    '<strong>' . esc_html( $current_user->display_name ) . '</strong>'
                );
                ?>
            </span>
        </div>
        <button class="welcome-toggle" aria-label="<?php esc_attr_e( 'Toggle welcome message', 'athlete-dashboard' ); ?>">
            <span class="toggle-icon" aria-hidden="true">&#9650;</span>
        </button>
    </div>
    <?php
}

/**
 * Render a dashboard section.
 *
 * @param string $id Section ID.
 * @param string $title Section title.
 * @param string $content_callback Content callback function or shortcode.
 * @param string $width Section width class.
 */
function athlete_dashboard_render_section($id, $title, $content_callback, $width) {
    ?>
    <div id="<?php echo esc_attr($id); ?>" class="dashboard-section <?php echo esc_attr($width); ?>">
        <h2>
            <?php echo esc_html($title); ?>
            <button class="toggle-btn" aria-expanded="false" aria-controls="<?php echo esc_attr($id); ?>-content">
                <span class="screen-reader-text"><?php esc_html_e('Toggle section', 'athlete-dashboard'); ?></span>
                <span aria-hidden="true">+</span>
            </button>
        </h2>
        <div id="<?php echo esc_attr($id); ?>-content" class="section-content" aria-hidden="true" style="display: none;">
            <?php
            if (strpos($content_callback, '[') === 0) {
                echo do_shortcode($content_callback);
            } elseif (function_exists($content_callback)) {
                call_user_func($content_callback);
            } else {
                esc_html_e('Content callback not found.', 'athlete-dashboard');
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Render login message for non-logged-in users.
 */
function athlete_dashboard_render_login_message() {
    ?>
    <p>
        <?php
        printf(
            /* translators: %s: login URL */
            wp_kses(
                __( 'Please <a href="%s">log in</a> to view your dashboard.', 'athlete-dashboard' ),
                array(
                    'a' => array(
                        'href' => array(),
                    ),
                )
            ),
            esc_url( wp_login_url( get_permalink() ) )
        );
        ?>
    </p>
    <?php
}

/**
 * Generate account details content.
 */
function athlete_dashboard_account_details_content() {
    $current_user = wp_get_current_user();
    ?>
    <form id="account-details-form" class="custom-form">
        <div class="user-profile">
            <div class="profile-picture">
                <?php echo get_avatar( $current_user->ID, 150 ); ?>
                <input type="file" id="profile-picture-upload" name="profile_picture" accept="image/*" style="display: none;">
            </div>
            <div class="profile-info">
                <p><strong><?php esc_html_e( 'Athlete Name:', 'athlete-dashboard' ); ?></strong> <span id="display-name-text"><?php echo esc_html( $current_user->display_name ); ?></span></p>
                <p><strong><?php esc_html_e( 'Email:', 'athlete-dashboard' ); ?></strong> <span id="email-text"><?php echo esc_html( $current_user->user_email ); ?></span></p>
                <p><strong><?php esc_html_e( 'Athlete Profile:', 'athlete-dashboard' ); ?></strong> <span id="bio-text"><?php echo wp_kses_post( $current_user->description ); ?></span></p>
            </div>
            <div class="edit-profile-fields" style="display: none;">
                <input type="text" name="display_name" id="edit-display-name" value="<?php echo esc_attr( $current_user->display_name ); ?>" placeholder="<?php esc_attr_e( 'Display Name', 'athlete-dashboard' ); ?>">
                <input type="email" name="email" id="edit-email" value="<?php echo esc_attr( $current_user->user_email ); ?>" placeholder="<?php esc_attr_e( 'Email', 'athlete-dashboard' ); ?>">
                <textarea name="bio" id="edit-bio" placeholder="<?php esc_attr_e( 'Bio', 'athlete-dashboard' ); ?>"><?php echo esc_textarea( $current_user->description ); ?></textarea>
            </div>
        </div>
        <div class="profile-actions">
            <button id="change-avatar" class="custom-button"><?php esc_html_e( 'Change Image', 'athlete-dashboard' ); ?></button>
            <button type="button" id="edit-profile" class="custom-button"><?php esc_html_e( 'Edit Profile', 'athlete-dashboard' ); ?></button>
            <button type="submit" id="save-profile" class="custom-button" style="display: none;"><?php esc_html_e( 'Save Profile', 'athlete-dashboard' ); ?></button>
            <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="custom-button logout-button"><?php esc_html_e( 'Logout', 'athlete-dashboard' ); ?></a>
        </div>
        <?php wp_nonce_field( 'athlete_dashboard_account_nonce', 'account_nonce' ); ?>
    </form>
    <?php
}

/**
 * Render a progress section.
 *
 * @param string $title The title of the progress section.
 * @param string $chart_id The ID for the chart canvas.
 * @param string $form_id The ID for the progress form.
 * @param string $nonce_name The name for the nonce field.
 * @param string $weight_field_name The name of the weight input field.
 * @param string $weight_unit_field_name The name of the weight unit select field.
 */
function athlete_dashboard_render_progress_section($title, $chart_id, $form_id, $nonce_name, $weight_field_name, $weight_unit_field_name) {
    ?>
    <div class="progress-section">
        <div class="progress-cards">
            <div class="progress-card">
                <h3><?php echo esc_html($title . ' ' . __('Progress', 'athlete-dashboard')); ?></h3>
                <div class="progress-chart-container">
                    <canvas id="<?php echo esc_attr($chart_id); ?>"></canvas>
                </div>
            </div>
            <div class="progress-card">
                <h3><?php esc_html_e('Add New Entry', 'athlete-dashboard'); ?></h3>
                <form id="<?php echo esc_attr($form_id); ?>" class="progress-input-form custom-form">
                    <div class="form-group">
                        <label for="<?php echo esc_attr($weight_field_name); ?>"><?php echo esc_html($title); ?> <?php esc_html_e('Weight:', 'athlete-dashboard'); ?></label>
                        <div class="weight-input-group">
                            <input type="number" id="<?php echo esc_attr($weight_field_name); ?>" name="<?php echo esc_attr($weight_field_name); ?>" required step="0.1">
                            <select id="<?php echo esc_attr($weight_unit_field_name); ?>" name="<?php echo esc_attr($weight_unit_field_name); ?>">
                                <option value="kg"><?php esc_html_e('kg', 'athlete-dashboard'); ?></option>
                                <option value="lbs"><?php esc_html_e('lbs', 'athlete-dashboard'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="<?php echo esc_attr($form_id); ?>_date"><?php esc_html_e('Date:', 'athlete-dashboard'); ?></label>
                        <input type="date" id="<?php echo esc_attr($form_id); ?>_date" name="date" required>
                    </div>
                    <button type="submit" class="custom-button"><?php esc_html_e('Add Progress', 'athlete-dashboard'); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php wp_nonce_field('athlete_dashboard_nonce', $nonce_name); ?>
    <?php
}

/**
 * Generate body weight progress content.
 */
function athlete_dashboard_body_weight_progress_content() {
    echo do_shortcode('[user_progress]');
    athlete_dashboard_render_progress_section(
        __('Body Weight', 'athlete-dashboard'),
        'bodyWeightProgressChart',
        'body-weight-progress-form',
        'body_weight_progress_nonce',
        'weight',
        'weight_unit'
    );
}

/**
 * Generate squat progress content.
 */
function athlete_dashboard_squat_progress_content() {
    echo do_shortcode('[user_squat_progress]');
    athlete_dashboard_render_progress_section(
        __('Squat', 'athlete-dashboard'),
        'squatProgressChart',
        'squat-progress-form',
        'squat_progress_nonce',
        'squat_weight',
        'squat_weight_unit'
    );
}

/**
 * Generate bench press progress content.
 */
function athlete_dashboard_bench_press_progress_content() {
    echo do_shortcode('[user_bench_press_progress]');
    athlete_dashboard_render_progress_section(
        __('Bench Press', 'athlete-dashboard'),
        'benchPressProgressChart',
        'bench-press-progress-form',
        'bench_press_progress_nonce',
        'bench_press_weight',
        'bench_press_weight_unit'
    );
}

/**
 * Generate deadlift progress content.
 */
function athlete_dashboard_deadlift_progress_content() {
    echo do_shortcode('[user_deadlift_progress]');
    athlete_dashboard_render_progress_section(
        __('Deadlift', 'athlete-dashboard'),
        'deadliftProgressChart',
        'deadlift-progress-form',
        'deadlift_progress_nonce',
        'deadlift_weight',
        'deadlift_weight_unit'
    );
}

/**
 * Generate comprehensive body composition content.
 */
function athlete_dashboard_comprehensive_body_composition_content() {
    ?>
    <div class="chart-container">
        <canvas id="comprehensiveBodyCompositionChart"></canvas>
    </div>
    <div class="data-input-container">
        <div class="body-composition-calculator">
            <h3><?php esc_html_e('Body Composition Calculator', 'athlete-dashboard'); ?></h3>
            <div class="calculator-inputs">
                <div class="form-group">
                    <label><?php esc_html_e('Gender:', 'athlete-dashboard'); ?></label>
                    <div>
                        <input type="radio" id="calc-gender-male" name="calc-gender" value="male" required>
                        <label for="calc-gender-male"><?php esc_html_e('Male', 'athlete-dashboard'); ?></label>
                        <input type="radio" id="calc-gender-female" name="calc-gender" value="female" required>
                        <label for="calc-gender-female"><?php esc_html_e('Female', 'athlete-dashboard'); ?></label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="calc-age"><?php esc_html_e('Age:', 'athlete-dashboard'); ?></label>
                    <input type="number" id="calc-age" name="calc-age" min="18" max="100" required>
                </div>
                <div class="form-group">
                    <label for="calc-height"><?php esc_html_e('Height:', 'athlete-dashboard'); ?></label>
                    <input type="number" id="calc-height" name="calc-height" step="0.01" required>
                    <select id="calc-height-unit">
                        <option value="cm">cm</option>
                        <option value="in">in</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="calc-weight"><?php esc_html_e('Weight:', 'athlete-dashboard'); ?></label>
                    <input type="number" id="calc-weight" name="calc-weight" step="0.01" required>
                    <select id="calc-weight-unit">
                        <option value="kg">kg</option>
                        <option value="lbs">lbs</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="calc-neck"><?php esc_html_e('Neck Circumference:', 'athlete-dashboard'); ?></label>
                    <input type="number" id="calc-neck" name="calc-neck" step="0.01" required>
                    <select id="calc-neck-unit">
                        <option value="cm">cm</option>
                        <option value="in">in</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="calc-waist"><?php esc_html_e('Waist Circumference:', 'athlete-dashboard'); ?></label>
                    <input type="number" id="calc-waist" name="calc-waist" step="0.01" required>
                    <select id="calc-waist-unit">
                        <option value="cm">cm</option>
                        <option value="in">in</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="calc-hips"><?php esc_html_e('Hip Circumference:', 'athlete-dashboard'); ?></label>
                    <input type="number" id="calc-hips" name="calc-hips" step="0.01" required>
                    <select id="calc-hips-unit">
                        <option value="cm">cm</option>
                        <option value="in">in</option>
                    </select>
                </div>
                <button type="button" id="calculate-composition" class="custom-button"><?php esc_html_e('Calculate', 'athlete-dashboard'); ?></button>
            </div>
        </div>
        <form id="comprehensive-body-composition-form" class="body-composition-form">
            <h3><?php esc_html_e('Log Body Composition', 'athlete-dashboard'); ?></h3>
            <div class="form-group">
                <label for="comprehensive-weight"><?php esc_html_e('Weight (kg):', 'athlete-dashboard'); ?></label>
                <input type="number" id="comprehensive-weight" name="weight" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="comprehensive-body-fat"><?php esc_html_e('Body Fat (%):', 'athlete-dashboard'); ?></label>
                <input type="number" id="comprehensive-body-fat" name="body_fat_percentage" step="0.01" min="0" max="100">
            </div>
            <div class="form-group">
                <label for="comprehensive-lean-mass"><?php esc_html_e('Lean Body Mass (kg):', 'athlete-dashboard'); ?></label>
                <input type="number" id="comprehensive-lean-mass" name="lean_mass" step="0.01">
            </div>
            <div class="form-group">
                <label for="comprehensive-bmi"><?php esc_html_e('BMI:', 'athlete-dashboard'); ?></label>
                <input type="number" id="comprehensive-bmi" name="bmi" step="0.01">
            </div>
            <div class="form-group">
                <label for="comprehensive-measurement-date"><?php esc_html_e('Date:', 'athlete-dashboard'); ?></label>
                <input type="date" id="comprehensive-measurement-date" name="date" required>
            </div>
            <button type="submit" class="custom-button"><?php esc_html_e('Log Body Composition', 'athlete-dashboard'); ?></button>
        </form>
    </div>
    <?php wp_nonce_field( 'athlete_dashboard_nonce', 'comprehensive_progress_nonce' ); ?>
    <?php
}

/**
 * Generate exercise progress content.
 */
function athlete_dashboard_exercise_progress_content() {
    ?>
    <div id="exercise-tabs">
        <ul>
            <?php foreach ( athlete_dashboard_get_exercise_tests() as $key => $test ) : ?>
                <li><a href="#<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $test['label'] ); ?></a></li>
            <?php endforeach; ?>
        </ul>
        <?php foreach ( athlete_dashboard_get_exercise_tests() as $key => $test ) : ?>
            <div id="<?php echo esc_attr( $key ); ?>" data-bilateral="<?php echo esc_attr( $test['bilateral'] ? 'true' : 'false' ); ?>">
                <div class="exercise-content-wrapper">
                    <div class="progress-chart-container">
                        <canvas id="<?php echo esc_attr( $key ); ?>-chart"></canvas>
                    </div>
                    <div class="exercise-form-container">
                        <form class="exercise-progress-form custom-form">
                            <input type="hidden" name="exercise_key" value="<?php echo esc_attr( $key ); ?>">
                            <?php if ( $test['bilateral'] ) : ?>
                                <div class="form-group">
                                    <label for="<?php echo esc_attr( $key ); ?>-left"><?php echo esc_html( $test['label'] ); ?> (Left) (<?php echo esc_html( $test['unit'] ); ?>):</label>
                                    <input type="number" id="<?php echo esc_attr( $key ); ?>-left" name="left_value" required step="<?php echo esc_attr( 1 / pow( 10, $test['decimal_places'] ) ); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="<?php echo esc_attr( $key ); ?>-right"><?php echo esc_html( $test['label'] ); ?> (Right) (<?php echo esc_html( $test['unit'] ); ?>):</label>
                                    <input type="number" id="<?php echo esc_attr( $key ); ?>-right" name="right_value" required step="<?php echo esc_attr( 1 / pow( 10, $test['decimal_places'] ) ); ?>">
                                </div>
                            <?php else : ?>
                                <div class="form-group">
                                    <label for="<?php echo esc_attr( $key ); ?>-value"><?php echo esc_html( $test['label'] ); ?> (<?php echo esc_html( $test['unit'] ); ?>):</label>
                                    <input type="number" id="<?php echo esc_attr( $key ); ?>-value" name="value" required step="<?php echo esc_attr( 1 / pow( 10, $test['decimal_places'] ) ); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="<?php echo esc_attr( $key ); ?>-date"><?php esc_html_e( 'Date:', 'athlete-dashboard' ); ?></label>
                                <input type="date" id="<?php echo esc_attr( $key ); ?>-date" name="date" required>
                            </div>
                            <button type="submit" class="custom-button"><?php esc_html_e( 'Add Progress', 'athlete-dashboard' ); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php wp_nonce_field( 'athlete_dashboard_nonce', 'exercise_progress_nonce' ); ?>
    <?php
}
/**
 * Generate meal log content.
 */
function athlete_dashboard_meal_log_content() {
    ?>
    <div class="meal-log-container">
        <div class="meal-log-form">
            <form id="meal-log-form" class="custom-form">
            <div class="form-group">
                <label for="meal_date"><?php esc_html_e('Date:', 'athlete-dashboard'); ?></label>
                <input type="date" id="meal_date" name="meal_date" required>
                <p class="field-subtext"><?php esc_html_e('', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="meal_time"><?php esc_html_e('Time:', 'athlete-dashboard'); ?></label>
                <input type="time" id="meal_time" name="meal_time" required>
                <p class="field-subtext"><?php esc_html_e('', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="meal_type"><?php esc_html_e('Meal Type:', 'athlete-dashboard'); ?></label>
                <select id="meal_type" name="meal_type" required>
					<option value="breakfast"><?php esc_html_e('Breakfast', 'athlete-dashboard'); ?></option>
					<option value="mid_morning_snack"><?php esc_html_e('Mid-Morning Snack', 'athlete-dashboard'); ?></option>
					<option value="brunch"><?php esc_html_e('Brunch', 'athlete-dashboard'); ?></option>
					<option value="lunch"><?php esc_html_e('Lunch', 'athlete-dashboard'); ?></option>
					<option value="mid_afternoon_snack"><?php esc_html_e('Mid-Afternoon Snack', 'athlete-dashboard'); ?></option>
					<option value="dinner"><?php esc_html_e('Dinner', 'athlete-dashboard'); ?></option>
					<option value="snack"><?php esc_html_e('Snack', 'athlete-dashboard'); ?></option>
					<option value="supper"><?php esc_html_e('Supper', 'athlete-dashboard'); ?></option>
					<option value="late_night_snack"><?php esc_html_e('Late-Night Snack', 'athlete-dashboard'); ?></option>
					<option value="happy_hour"><?php esc_html_e('Happy Hour', 'athlete-dashboard'); ?></option>
					<option value="afternoon_tea"><?php esc_html_e('Afternoon Tea', 'athlete-dashboard'); ?></option>
					<option value="pre_workout_meal"><?php esc_html_e('Pre-Workout Meal', 'athlete-dashboard'); ?></option>
					<option value="post_workout_meal"><?php esc_html_e('Post-Workout Meal', 'athlete-dashboard'); ?></option>
				</select>
                <p class="field-subtext"><?php esc_html_e('', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="meal_name"><?php esc_html_e('Meal Name:', 'athlete-dashboard'); ?></label>
                <input type="text" id="meal_name" name="meal_name" required>
                <p class="field-subtext"><?php esc_html_e('What the meal is called on the menu or at home, i.e. Spaghetti with Meatballs.', 'athlete-dashboard'); ?></p>
            </div>
            
            <!-- Protein -->
            <div class="form-group">
                <label for="protein_type"><?php esc_html_e('Protein Type(s):', 'athlete-dashboard'); ?></label>
                <input type="text" id="protein_type" name="protein_type">
                <p class="field-subtext"><?php esc_html_e('Specify the type or types of protein in your meal, e.g., chicken, shrimp, and tofu.', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="protein_quantity"><?php esc_html_e('Protein Quantity:', 'athlete-dashboard'); ?></label>
                <input type="number" id="protein_quantity" name="protein_quantity" step="0.1">
                <select id="protein_unit" name="protein_unit">
                    <option value="g">g</option>
                    <option value="oz">oz</option>
                    <option value="pieces">pieces</option>
                </select>
                <p class="field-subtext"><?php esc_html_e('Enter the combined amount of protein and select the unit of measurement.', 'athlete-dashboard'); ?></p>
            </div>

            <!-- Fat -->
            <div class="form-group">
                <label for="fat_type"><?php esc_html_e('Fat Type(s):', 'athlete-dashboard'); ?></label>
                <input type="text" id="fat_type" name="fat_type">
                <p class="field-subtext"><?php esc_html_e('Indicate the type of fat(s), e.g., olive oil, avocado.', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="fat_quantity"><?php esc_html_e('Fat Quantity:', 'athlete-dashboard'); ?></label>
                <input type="number" id="fat_quantity" name="fat_quantity" step="0.1">
                <select id="fat_unit" name="fat_unit">
                    <option value="g">g</option>
                    <option value="tsp">tsp</option>
                    <option value="tbsp">tbsp</option>
                </select>
                <p class="field-subtext"><?php esc_html_e('Specify the combined amount of fat and choose the unit of measurement.', 'athlete-dashboard'); ?></p>
            </div>

            <!-- Carbohydrates: Starches & Grains -->
            <div class="form-group">
                <label for="carb_starch_type"><?php esc_html_e('Starch/Grain Type(s):', 'athlete-dashboard'); ?></label>
                <input type="text" id="carb_starch_type" name="carb_starch_type">
                <p class="field-subtext"><?php esc_html_e('Enter the type of starch or grain, e.g., rice, bread.', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="carb_starch_quantity"><?php esc_html_e('Starch/Grain Quantity:', 'athlete-dashboard'); ?></label>
                <input type="number" id="carb_starch_quantity" name="carb_starch_quantity" step="0.1">
                <select id="carb_starch_unit" name="carb_starch_unit">
                    <option value="g">g</option>
                    <option value="oz">oz</option>
                    <option value="cups">cups</option>
                    <option value="slices">slices</option>
                </select>
                <p class="field-subtext"><?php esc_html_e('Indicate the combined amount of starch/grain and select the unit.', 'athlete-dashboard'); ?></p>
            </div>

            <!-- Carbohydrates: Fruits -->
            <div class="form-group">
                <label for="carb_fruit_type"><?php esc_html_e('Fruit Type(s):', 'athlete-dashboard'); ?></label>
                <input type="text" id="carb_fruit_type" name="carb_fruit_type">
                <p class="field-subtext"><?php esc_html_e('Specify the type of fruit consumed, e.g., apple, banana.', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="carb_fruit_quantity"><?php esc_html_e('Fruit Quantity:', 'athlete-dashboard'); ?></label>
                <input type="number" id="carb_fruit_quantity" name="carb_fruit_quantity" step="0.1">
                <select id="carb_fruit_unit" name="carb_fruit_unit">
                    <option value="pieces">pieces</option>
                    <option value="g">g</option>
                    <option value="oz">oz</option>
                    <option value="cups">cups</option>
                </select>
                <p class="field-subtext"><?php esc_html_e('Enter the combined amount of fruit and choose the unit of measurement.', 'athlete-dashboard'); ?></p>
            </div>

            <!-- Carbohydrates: Vegetables -->
            <div class="form-group">
                <label for="carb_vegetable_type"><?php esc_html_e('Vegetable Type(s):', 'athlete-dashboard'); ?></label>
                <input type="text" id="carb_vegetable_type" name="carb_vegetable_type">
                <p class="field-subtext"><?php esc_html_e('Enter the type of vegetable, e.g., broccoli, carrots.', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="carb_vegetable_quantity"><?php esc_html_e('Vegetable Quantity:', 'athlete-dashboard'); ?></label>
                <input type="number" id="carb_vegetable_quantity" name="carb_vegetable_quantity" step="0.1">
                <select id="carb_vegetable_unit" name="carb_vegetable_unit">
                    <option value="g">g</option>
                    <option value="oz">oz</option>
                    <option value="cups">cups</option>
                </select>
                <p class="field-subtext"><?php esc_html_e('Specify the combined amount of vegetables and select the unit.', 'athlete-dashboard'); ?></p>
            </div>

            <div class="form-group">
                <label for="estimated_calories"><?php esc_html_e('Estimated Calories:', 'athlete-dashboard'); ?></label>
                <input type="number" id="estimated_calories" name="estimated_calories" required>
                <p class="field-subtext"><?php esc_html_e('Enter your best estimate of the total calories for this meal.', 'athlete-dashboard'); ?></p>
            </div>
            <div class="form-group">
                <label for="meal_description"><?php esc_html_e('Meal Diary:', 'athlete-dashboard'); ?></label>
                <textarea id="meal_description" name="meal_description" rows="4"></textarea>
                <p class="field-subtext"><?php esc_html_e('Add details about how you felt before and after your meal and your thoughts on the meal.', 'athlete-dashboard'); ?></p>
            </div>
            <button type="submit" class="custom-button"><?php esc_html_e('Log Meal', 'athlete-dashboard'); ?></button>
            </form>
        </div>
		
        <div class="meal-list-scrollable">
            <div id="recent-meals">
                <h3><?php esc_html_e('Recent Meals', 'athlete-dashboard'); ?></h3>
                <!-- The meal list will be populated by JavaScript -->
            </div>
        </div>
    </div>
    <?php wp_nonce_field('athlete_dashboard_nonce', 'meal_log_nonce'); ?>
    <?php
}

/**
 * Generate workout log content.
 */
function athlete_dashboard_log_workout_content() {
    ?>
    <div class="workout-log-container">
        <form id="workout-log-form" class="custom-form">
            <div class="form-group">
                <label for="workout_date"><?php esc_html_e('Workout Date:', 'athlete-dashboard'); ?></label>
                <input type="date" id="workout_date" name="workout_date" required>
            </div>
            <div class="form-group">
                <label for="workout_duration"><?php esc_html_e('Duration (minutes):', 'athlete-dashboard'); ?></label>
                <input type="number" id="workout_duration" name="workout_duration" required min="1">
            </div>
            <div class="form-group">
                <label for="workout_type"><?php esc_html_e('Workout Type:', 'athlete-dashboard'); ?></label>
                <input type="text" id="workout_type" name="workout_type" required>
            </div>
            <div class="form-group">
                <label for="workout_intensity"><?php esc_html_e('Intensity (1-10):', 'athlete-dashboard'); ?></label>
                <input type="number" id="workout_intensity" name="workout_intensity" required min="1" max="10">
            </div>
            <div class="form-group">
                <label for="workout_notes"><?php esc_html_e('Notes:', 'athlete-dashboard'); ?></label>
                <textarea id="workout_notes" name="notes" rows="4"></textarea>
            </div>
            <button type="submit" class="custom-button"><?php esc_html_e('Log Workout', 'athlete-dashboard'); ?></button>
        </form>
        <div id="recent-workouts">
            <h3><?php esc_html_e('Recent Workouts', 'athlete-dashboard'); ?></h3>
            <?php echo do_shortcode('[user_logged_workouts]'); ?>
        </div>
    </div>
    <?php wp_nonce_field('athlete_dashboard_nonce', 'workout_log_nonce'); ?>
    <?php
}
