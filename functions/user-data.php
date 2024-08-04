<?php
/**
 * User Data Functions for Athlete Dashboard
 *
 * This file contains functions related to user-specific data and content generation.
 *
 * @package AthleteDashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Generate content for personal training sessions section.
 */
function athlete_dashboard_personal_training_sessions_content() {
    ?>
    <div class="personal-training-section">
        <h2>PT Scheduler</h2>
        
        <div class="book-session">
            <h3>Book a New Session</h3>
            <form id="book-session-form">
                <div class="form-group">
                    <label for="session-date">Session Date:</label>
                    <input type="date" id="session-date" name="session-date" required>
                </div>
                <div class="form-group">
                    <label for="session-time">Session Time:</label>
                    <input type="time" id="session-time" name="session-time" required>
                </div>
                <div class="form-group">
                    <label for="session-duration">Duration (minutes):</label>
                    <input type="number" id="session-duration" name="session-duration" min="30" max="120" step="15" value="60" required>
                </div>
                <div class="form-group">
                    <label for="session-focus">Session Focus:</label>
                    <textarea id="session-focus" name="session-focus" maxlength="1000" required></textarea>
                </div>
                <button type="submit" class="btn-book">Book Session with Justin Fassio</button>
            </form>
        </div>

        <div class="upcoming-sessions">
            <h3>Upcoming Sessions</h3>
            <div class="sessions-list">
                <!-- Sessions will be dynamically added here -->
            </div>
        </div>
    </div>
    <?php
}

function athlete_dashboard_class_bookings_content() {
    ?>
    <div class="class-bookings-section">
        <h2>Class Bookings</h2>
        
        <div class="available-classes">
            <h3>Available Classes</h3>
            <form id="book-class-form">
                <div class="form-group">
                    <label for="class-day">Day:</label>
                    <select id="class-day" name="class-day" required>
                        <option value="Monday">Monday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Friday">Friday</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="class-time">Time:</label>
                    <select type="time" id="class-time" name="class-time" required>
					<option value="7:00 AM">7:00 AM</option>
					<option value="12:00 PM">12:00 PM</option>
					<option value="4:00 PM">4:00 PM</option>
				   </select>
                </div>
				<div class="form-group">
					<label for="class-name">Class:</label>
					<select id="class-name" name="class-name" required>
						<option value="Online Boot Camp">Online Boot Camp</option>
						<option value="Outdoor Boot Camp">Outdoor Boot Camp</option>
						<option value="Small Group Strength Training">Small Group Strength Training</option>
						<option value="Small Group Cardio">Small Group Cardio</option>
					</select>
				</div>
                <div class="form-group">
                    <label for="class-description">Description:</label>
                    <textarea id="class-description" name="class-description" readonly>High-intensity interval training (HIIT) combining cardio and strength exercises. Suitable for all fitness levels.</textarea>
                </div>
                <div class="form-group">
                    <label for="class-difficulty">Difficulty Level:</label>
                    <input type="text" id="class-difficulty" name="class-difficulty" value="Intermediate" readonly required>
                </div>
                <div class="form-group">
                    <label for="class-instructor">Instructor:</label>
                    <input type="text" id="class-instructor" name="class-instructor" value="Justin Fassio" readonly required>
                </div>
                <button type="submit" class="btn-book">Book Class</button>
            </form>
        </div>

        <div class="booked-classes">
            <h3>Your Booked Classes</h3>
            <div class="classes-list">
                <!-- Booked classes will be dynamically added here -->
            </div>
        </div>
    </div>
    <?php
}

function athlete_dashboard_membership_status_content() {
    ?>
    <div class="membership-status-section">
        <h2>Your Membership</h2>
        
        <div class="current-plan">
            <h3>Current Plan Details</h3>
            <p>You are currently on the <strong>Basic Plan</strong>.</p>
            <ul class="feature-list">
                <li>Access to online classes</li>
                <li>Basic workout tracking</li>
                <li>Limited nutrition guidance</li>
            </ul>
            <p>Renewal Date: <strong>June 1, 2024</strong></p>
        </div>

        <div class="upgrade-options">
            <h3>Upgrade Your Membership</h3>
            <div class="plan-options">
                <div class="plan-card">
                    <h4>Trailhead</h4>
                    <h5>6-Week AI-Personalized Program</h5>
                    <p class="price">$30</p>
                    <ul class="feature-list">
                        <li>AI-Generated 6-Week personalized fitness program</li>
                        <li>Increased personalization w/ expanded fitness profile questionnaire</li>
                        <li>Reviewed and edited by an ACSM Certified Personal Trainer</li>
                    </ul>
                    <stripe-buy-button
                        buy-button-id="buy_btn_1Pj1aLCM7MTfmzuurydsTolo"
                        publishable-key="pk_live_51PUATVCM7MTfmzuuuo5vlWZkYpKW1tVYCIwUeudvdTCvAd4NRnxja5J4r2D1i3ETCFtOIrlQLStY4DQ9inoRHIQY000kNeaCrC"
                    >
                    </stripe-buy-button>
                </div>
                <div class="plan-card">
                    <h4>Trailblazer</h4>
                    <h5>6-Week AI-Enhanced Coach Written Program</h5>
                    <p class="price">$97</p>
                    <ul class="feature-list">
                        <li>Everything in Trailhead</li>
                        <li>Start with a 10-minute zoom call with a Certified Trainer</li>
                        <li>Access to live weekly forums with trainers</li>
                        <li>Certified trainer review and adjustment of AI-generated program</li>
                        <li>Fine-tuned workouts based on your specific profile and goals</li>
                        <li>Weekly progress tracking</li>
                    </ul>
                    <stripe-buy-button
                        buy-button-id="buy_btn_1Pj1b0CM7MTfmzuuko0y7NGl"
                        publishable-key="pk_live_51PUATVCM7MTfmzuuuo5vlWZkYpKW1tVYCIwUeudvdTCvAd4NRnxja5J4r2D1i3ETCFtOIrlQLStY4DQ9inoRHIQY000kNeaCrC"
                    >
                    </stripe-buy-button>
                </div>
                <div class="plan-card">
                    <h4>Seeker</h4>
                    <h5>6-Week AI-Enhanced Coach Written Fitness & Nutrition</h5>
                    <p class="price">$127</p>
                    <ul class="feature-list">
                        <li>Everything in Trailhead and Trailblazer</li>
                        <li>Start with a 20-minute Zoom call with a certified trainer</li>
                        <li>Comprehensive nutrition program, reviewed and adjusted by our certified personal trainers</li>
                        <li>Custom meal plans aligned with your workout regimen</li>
                        <li>Trainer-approved recipes tailored to your fitness goals</li>
                        <li>Weekly grocery lists optimized for your plan</li>
                        <li>Grocery budgeting and meal planning and prep strategies</li>
                    </ul>
                    <stripe-buy-button
                        buy-button-id="buy_btn_1Pj1b0CM7MTfmzuuko0y7NGl"
                        publishable-key="pk_live_51PUATVCM7MTfmzuuuo5vlWZkYpKW1tVYCIwUeudvdTCvAd4NRnxja5J4r2D1i3ETCFtOIrlQLStY4DQ9inoRHIQY000kNeaCrC"
                    >
                    </stripe-buy-button>
                </div>
                <div class="plan-card">
                    <h4>Peakmaster</h4>
                    <h5>6-Week AI-Enhanced Coach Written & Instructed Fitness & Nutrition</h5>
                    <p class="price">$197</p>
                    <ul class="feature-list">
                        <li>Everything in Trailhead, Trailblazer and Seeker</li>
                        <li>Start with a 20-minute Zoom call with a Certified Trainer</li>
                        <li>45 Minute 1-on-1 training session with Certified Personal Trainer</li>
                        <li>Personalized form check and technique optimization</li>
                        <li>Customized tips for maximizing program results</li>
                        <li>Ongoing expert adjustments to your AI-generated plan throughout the 6 weeks</li>
                        <li>Nutrition program progression with weekly menu recommendations and recipes</li>
                    </ul>
                    <stripe-buy-button
                        buy-button-id="buy_btn_1Pj1ZICM7MTfmzuuQ6oLdS2K"
                        publishable-key="pk_live_51PUATVCM7MTfmzuuuo5vlWZkYpKW1tVYCIwUeudvdTCvAd4NRnxja5J4r2D1i3ETCFtOIrlQLStY4DQ9inoRHIQY000kNeaCrC"
                    >
                    </stripe-buy-button>
                </div>
            </div>
        </div>

        <div class="payment-history">
            <h3>Payment History</h3>
            <table id="payment-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Plan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Payment history will be dynamically added here -->
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function athlete_dashboard_check_ins_attendance_content() {
    ?>
    <div class="check-ins-attendance-section">
        <h2>Check-Ins and Activity Log</h2>
        
        <div class="date-range-selector">
            <label for="date-range">Select Date Range:</label>
            <select id="date-range">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
            </select>
        </div>

        <div class="activity-summary">
            <div class="summary-card">
                <h3>Check-Ins</h3>
                <p class="summary-number" id="check-ins-count">0</p>
            </div>
            <div class="summary-card">
                <h3>Classes Attended</h3>
                <p class="summary-number" id="classes-attended-count">0</p>
            </div>
            <div class="summary-card">
                <h3>Workout Logs</h3>
                <p class="summary-number" id="workout-logs-count">0</p>
            </div>
            <div class="summary-card">
                <h3>Meal Logs</h3>
                <p class="summary-number" id="meal-logs-count">0</p>
            </div>
            <div class="summary-card">
                <h3>Progress Entries</h3>
                <p class="summary-number" id="progress-entries-count">0</p>
            </div>
            <div class="summary-card">
                <h3>Exercise Tests</h3>
                <p class="summary-number" id="exercise-tests-count">0</p>
            </div>
        </div>

        <div class="activity-details">
            <h3>Detailed Activity Log</h3>
            <table id="activity-log-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Activity log entries will be dynamically added here -->
                </tbody>
            </table>
        </div>

        <div class="missed-sessions">
            <h3>Missed Sessions</h3>
            <ul id="missed-sessions-list">
                <!-- Missed sessions will be dynamically added here -->
            </ul>
        </div>
    </div>
    <?php
}

function athlete_dashboard_goal_tracking_progress_content() {
    ?>
    <div class="goal-tracking-section">
        <h2>Goal Tracking and Progress</h2>
        
        <div class="set-goal">
            <h3>Set Your Weekly Workout Goal</h3>
            <form id="workout-goal-form">
                <div class="form-group">
                    <label for="weekly-workout-goal">Weekly Workout Goal (1-14):</label>
                    <input type="number" id="weekly-workout-goal" name="weekly-workout-goal" min="1" max="14" required>
                </div>
                <button type="submit" class="btn-set-goal">Set Goal</button>
            </form>
        </div>

        <div class="progress-tracking">
            <h3>Workout Progress</h3>
            <div class="progress-item">
                <h4>Week to Date</h4>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="weekly-progress" style="width: 0%;"></div>
                </div>
                <p><span id="weekly-workouts">0</span> workouts (<span id="weekly-percentage">0</span>% of goal)</p>
            </div>
            <div class="progress-item">
                <h4>Month to Date</h4>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="monthly-progress" style="width: 0%;"></div>
                </div>
                <p><span id="monthly-workouts">0</span> workouts (<span id="monthly-percentage">0</span>% of goal)</p>
            </div>
            <div class="progress-item">
                <h4>Year to Date</h4>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="yearly-progress" style="width: 0%;"></div>
                </div>
                <p><span id="yearly-workouts">0</span> workouts (<span id="yearly-percentage">0</span>% of goal)</p>
            </div>
        </div>

        <div class="personal-bests">
            <h3>Personal Bests and Records</h3>
            <p>This section will display your personal best achievements.</p>
            <!-- Placeholder for personal bests, to be implemented later -->
        </div>
    </div>
    <?php
}

function athlete_dashboard_personalized_recommendations_content() {
    ?>
    <div class="personalized-recommendations-section">
        <h2>Personalized Recommendations</h2>
        
        <div class="recommendations-container">
            <div class="recommendation-card suggested-classes">
                <h3>Suggested Classes</h3>
                <ul class="recommendation-list" id="suggested-classes-list">
                    <li>High-Intensity Interval Training (HIIT) - Monday 6:00 PM</li>
                    <li>Yoga for Athletes - Wednesday 7:30 PM</li>
                    <li>Strength Training Fundamentals - Friday 5:30 PM</li>
                </ul>
                <button class="btn-view-more" id="view-more-classes">View More Classes</button>
            </div>

            <div class="recommendation-card nutrition-tips">
                <h3>Nutrition and Wellness Tips</h3>
                <ul class="recommendation-list" id="nutrition-tips-list">
                    <li>Increase your protein intake to support muscle recovery</li>
                    <li>Try incorporating more leafy greens for improved endurance</li>
                    <li>Stay hydrated! Aim for 8 glasses of water daily</li>
                </ul>
                <button class="btn-view-more" id="view-more-tips">View More Tips</button>
            </div>

            <div class="recommendation-card exclusive-offers">
                <h3>Exclusive Offers</h3>
                <ul class="recommendation-list" id="exclusive-offers-list">
                    <li>20% off your next personal training session</li>
                    <li>Buy one month, get one week free on any membership upgrade</li>
                    <li>Refer a friend and both get a free nutrition consultation</li>
                </ul>
                <button class="btn-view-more" id="view-more-offers">View More Offers</button>
            </div>
        </div>
    </div>
    <?php
}