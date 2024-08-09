jQuery(document).ready(function($) {
    'use strict';

    // Configuration object
    const CONFIG = {
        updateInterval: 300000,
        ajaxUrl: athleteDashboard.ajax_url,
        nonce: athleteDashboard.nonce,
        exerciseTests: athleteDashboard.exerciseTests
    };

// AthleteUI module
const AthleteUI = (function() {
    function initializeExerciseTabs() {
        try {
            $('#exercise-tabs').tabs({
                create: function(event, ui) {
                    const firstExerciseKey = Object.keys(CONFIG.exerciseTests)[0];
                    AthleteCharts.initializeExerciseChart(firstExerciseKey);
                    AthleteCharts.updateExerciseChart(firstExerciseKey);
                },
                activate: function(event, ui) {
                    const exerciseKey = ui.newPanel.attr('id');
                    if (!AthleteCharts.chartExists(exerciseKey)) {
                        AthleteCharts.initializeExerciseChart(exerciseKey);
                    }
                    AthleteCharts.updateExerciseChart(exerciseKey);
                }
            });
        } catch (error) {
            console.error('Error initializing exercise tabs:', error);
        }
    }

    function initializeGroupToggles() {
        $('.dashboard-group').each(function() {
            const $group = $(this);
            const $header = $group.find('.group-header');
            const $content = $group.find('.group-content');
            const $toggleBtn = $group.find('.toggle-group');

            $header.on('click', function() {
                toggleGroup($group, $content, $toggleBtn);
            });

            $toggleBtn.on('click', function(e) {
                e.stopPropagation(); // Prevent the header click event from firing
                toggleGroup($group, $content, $toggleBtn);
            });

            // Restore group states on page load
            const groupName = $group.data('group-name');
            const savedState = localStorage.getItem('groupState_' + groupName);

            if (savedState === 'collapsed') {
                collapseGroup($group, $content, $toggleBtn);
            }
        });
    }

    function toggleGroup($group, $content, $toggleBtn) {
        const isExpanded = $toggleBtn.attr('aria-expanded') === 'true';

        if (isExpanded) {
            collapseGroup($group, $content, $toggleBtn);
        } else {
            expandGroup($group, $content, $toggleBtn);
        }

        // Save the state to localStorage
        const groupName = $group.data('group-name');
        localStorage.setItem('groupState_' + groupName, isExpanded ? 'collapsed' : 'expanded');
    }

    function collapseGroup($group, $content, $toggleBtn) {
        $group.addClass('collapsed');
        $toggleBtn.attr('aria-expanded', 'false');
        $content.slideUp(300);
        $toggleBtn.find('.fa-chevron-up').hide();
        $toggleBtn.find('.fa-chevron-down').show();
    }

    function expandGroup($group, $content, $toggleBtn) {
        $group.removeClass('collapsed');
        $toggleBtn.attr('aria-expanded', 'true');
        $content.slideDown(300);
        $toggleBtn.find('.fa-chevron-up').show();
        $toggleBtn.find('.fa-chevron-down').hide();
    }

    function restoreSectionStates() {
        $('.dashboard-section').each(function() {
            const sectionId = $(this).attr('id');
            const isExpanded = localStorage.getItem(sectionId) === 'expanded';
            if (isExpanded) {
                openSection($(this), false);
            } else {
                closeSection($(this), false);
            }
        });
    }

    function toggleSection(button) {
        const $button = $(button);
        const $section = $button.closest('.dashboard-section');
        const isExpanded = $button.attr('aria-expanded') === 'true';

        // Close all other sections
        $('.dashboard-section').not($section).each(function() {
            closeSection($(this), true);
        });

        if (isExpanded) {
            closeSection($section, true);
        } else {
            openSection($section, true);
        }
    }

    function openSection($section, shouldAnimate) {
        const $button = $section.find('.toggle-btn');
        const $content = $section.find('.section-content');
        const sectionId = $section.attr('id');

        $button.attr('aria-expanded', 'true');
        $button.find('span[aria-hidden]').text('-');
        
        if (shouldAnimate) {
            $content.slideDown(300, function() {
                $content.attr('aria-hidden', 'false');
                makeSticky($section);
            });
        } else {
            $content.show().attr('aria-hidden', 'false');
            makeSticky($section);
        }

        $section.addClass('active');
        localStorage.setItem(sectionId, 'expanded');
    }

    function closeSection($section, shouldAnimate) {
        const $button = $section.find('.toggle-btn');
        const $content = $section.find('.section-content');
        const sectionId = $section.attr('id');

        $button.attr('aria-expanded', 'false');
        $button.find('span[aria-hidden]').text('+');
        
        if (shouldAnimate) {
            $content.slideUp(300, function() {
                $content.attr('aria-hidden', 'true');
                removeSticky($section);
            });
        } else {
            $content.hide().attr('aria-hidden', 'true');
            removeSticky($section);
        }

        $section.removeClass('active');
        localStorage.setItem(sectionId, 'collapsed');
    }

    function makeSticky($section) {
        const $button = $section.find('.toggle-btn');
        const buttonOffset = $button.offset().top;
        
        $('html, body').animate({
            scrollTop: buttonOffset - 20  // 20px padding above the button
        }, 300);

        $section.css({
            position: 'sticky',
            top: '20px',  // Adjust this value as needed
            zIndex: 100
        });
    }

    function removeSticky($section) {
        $section.css({
            position: '',
            top: '',
            zIndex: ''
        });
    }

    return {
        initializeExerciseTabs,
        initializeGroupToggles,
        restoreSectionStates,
        toggleSection
    };
})();
	
	// AthleteCharts module
	const AthleteCharts = (function() {
		let bodyWeightChart, squatProgressChart, benchPressProgressChart, deadliftProgressChart, bodyCompositionChart;
		const exerciseCharts = {};

		function chartExists(exerciseKey) {
			return !!exerciseCharts[exerciseKey];
		}

		// New function for creating high-resolution canvas
		function createHighResolutionCanvas(canvas) {
			const dpr = window.devicePixelRatio || 1;
			const rect = canvas.getBoundingClientRect();

			canvas.width = rect.width * dpr;
			canvas.height = rect.height * dpr;
			canvas.style.width = `${rect.width}px`;
			canvas.style.height = `${rect.height}px`;

			const ctx = canvas.getContext('2d');
			ctx.scale(dpr, dpr);

			return ctx;
		}

		// Add this function to the AthleteCharts module
		function setChartSize(canvas) {
			const container = canvas.parentElement;
			canvas.style.width = '100%';
			canvas.style.height = `${container.offsetWidth * 0.5}px`; // 2:1 aspect ratio
		}
		
		// Update or add this function in the AthleteCharts module
		function handleResize() {
			[bodyWeightChart, squatProgressChart, benchPressProgressChart, deadliftProgressChart].forEach(chart => {
				if (chart) {
					setChartSize(chart.canvas);
					chart.resize();
				}
			});
		}
		
		// Updated initializeProgressChart function
		function initializeProgressChart(canvasId, label, borderColor) {
			const canvas = document.getElementById(canvasId);
			if (!canvas) {
				console.warn(`${label} Progress Chart canvas not found. Skipping initialization.`);
				return null;
			}
			const context = createHighResolutionCanvas(canvas);
			return new Chart(context, {
				type: 'line',
				data: {
					datasets: [{
						label: label,
						borderColor: borderColor,
						tension: 0.1,
						pointHoverRadius: 8,
						pointHoverBackgroundColor: borderColor
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					devicePixelRatio: window.devicePixelRatio || 1,
					scales: {
						x: {
							type: 'time',
							time: {
								unit: 'day'
							},
							ticks: {
								autoSkip: true,
								maxTicksLimit: 10,
								font: {
									size: 12,
									family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
								}
							}
						},
						y: {
							beginAtZero: false,
							ticks: {
								font: {
									size: 12,
									family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
								}
							}
						}
					},
					interaction: {
						mode: 'nearest',
						axis: 'x',
						intersect: false
					},
					plugins: {
						tooltip: {
							mode: 'index',
							intersect: false,
							bodyFont: {
								size: 14,
								family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
							},
							titleFont: {
								size: 16,
								family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif"
							},
							callbacks: {
								label: function(context) {
									let label = context.dataset.label || '';
									if (label) {
										label += ': ';
									}
									if (context.parsed.y !== null) {
										label += context.parsed.y.toFixed(1) + ' ' + (context.dataset.unit || '');
									}
									return label;
								},
								title: function(tooltipItems) {
									return new Date(tooltipItems[0].parsed.x).toLocaleDateString();
								}
							}
						},
						legend: {
							display: false
						}
					}
				}
			});
		}

		function initializeBodyWeightProgressChart() {
			bodyWeightChart = initializeProgressChart('bodyWeightProgressChart', 'Body Weight', 'rgb(75, 192, 192)');
		}

		function initializeSquatProgressChart() {
			squatProgressChart = initializeProgressChart('squatProgressChart', 'Squat Weight', 'rgb(255, 99, 132)');
		}

		function initializeBenchPressProgressChart() {
			benchPressProgressChart = initializeProgressChart('benchPressProgressChart', 'Bench Press Weight', 'rgb(54, 162, 235)');
		}

		function initializeDeadliftProgressChart() {
			deadliftProgressChart = initializeProgressChart('deadliftProgressChart', 'Deadlift Weight', 'rgb(130, 192, 75)');
		}

		function updateChart(chart, data) {
			if (!chart) return;
			chart.data.datasets[0].data = data.map(item => ({
				x: new Date(item.date),
				y: parseFloat(item.weight)
			}));
			chart.data.datasets[0].unit = data[0].unit; // Assuming all entries use the same unit
			chart.update();
		}

		function updateBodyWeightProgressChart() {
			$.ajax({
				url: athleteDashboard.ajax_url,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_user_progress',
					nonce: athleteDashboard.nonce
				},
				success: function(response) {
					if (response.success && bodyWeightChart) {
						updateChart(bodyWeightChart, response.data.datasets[0].data);
					} else {
						console.error('Error in body weight progress response:', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error updating body weight progress chart:', error);
				}
			});
		}

		function updateSquatProgressChart() {
			$.ajax({
				url: athleteDashboard.ajax_url,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_user_squat_progress',
					nonce: athleteDashboard.nonce
				},
				success: function(response) {
					if (response.success && squatProgressChart) {
						updateChart(squatProgressChart, response.data.datasets[0].data);
					} else {
						console.error('Error in squat progress response:', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error updating squat progress chart:', error);
				}
			});
		}

		function updateBenchPressProgressChart() {
			$.ajax({
				url: athleteDashboard.ajax_url,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_user_bench_press_progress',
					nonce: athleteDashboard.nonce
				},
				success: function(response) {
					if (response.success && benchPressProgressChart) {
						updateChart(benchPressProgressChart, response.data.datasets[0].data);
					} else {
						console.error('Error in bench press progress response:', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error updating bench press progress chart:', error);
				}
			});
		}

		function updateDeadliftProgressChart() {
			$.ajax({
				url: athleteDashboard.ajax_url,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_user_deadlift_progress',
					nonce: athleteDashboard.nonce
				},
				success: function(response) {
					if (response.success && deadliftProgressChart) {
						updateChart(deadliftProgressChart, response.data.datasets[0].data);
					} else {
						console.error('Error in deadlift progress response:', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error updating deadlift progress chart:', error);
				}
			});
		}			

        function initializeComprehensiveBodyCompositionChart() {
            const context = document.getElementById('comprehensiveBodyCompositionChart').getContext('2d');
            bodyCompositionChart = new Chart(context, {
                type: 'line',
                data: {
                    datasets: [
                        {
                            label: 'Weight (kg)',
                            borderColor: 'rgb(75, 192, 192)',
                            yAxisID: 'y-axis-1'
                        },
                        {
                            label: 'Body Fat (%)',
                            borderColor: 'rgb(255, 99, 132)',
                            yAxisID: 'y-axis-2'
                        },
                        {
                            label: 'Muscle Mass (kg)',
                            borderColor: 'rgb(54, 162, 235)',
                            yAxisID: 'y-axis-1'
                        },
                        {
                            label: 'Body Mass Index',
                            borderColor: 'rgb(255, 206, 86)',
                            yAxisID: 'y-axis-2'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day'
                            }
                        },
                        'y-axis-1': {
                            type: 'linear',
                            display: true,
                            position: 'left'
                        },
                        'y-axis-2': {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }
		function initializeExerciseChart(exerciseKey) {
			const context = document.getElementById(`${exerciseKey}-chart`);
			if (!context) {
				console.error(`Canvas context not found for ${exerciseKey}`);
				return;
			}

			if (exerciseCharts[exerciseKey]) {
				exerciseCharts[exerciseKey].destroy();
			}

			const isBilateral = $(`#${exerciseKey}`).data('bilateral');

			exerciseCharts[exerciseKey] = new Chart(context.getContext('2d'), {
				type: 'line',
				data: {
					datasets: isBilateral ? [
						{
							label: `${CONFIG.exerciseTests[exerciseKey].label} (Left)`,
							borderColor: 'rgb(75, 192, 192)',
							tension: 0.1
						},
						{
							label: `${CONFIG.exerciseTests[exerciseKey].label} (Right)`,
							borderColor: 'rgb(255, 99, 132)',
							tension: 0.1
						}
					] : [
						{
							label: CONFIG.exerciseTests[exerciseKey].label,
							borderColor: 'rgb(75, 192, 192)',
							tension: 0.1
						}
					]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						x: {
							type: 'time',
							time: {
								unit: 'day'
							}
						},
						y: {
							beginAtZero: false
						}
					}
				}
			});
		}

		function updateBodyWeightProgressChart() {
			console.log('Updating body weight progress chart');
			$.ajax({
				url: CONFIG.ajaxUrl,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_user_progress',
					nonce: CONFIG.nonce
				},
				success: function(response) {
					console.log('Body weight progress AJAX response:', response);
					if (response.success && bodyWeightChart) {
						// Sort the data by date
						response.data.datasets[0].data.sort((a, b) => new Date(a.x) - new Date(b.x));
						bodyWeightChart.data = response.data;
						bodyWeightChart.update();
					} else {
						console.error('Error in body weight progress response:', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error updating body weight progress chart:', error);
					console.log('XHR status:', status);
					console.log('XHR response:', xhr.responseText);
				}
			});
		}

        function updateSquatProgressChart() {
            console.log('Updating squat progress chart');
            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athlete_dashboard_get_user_squat_progress',
                    nonce: CONFIG.nonce
                },
                success: function(response) {
                    console.log('Squat progress AJAX response:', response);
                    if (response.success && squatProgressChart) {
                        squatProgressChart.data = response.data;
                        squatProgressChart.update();
                    } else {
                        console.error('Error in squat progress response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating squat progress chart:', error);
                }
            });
        }

		function updateBenchPressProgressChart() {
			console.log('Updating bench press progress chart');
			$.ajax({
				url: CONFIG.ajaxUrl,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_user_bench_press_progress',
					nonce: CONFIG.nonce
				},
				success: function(response) {
					console.log('Bench press progress AJAX response:', response);
					if (response.success && benchPressProgressChart) {
						benchPressProgressChart.data = response.data;
						benchPressProgressChart.update();
					} else {
						console.error('Error in bench press progress response:', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error updating bench press progress chart:', error);
				}
			});
		}		

		function updateDeadliftProgressChart() {
			console.log('Updating deadlift progress chart');
			$.ajax({
				url: CONFIG.ajaxUrl,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_user_deadlift_progress',
					nonce: CONFIG.nonce
				},
				success: function(response) {
					console.log('Deadlift progress AJAX response:', response);
					if (response.success && deadliftProgressChart) {
						deadliftProgressChart.data = response.data;
						deadliftProgressChart.update();
					} else {
						console.error('Error in deadlift progress response:', response);
					}
				},
				error: function(xhr, status, error) {
					console.error('Error updating deadlift progress chart:', error);
				}
			});
		}			

	$('#calculate-composition').on('click', function() {
		// Get input values
		const gender = $('input[name="calc-gender"]:checked').val();
		const age = parseInt($('#calc-age').val());
		const height = parseFloat($('#calc-height').val()) * ($('#calc-height-unit').val() === 'in' ? 2.54 : 1); // convert to cm
		const weight = parseFloat($('#calc-weight').val()) * ($('#calc-weight-unit').val() === 'lbs' ? 0.453592 : 1); // convert to kg
		const neck = parseFloat($('#calc-neck').val()) * ($('#calc-neck-unit').val() === 'in' ? 2.54 : 1); // convert to cm
		const waist = parseFloat($('#calc-waist').val()) * ($('#calc-waist-unit').val() === 'in' ? 2.54 : 1); // convert to cm
		const hips = parseFloat($('#calc-hips').val()) * ($('#calc-hips-unit').val() === 'in' ? 2.54 : 1); // convert to cm

		// Calculate body fat percentage using U.S. Navy method
		let bodyFatPercentage;
		if (gender === 'male') {
			bodyFatPercentage = 495 / (1.0324 - 0.19077 * Math.log10(waist - neck) + 0.15456 * Math.log10(height)) - 450;
		} else {
			bodyFatPercentage = 495 / (1.29579 - 0.35004 * Math.log10(waist + hips - neck) + 0.22100 * Math.log10(height)) - 450;
		}

		// Ensure body fat percentage is within realistic bounds
		bodyFatPercentage = Math.max(5, Math.min(bodyFatPercentage, 50));

		// Calculate BMI
		const heightInMeters = height / 100;
		const bmi = weight / (heightInMeters * heightInMeters);

		// Calculate Lean Body Mass
		const leanBodyMass = weight * (1 - (bodyFatPercentage / 100));

		// Populate the log form with two decimal places
		$('#comprehensive-weight').val(weight.toFixed(2)).addClass('highlighted');
		$('#comprehensive-body-fat').val(bodyFatPercentage.toFixed(2)).addClass('highlighted');
		$('#comprehensive-lean-mass').val(leanBodyMass.toFixed(2)).addClass('highlighted');
		$('#comprehensive-bmi').val(bmi.toFixed(2)).addClass('highlighted');
		$('#comprehensive-measurement-date').val(new Date().toISOString().split('T')[0]).addClass('highlighted');

		// Provide visual feedback
		$('.body-composition-form').addClass('calculation-complete');
		$('#calculation-message').text('Calculation complete. You can now log your body composition.').show();

		// Smooth scroll to the log form
		$('html, body').animate({
			scrollTop: $('.body-composition-form').offset().top
		}, 1000);
	});
		
    // Remove highlighting when user starts editing
    $('.body-composition-form input').on('focus', function() {
        $(this).removeClass('highlighted');
    });

// Global variable to store the chart instance
// Global variables
let comprehensiveBodyCompositionChart = null;
let isChartUpdateInProgress = false;

function initializeBodyCompositionChart() {
    const ctx = document.getElementById('comprehensiveBodyCompositionChart').getContext('2d');
    
    comprehensiveBodyCompositionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Weight (kg)',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                },
                {
                    label: 'Body Fat (%)',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                },
                {
                    label: 'Lean Mass (kg)',
                    data: [],
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1
                },
                {
                    label: 'BMI',
                    data: [],
                    borderColor: 'rgb(255, 206, 86)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day'
                    }
                },
                y: {
                    beginAtZero: false
                }
            }
        }
    });
}

function updateBodyCompositionChart() {
    if (isChartUpdateInProgress) {
        console.log('Chart update already in progress. Skipping this update.');
        return;
    }

    isChartUpdateInProgress = true;

    $.ajax({
        url: athleteDashboard.ajax_url,
        type: 'POST',
        data: {
            action: 'get_body_composition_data',
            nonce: athleteDashboard.nonce
        },
        success: function(response) {
            if (response.success && comprehensiveBodyCompositionChart) {
                comprehensiveBodyCompositionChart.data.labels = response.data.map(item => item.date);
                comprehensiveBodyCompositionChart.data.datasets[0].data = response.data.map(item => item.weight);
                comprehensiveBodyCompositionChart.data.datasets[1].data = response.data.map(item => item.body_fat);
                comprehensiveBodyCompositionChart.data.datasets[2].data = response.data.map(item => item.lean_mass);
                comprehensiveBodyCompositionChart.data.datasets[3].data = response.data.map(item => item.bmi);

                comprehensiveBodyCompositionChart.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating body composition chart:', error);
        },
        complete: function() {
            isChartUpdateInProgress = false;
        }
    });
}

// Initialize the chart when the document is ready
$(document).ready(function() {
    if (document.getElementById('comprehensiveBodyCompositionChart')) {
        initializeBodyCompositionChart();
        updateBodyCompositionChart();
    }
});

// Update the chart when the form is submitted
$('#comprehensive-body-composition-form').on('submit', function(e) {
    e.preventDefault();
    // Your existing form submission code here
    // ...

    // After successful submission, update the chart
    updateBodyCompositionChart();
});

// Optional: Update the chart periodically
setInterval(updateBodyCompositionChart, 300000); // Update every 5 minutes

// Handle form submission
$('#comprehensive-body-composition-form').on('submit', function(e) {
    e.preventDefault();
    
    // Check if all required fields are filled
    let isValid = true;
    $(this).find('input[required]').each(function() {
        if (!$(this).val()) {
            isValid = false;
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });

    if (!isValid) {
        alert('Please fill in all required fields.');
        return;
    }

    // AJAX call to save data
    $.ajax({
        url: athleteDashboard.ajax_url,
        type: 'POST',
        data: {
            action: 'save_body_composition',
            nonce: athleteDashboard.nonce,
            weight: $('#comprehensive-weight').val(),
            body_fat: $('#comprehensive-body-fat').val(),
            lean_mass: $('#comprehensive-lean-mass').val(),
            bmi: $('#comprehensive-bmi').val(),
            date: $('#comprehensive-measurement-date').val()
        },
        success: function(response) {
            if (response.success) {
                alert('Body composition data saved successfully!');
                updateBodyCompositionChart(); // Update the chart after successful save
                // Reset form and remove highlights
                $('#comprehensive-body-composition-form')[0].reset();
                $('.body-composition-form').removeClass('calculation-complete');
                $('.body-composition-form input').removeClass('highlighted');
                $('#calculation-message').hide();
            } else {
                alert('Error saving data. Please try again.');
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');
        }
    });
});

// Initial chart load
$(document).ready(function() {
    updateBodyCompositionChart();
});		
		
        function updateComprehensiveBodyCompositionChart(startDate, endDate) {
            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athlete_dashboard_get_comprehensive_body_composition_progress',
                    nonce: CONFIG.nonce,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success && bodyCompositionChart) {
                        bodyCompositionChart.data = response.data;
                        bodyCompositionChart.update();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating comprehensive body composition chart:', error);
                }
            });
        }

        function updateExerciseChart(exerciseKey) {
            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athlete_dashboard_get_exercise_progress',
                    nonce: CONFIG.nonce,
                    exercise_key: exerciseKey
                },
                success: function(response) {
                    if (response.success && exerciseCharts[exerciseKey]) {
                        exerciseCharts[exerciseKey].data.datasets = response.data.datasets;
                        exerciseCharts[exerciseKey].update();
                    } else {
                        console.error(`Error updating ${exerciseKey} chart:`, response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(`Error updating ${exerciseKey} chart:`, error);
                }
            });
        }

		function resizeAllCharts() {
			if (bodyWeightChart) bodyWeightChart.resize();
			if (squatProgressChart) squatProgressChart.resize();
			if (benchPressProgressChart) benchPressProgressChart.resize();
			if (deadliftProgressChart) deadliftProgressChart.resize();
			if (bodyCompositionChart) bodyCompositionChart.resize();
			Object.values(exerciseCharts).forEach(chart => chart.resize());
		}

		return {
			createHighResolutionCanvas,
			initializeBodyWeightProgressChart,
			initializeSquatProgressChart,
			initializeBenchPressProgressChart,
			initializeDeadliftProgressChart,
			initializeComprehensiveBodyCompositionChart,
			initializeExerciseChart,
			updateBodyWeightProgressChart,
			updateSquatProgressChart,
			updateBenchPressProgressChart,
        	updateDeadliftProgressChart,
			updateComprehensiveBodyCompositionChart,
			updateExerciseChart,
			resizeAllCharts,
			chartExists
		};
	})();

	// AthleteForm module
	const AthleteForm = (function() {
		function initializeBodyWeightProgressForm() {
			$('#body-weight-progress-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();
				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData + '&action=athlete_dashboard_handle_progress_submission&nonce=' + CONFIG.nonce,
					success: function(response) {
						if (response.success) {
							AthleteCharts.updateBodyWeightProgressChart();
							alert('Body weight progress submitted successfully!');
						} else {
							console.error('Error submitting body weight progress:', response.data.message);
							alert('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting body weight progress:', error);
						alert('An error occurred. Please try again.');
					}
				});
			});
		}

		function initializeSquatProgressForm() {
			$('#squat-progress-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();
				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData + '&action=athlete_dashboard_handle_squat_progress_submission&nonce=' + CONFIG.nonce,
					success: function(response) {
						if (response.success) {
							AthleteCharts.updateSquatProgressChart();
							alert('Squat progress submitted successfully!');
						} else {
							console.error('Error submitting squat progress:', response.data.message);
							alert('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting squat progress:', error);
						alert('An error occurred. Please try again.');
					}
				});
			});
		}

		function initializeBenchPressProgressForm() {
			$('#bench-press-progress-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();
				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData + '&action=athlete_dashboard_handle_bench_press_progress_submission&nonce=' + CONFIG.nonce,
					success: function(response) {
						if (response.success) {
							AthleteCharts.updateBenchPressProgressChart();
							alert('Bench press progress submitted successfully!');
						} else {
							console.error('Error submitting bench press progress:', response.data.message);
							alert('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting bench press progress:', error);
						alert('An error occurred. Please try again.');
					}
				});
			});
		}	

		function initializeDeadliftProgressForm() {
			$('#deadlift-progress-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();
				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData + '&action=athlete_dashboard_handle_deadlift_progress_submission&nonce=' + CONFIG.nonce,
					success: function(response) {
						if (response.success) {
							AthleteCharts.updateDeadliftProgressChart();
							alert('Deadlift progress submitted successfully!');
						} else {
							console.error('Error submitting deadlift progress:', response.data.message);
							alert('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting deadlift progress:', error);
						alert('An error occurred. Please try again.');
					}
				});
			});
		}		
		
		function initializeExerciseProgressForm() {
			$('.exercise-progress-form').on('submit', function(e) {
				e.preventDefault();
				const $form = $(this);
				const exerciseKey = $form.find('input[name="exercise_key"]').val();
				const date = $form.find('input[name="date"]').val();
				const isBilateral = $form.closest('[data-bilateral]').data('bilateral');

				let formData = {
					action: 'athlete_dashboard_handle_exercise_progress_submission',
					nonce: CONFIG.nonce,
					exercise_key: exerciseKey,
					date: date
				};

				if (isBilateral) {
					formData.left_value = $form.find('input[name="left_value"]').val();
					formData.right_value = $form.find('input[name="right_value"]').val();
				} else {
					formData.value = $form.find('input[name="value"]').val();
				}

				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							alert('Exercise progress added successfully!');
							AthleteCharts.updateExerciseChart(exerciseKey);
							$form[0].reset();
						} else {
							console.error('Error submitting exercise progress:', response.data.message);
							alert('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting exercise progress:', error);
						alert('An error occurred while submitting the form. Please try again.');
					}
				});
			});
		}

		function initializeComprehensiveBodyCompositionForm() {
			$('#comprehensive-body-composition-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();
				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData + '&action=athlete_dashboard_store_comprehensive_body_composition_progress&nonce=' + CONFIG.nonce,
					success: function(response) {
						if (response.success) {
							AthleteCharts.updateComprehensiveBodyCompositionChart();
							alert('Comprehensive body composition data submitted successfully!');
							$('#comprehensive-body-composition-form')[0].reset();
						} else {
							console.error('Error submitting comprehensive body composition:', response.data.message);
							alert('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting comprehensive body composition:', error);
						alert('An error occurred. Please try again.');
					}
				});
			});
		}
		
		function initializeWorkoutLogForm() {
			$('#workout-log-form').on('submit', function(e) {
				e.preventDefault();
				const formData = $(this).serialize();
				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData + '&action=athlete_dashboard_submit_workout_log&nonce=' + CONFIG.nonce,
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							$('#workout-log-form')[0].reset();
							AthleteWorkoutLog.refreshRecentWorkouts();
						} else {
							alert('Error: ' + response.data.message);
							console.error('Workout log submission errors:', response.data.errors);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting workout log:', error);
						alert('An error occurred while submitting the workout log. Please try again.');
					}
				});
			});
		}
		
		// Initialize MealLogForm in the AthleteForm module
		function initializeMealLogForm() {
			$('#meal-log-form').off('submit').on('submit', function(e) {
				e.preventDefault();
				if ($(this).data('submitting')) return; // Prevent double submission
				$(this).data('submitting', true);

				const $form = $(this);
				const $submitButton = $form.find('button[type="submit"]');
				$submitButton.prop('disabled', true);

				const formData = $form.serialize();
				$.ajax({
					url: CONFIG.ajaxUrl,
					type: 'POST',
					data: formData + '&action=athlete_dashboard_submit_meal_log&nonce=' + CONFIG.nonce,
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							$form[0].reset();
							if (typeof AthleteMealLog !== 'undefined' && AthleteMealLog.refreshRecentMeals) {
								AthleteMealLog.refreshRecentMeals();
							}
						} else {
							alert('Error: ' + response.data.message);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error submitting meal log:', error);
						alert('An error occurred while submitting the meal log. Please try again.');
					},
					complete: function() {
						$form.data('submitting', false);
						$submitButton.prop('disabled', false);
					}
				});
			});
		}	
		
		return {
			initializeBodyWeightProgressForm,
			initializeSquatProgressForm,
			initializeBenchPressProgressForm,
			initializeDeadliftProgressForm,
			initializeExerciseProgressForm,
			initializeComprehensiveBodyCompositionForm,
			initializeWorkoutLogForm,  
			initializeMealLogForm
		};
	})();

	const AthleteWorkoutJourney = (function() {
		// Private variables
		let config = {
			ajaxUrl: athleteDashboard.ajax_url,
			nonce: athleteDashboard.nonce
		};
	
		// Initialize scrolling functionality
		function initializeScrolling() {
			const scrollContainer = document.querySelector('.workout-list-scrollable');
			if (scrollContainer) {
				scrollContainer.addEventListener('scroll', handleScroll);
			}
		}
	
		// Handle scroll events (placeholder for future infinite scroll)
		function handleScroll() {
			// Implement infinite scrolling or load more functionality here
			// For example:
			// if (isNearBottom(scrollContainer)) {
			//     loadMoreWorkouts();
			// }
		}
	
		// Refresh workouts list
		function refreshWorkouts() {
			jQuery.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_recent_workouts',
					nonce: config.nonce
				},
				success: function(response) {
					if (response.success) {
						jQuery('.workout-list-scrollable').html(response.data.html);
					} else {
						console.error('Error refreshing workouts:', response.data.message);
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX error refreshing workouts:', error);
				}
			});
		}
	
		// Update existing workout (to be implemented)
		function updateWorkout(workoutId, updatedData) {
			jQuery.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_update_workout',
					nonce: config.nonce,
					workout_id: workoutId,
					workout_data: updatedData
				},
				success: function(response) {
					if (response.success) {
						console.log('Workout updated successfully');
						refreshWorkouts(); // Refresh the list to show updated workout
					} else {
						console.error('Error updating workout:', response.data.message);
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX error updating workout:', error);
				}
			});
		}
	
		// Log new workout
		function logWorkout(workoutData) {
			jQuery.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_log_workout',
					nonce: config.nonce,
					workout_data: workoutData
				},
				success: function(response) {
					if (response.success) {
						console.log('Workout logged successfully');
						refreshWorkouts(); // Refresh the list to include new workout
					} else {
						console.error('Error logging workout:', response.data.message);
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX error logging workout:', error);
				}
			});
		}
	
		// Function to open the workout modal
		function openWorkoutModal(workoutId) {
			// Use AJAX to fetch the full workout data from the server
			jQuery.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'get_full_workout',
					nonce: config.nonce,
					workout_id: workoutId
				},
				success: function(response) {
					if (response.success) {
						// If the data is successfully fetched, show the modal
						showModal(response.data);
					} else {
						// Log an error if the fetch was unsuccessful
						console.error('Error fetching workout:', response.data.message);
					}
				},
				error: function(xhr, status, error) {
					// Log an error if the AJAX request fails
					console.error('AJAX error fetching workout:', error);
				}
			});
		}

		// Function to display the workout in a modal/lightbox
		function showModal(workoutContent) {
			// Create a new div for the modal and add the workout content
			var modal = jQuery('<div class="workout-modal"></div>');
			modal.html(workoutContent);
			jQuery('body').append(modal);
		
			// Find existing Print Workout and Close Workout buttons
			var printButton = modal.find('.print-workout');
			var closeButton = modal.find('.workout-lightbox-close');
		
			// Add event listener for the Close Workout button
			closeButton.on('click', function() {
				modal.remove();  // Remove the modal when close button is clicked
			});
		
			// Add click event listener for the Print Workout button
			printButton.on('click', function() {
				// Create a new window for printing
				var printWindow = window.open('', '_blank');
				var printContent = modal.find('.workout-lightbox-content').clone();
				
				// Remove elements that shouldn't be printed
				printContent.find('.modal-button-container').remove();
				
				// Write the content to the new window with custom styling
				printWindow.document.write('<html><head><title>Print Workout</title>');
				printWindow.document.write('<style>');
				printWindow.document.write(`
					body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
					h2 { color: #2c3e50; }
					.exercise-item { margin-bottom: 10px; }
					@media print {
						body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
						.exercise-item { page-break-inside: avoid; }
					}
				`);
				printWindow.document.write('</style>');
				printWindow.document.write('</head><body>');
				printWindow.document.write(printContent.html());
				printWindow.document.write('</body></html>');
				
				printWindow.document.close();
				printWindow.focus();
				
				// Print after a short delay to ensure styles are applied
				setTimeout(function() {
					printWindow.print();
					printWindow.close();
				}, 250);
			});
		

			// Add click event for toggling exercise completion
			modal.on('click', '.exercise-item', function() {
				jQuery(this).toggleClass('completed');
			});

			// Add the workout-modal-content class to the modal content
			modal.find('.full-workout').addClass('workout-modal-content');
		}

		// Initialize event listeners
		function initializeEventListeners() {
			// Listen for "Update Workout" button clicks
			jQuery(document).on('click', '.update-workout-btn', function() {
				const workoutId = jQuery(this).data('workout-id');
				const updatedData = getUpdatedWorkoutData(workoutId); // This function needs to be implemented
				updateWorkout(workoutId, updatedData);
			});

			// Event listener for opening workout modal
			jQuery(document).on('click', '.open-workout', function() {
				var workoutId = jQuery(this).data('workout-id');
				openWorkoutModal(workoutId);
			});

			// Listen for "Log Workout" form submission
			jQuery('#log-workout-form').on('submit', function(event) {
				event.preventDefault(); // Prevent the default form submission
				const workoutData = jQuery(this).serialize();
				logWorkout(workoutData);
			});
		}

		// Public methods
		return {
			init: function() {
				initializeScrolling(); // This function needs to be implemented
				initializeEventListeners();
				// Other initialization code...
			},
			refreshWorkouts: refreshWorkouts, // This function needs to be implemented
			updateWorkout: updateWorkout, // This function needs to be implemented
			logWorkout: logWorkout, // This function needs to be implemented
			openWorkoutModal: openWorkoutModal // Expose this method if needed externally
		};
	})();

	
    // AthleteProfile module
    const AthleteProfile = (function() {
        function initializeProfilePictureUpload() {
            $('#change-avatar').on('click', function(e) {
                e.preventDefault();
                $('#profile-picture-upload').click();
            });

            $('#profile-picture-upload').on('change', function() {
                const file = this.files[0];
                if (file) {
                    const formData = new FormData();
                    formData.append('action', 'athlete_dashboard_update_profile_picture');
                    formData.append('nonce', CONFIG.nonce);
                    formData.append('profile_picture', file);

                    $.ajax({
                        url: CONFIG.ajaxUrl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('.profile-picture img').attr('src', response.data.url);
                                alert('Profile picture updated successfully!');
                            } else {
                                console.error('Error updating profile picture:', response.data.message);
                                alert('Error: ' + response.data.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error updating profile picture:', error);
                            alert('An error occurred while updating the profile picture. Please try again.');
                        }
                    });
                }
            });
        }

        function initializeProfileEdit() {
            const $form = $('#account-details-form');
            const $editBtn = $('#edit-profile');
            const $saveBtn = $('#save-profile');
            const $displayFields = $('.profile-info');
            const $editFields = $('.edit-profile-fields');

            $editBtn.on('click', function() {
                $displayFields.hide();
                $editFields.show();
                $editBtn.hide();
                $saveBtn.show();
            });

            $form.on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.ajax({
                    url: CONFIG.ajaxUrl,
                    type: 'POST',
                    data: formData + '&action=athlete_dashboard_update_profile&nonce=' + CONFIG.nonce,
                    success: function(response) {
                        if (response.success) {
                            updateProfileDisplay(formData);
                            $editFields.hide();
                            $displayFields.show();
                            $saveBtn.hide();
                            $editBtn.show();
                            alert('Profile updated successfully!');
                        } else {
                            console.error('Error updating profile:', response.data.message);
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error updating profile:', error);
                        alert('An error occurred while updating the profile. Please try again.');
                    }
                });
            });
        }

        function updateProfileDisplay(formData) {
            const data = new URLSearchParams(formData);
            $('#display-name-text').text(data.get('display_name'));
            $('#email-text').text(data.get('email'));
            $('#bio-text').text(data.get('bio'));
        }

        return {
            initializeProfilePictureUpload,
            initializeProfileEdit
        };
    })();

	
	
const AthleteNutritionJourney = (function() {
    // Private variables
    let config = {
        ajaxUrl: athleteDashboard.ajax_url,
        nonce: athleteDashboard.nonce
    };

    // Initialize scrolling functionality
    function initializeScrolling() {
        const scrollContainer = document.querySelector('.nutrition-list-scrollable');
        if (scrollContainer) {
            scrollContainer.addEventListener('scroll', handleScroll);
        }
    }

    // Handle scroll events (placeholder for future infinite scroll)
    function handleScroll() {
        // Implement infinite scrolling or load more functionality here
        // For example:
        // if (isNearBottom(scrollContainer)) {
        //     loadMoreNutritionEntries();
        // }
    }

    // Refresh nutrition list
    function refreshNutrition() {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'athlete_dashboard_get_recent_nutrition',
                nonce: config.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.nutrition-list-scrollable').html(response.data.html);
                } else {
                    console.error('Error refreshing nutrition:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error refreshing nutrition:', error);
            }
        });
    }

    // Update existing nutrition entry (to be implemented)
    function updateNutrition(nutritionId, updatedData) {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'athlete_dashboard_update_nutrition',
                nonce: config.nonce,
                nutrition_id: nutritionId,
                nutrition_data: updatedData
            },
            success: function(response) {
                if (response.success) {
                    console.log('Nutrition entry updated successfully');
                    refreshNutrition(); // Refresh the list to show updated entry
                } else {
                    console.error('Error updating nutrition entry:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error updating nutrition entry:', error);
            }
        });
    }

    // Log new nutrition entry (to be implemented)
    function logNutrition(nutritionData) {
        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'athlete_dashboard_log_nutrition',
                nonce: config.nonce,
                nutrition_data: nutritionData
            },
            success: function(response) {
                if (response.success) {
                    console.log('Nutrition entry logged successfully');
                    refreshNutrition(); // Refresh the list to include new entry
                } else {
                    console.error('Error logging nutrition entry:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error logging nutrition entry:', error);
            }
        });
    }

// Personal Training Sessions functionality
$(document).ready(function() {
    const $bookSessionForm = $('#book-session-form');
    const $sessionsList = $('.sessions-list');

    $bookSessionForm.on('submit', function(e) {
        e.preventDefault();
        
        const sessionDate = $('#session-date').val();
        const sessionTime = $('#session-time').val();
        const sessionDuration = $('#session-duration').val();
        const sessionFocus = $('#session-focus').val();

        const sessionCard = createSessionCard(sessionDate, sessionTime, sessionDuration, sessionFocus);
        $sessionsList.prepend(sessionCard);

        // Clear form fields after submission
        $bookSessionForm[0].reset();
    });

    function createSessionCard(date, time, duration, focus) {
        const card = $('<div>').addClass('session-card');
        const formattedDate = new Date(date + 'T' + time).toLocaleString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric'
        });

        card.html(`
            <h4>${formattedDate}</h4>
            <p><strong>Duration:</strong> ${duration} minutes</p>
            <p><strong>Focus:</strong> ${focus}</p>
            <p><strong>Trainer:</strong> Justin Fassio</p>
            <button class="btn-cancel">Cancel Session</button>
        `);

        card.find('.btn-cancel').on('click', function() {
            if (confirm('Are you sure you want to cancel this session?')) {
                card.remove();
            }
        });

        return card;
    }
});

	// Class Bookings functionality
	$(document).ready(function() {
		const $bookClassForm = $('#book-class-form');
		const $classesList = $('.classes-list');

		$bookClassForm.on('submit', function(e) {
			e.preventDefault();

			const classDay = $('#class-day').val();
			const classTime = $('#class-time').val();
			const className = $('#class-name').val();
			const classDescription = $('#class-description').val();
			const classDifficulty = $('#class-difficulty').val();
			const classInstructor = $('#class-instructor').val();

			const classCard = createClassCard(classDay, classTime, className, classDescription, classDifficulty, classInstructor);
			$classesList.prepend(classCard);

			// Clear form fields after submission
			$bookClassForm[0].reset();
		});

		function createClassCard(day, time, name, description, difficulty, instructor) {
			const card = $('<div>').addClass('class-card');
			const formattedDate = new Date(`${day} ${time}`).toLocaleString('en-US', {
				weekday: 'long',
				hour: 'numeric',
				minute: 'numeric'
			});

			card.html(`
				<h4>${name} - ${formattedDate}</h4>
				<p><strong>Description:</strong> ${description}</p>
				<p><strong>Difficulty:</strong> ${difficulty}</p>
				<p><strong>Instructor:</strong> ${instructor}</p>
				<button class="btn-cancel">Cancel Booking</button>
			`);

			card.find('.btn-cancel').on('click', function() {
				if (confirm('Are you sure you want to cancel this class booking?')) {
					card.remove();
				}
			});

			return card;
		}
	});	
	
		// Membership Status functionality
	$(document).ready(function() {
		const $paymentHistoryTable = $('#payment-history-table tbody');

		// Dummy payment history data
		const paymentHistory = [
			{ date: '2023-05-01', amount: '$29.99', plan: 'Basic Monthly', status: 'Paid' },
			{ date: '2023-04-01', amount: '$29.99', plan: 'Basic Monthly', status: 'Paid' },
			{ date: '2023-03-01', amount: '$29.99', plan: 'Basic Monthly', status: 'Paid' },
		];

		// Populate payment history table
		paymentHistory.forEach(payment => {
			const row = `
				<tr>
					<td>${payment.date}</td>
					<td>${payment.amount}</td>
					<td>${payment.plan}</td>
					<td>${payment.status}</td>
				</tr>
			`;
			$paymentHistoryTable.append(row);
		});

		// Add event listener for Stripe buy buttons
		document.addEventListener('stripe-buy-button-success', function(event) {
			const { id, amount } = event.detail;
			alert(`Thank you for your purchase! Order ID: ${id}, Amount: ${amount}`);
			// Here you would typically update the user's membership status in your database
		});
	});

// Check-Ins and Attendance functionality
$(document).ready(function() {
    const $dateRange = $('#date-range');
    const $activityLogTable = $('#activity-log-table tbody');
    const $missedSessionsList = $('#missed-sessions-list');

    $dateRange.on('change', function() {
        updateActivitySummary();
        updateActivityLog();
        updateMissedSessions();
    });

    function updateActivitySummary() {
        // In a real application, this would fetch data from the server
        const days = parseInt($dateRange.val());
        $('#check-ins-count').text(Math.floor(Math.random() * days));
        $('#classes-attended-count').text(Math.floor(Math.random() * days * 0.5));
        $('#workout-logs-count').text(Math.floor(Math.random() * days * 0.7));
        $('#meal-logs-count').text(Math.floor(Math.random() * days * 2));
        $('#progress-entries-count').text(Math.floor(Math.random() * days * 0.2));
        $('#exercise-tests-count').text(Math.floor(Math.random() * days * 0.1));
    }

    function updateActivityLog() {
        $activityLogTable.empty();
        const days = parseInt($dateRange.val());
        const activities = ['Check-In', 'Class Attendance', 'Workout Log', 'Meal Log', 'Progress Entry', 'Exercise Test'];
        
        for (let i = 0; i < Math.min(days, 10); i++) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            const activity = activities[Math.floor(Math.random() * activities.length)];
            const details = getActivityDetails(activity);
            
            $activityLogTable.append(`
                <tr>
                    <td>${date.toLocaleDateString()}</td>
                    <td>${activity}</td>
                    <td>${details}</td>
                </tr>
            `);
        }
    }

    function updateMissedSessions() {
        $missedSessionsList.empty();
        const days = parseInt($dateRange.val());
        const missedCount = Math.floor(Math.random() * Math.min(days * 0.2, 5));
        
        for (let i = 0; i < missedCount; i++) {
            const date = new Date();
            date.setDate(date.getDate() - Math.floor(Math.random() * days));
            $missedSessionsList.append(`
                <li>Missed class on ${date.toLocaleDateString()} - HIIT Training</li>
            `);
        }
    }

    function getActivityDetails(activity) {
        switch (activity) {
            case 'Check-In':
                return 'Duration: 1h 30m';
            case 'Class Attendance':
                return 'Yoga for Athletes';
            case 'Workout Log':
                return 'Strength Training - Upper Body';
            case 'Meal Log':
                return 'Breakfast - Oatmeal with fruits';
            case 'Progress Entry':
                return 'Weight: 70kg';
            case 'Exercise Test':
                return '5k Run: 22 minutes';
            default:
                return '';
        }
    }

    // Initial update
    updateActivitySummary();
    updateActivityLog();
    updateMissedSessions();
});	
	
// Goal Tracking functionality
$(document).ready(function() {
    const $workoutGoalForm = $('#workout-goal-form');
    let weeklyGoal = 7; // Default goal

    $workoutGoalForm.on('submit', function(e) {
        e.preventDefault();
        weeklyGoal = parseInt($('#weekly-workout-goal').val());
        updateProgress();
    });

    function updateProgress() {
        // Simulated data - in a real scenario, this would come from the server
        const weeklyWorkouts = Math.floor(Math.random() * (weeklyGoal + 1));
        const monthlyWorkouts = Math.floor(Math.random() * (weeklyGoal * 4 + 1));
        const yearlyWorkouts = Math.floor(Math.random() * (weeklyGoal * 52 + 1));

        updateProgressBar('weekly', weeklyWorkouts, weeklyGoal);
        updateProgressBar('monthly', monthlyWorkouts, weeklyGoal * 4);
        updateProgressBar('yearly', yearlyWorkouts, weeklyGoal * 52);
    }

    function updateProgressBar(period, workouts, goal) {
        const percentage = Math.min(Math.round((workouts / goal) * 100), 100);
        $(`#${period}-progress`).css('width', `${percentage}%`);
        $(`#${period}-workouts`).text(workouts);
        $(`#${period}-percentage`).text(percentage);
    }

// Personalized Recommendations functionality
$(document).ready(function() {
    const recommendationTypes = ['classes', 'tips', 'offers'];

    recommendationTypes.forEach(type => {
        $(`#view-more-${type}`).on('click', function() {
            loadMoreRecommendations(type);
        });
    });

    function loadMoreRecommendations(type) {
        const $list = $(`#${type}-list`);
        const newItems = getNewRecommendations(type);

        newItems.forEach(item => {
            $list.append(`<li>${item}</li>`);
        });

        // Disable button if we've added all available recommendations
        if ($list.children().length >= 6) {
            $(`#view-more-${type}`).prop('disabled', true).text('No More Recommendations');
        }
    }

    function getNewRecommendations(type) {
        // In a real application, this would fetch data from the server
        const recommendations = {
            classes: [
                'Advanced Kettlebell Workshop - Saturday 10:00 AM',
                'Functional Fitness Circuit - Thursday 6:30 PM',
                'Outdoor Bootcamp - Sunday 8:00 AM'
            ],
            tips: [
                'Try meal prepping on Sundays for a stress-free week',
                'Incorporate active recovery days to prevent burnout',
                'Experiment with different pre-workout snacks for optimal energy'
            ],
            offers: [
                'Free 7-day trial of our premium online workout library',
                '15% off all nutritional supplements this month',
                'Bring a friend for free to any class this week'
            ]
        };

        return recommendations[type];
    }
});	
	
    // Initial update
    updateProgress();

    // Simulated progress updates every 5 seconds (for demonstration purposes)
    setInterval(updateProgress, 5000);
});	
	
    // Initialize event listeners
    function initializeEventListeners() {
        // Example: Listen for "Update Nutrition" button clicks
        $(document).on('click', '.update-nutrition-btn', function() {
            const nutritionId = $(this).data('nutrition-id');
            const updatedData = getUpdatedNutritionData(nutritionId); // Implement this function
            updateNutrition(nutritionId, updatedData);
        });

        // Example: Listen for "Log Nutrition" form submission
        $('#log-nutrition-form').on('submit', function(e) {
            e.preventDefault();
            const nutritionData = $(this).serialize();
            logNutrition(nutritionData);
        });
    }

    // Public methods
    return {
        init: function() {
            initializeScrolling();
            initializeEventListeners();
            // Other initialization code...
        },
        refreshNutrition: refreshNutrition,
        updateNutrition: updateNutrition,
        logNutrition: logNutrition
    };
})();

// Initialize in the main script
$(document).ready(function() {
    // ... other initializations ...
    AthleteNutritionJourney.init();
});	
	
/// AthleteMealLog module
const AthleteMealLog = (function() {
    let isSubmitting = false;

    function refreshRecentMeals() {
        $.ajax({
            url: CONFIG.ajaxUrl,
            type: 'POST',
            data: {
                action: 'athlete_dashboard_get_recent_meals',
                nonce: CONFIG.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayRecentMeals(response.data.meals);
                } else {
                    console.error('Error refreshing recent meals:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error refreshing recent meals:', error);
            }
        });
    }

    function displayRecentMeals(meals) {
        const $container = $('#recent-meals');
        $container.empty();

        if (meals.length === 0) {
            $container.append('<p class="no-meals">No recent meals found.</p>');
            return;
        }

        const $scrollableContainer = $('<div class="meal-list-scrollable"></div>');
        const $list = $('<div class="meal-list"></div>');

        meals.forEach(function(meal) {
            const formattedDateTime = new Date(meal.date + 'T' + meal.time).toLocaleString();
            const $item = $(`
                <div class="meal-card">
                    <div class="meal-header">
                        <h4 class="meal-date">${formattedDateTime}</h4>
                        <span class="meal-type">${meal.type}</span>
                    </div>
                    <div class="meal-details">
                        <p><strong>Name:</strong> ${meal.name}</p>
                        <p><strong>Date:</strong> ${meal.date}</p>
                        <p><strong>Time:</strong> ${meal.time}</p>
                        <p><strong>Calories:</strong> ${meal.calories}</p>
                        <p><strong>Protein:</strong> ${meal.protein.type} - ${meal.protein.quantity} ${meal.protein.unit}</p>
                        <p><strong>Fat:</strong> ${meal.fat.type} - ${meal.fat.quantity} ${meal.fat.unit}</p>
                        <p><strong>Carbs (Starch):</strong> ${meal.carb_starch.type} - ${meal.carb_starch.quantity} ${meal.carb_starch.unit}</p>
                        <p><strong>Carbs (Fruit):</strong> ${meal.carb_fruit.type} - ${meal.carb_fruit.quantity} ${meal.carb_fruit.unit}</p>
                        <p><strong>Carbs (Vegetable):</strong> ${meal.carb_vegetable.type} - ${meal.carb_vegetable.quantity} ${meal.carb_vegetable.unit}</p>
                        ${meal.description ? `<p><strong>Description:</strong> ${meal.description}</p>` : ''}
                    </div>
                </div>
            `);
            $list.append($item);
        });

        $scrollableContainer.append($list);
        $container.append($scrollableContainer);
    }

    function initializeMealLogForm() {
        $('#meal-log-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            if (isSubmitting) return;
            isSubmitting = true;

            const $form = $(this);
            const $submitButton = $form.find('button[type="submit"]');
            $submitButton.prop('disabled', true);

            const formData = $form.serialize();
            $.ajax({
                url: CONFIG.ajaxUrl,
                type: 'POST',
                data: formData + '&action=athlete_dashboard_submit_meal_log&nonce=' + CONFIG.nonce,
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        $form[0].reset();
                        refreshRecentMeals();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error submitting meal log:', error);
                    alert('An error occurred while submitting the meal log. Please try again.');
                },
                complete: function() {
                    isSubmitting = false;
                    $submitButton.prop('disabled', false);
                }
            });
        });
    }

    return {
        refreshRecentMeals,
        displayRecentMeals,
        initializeMealLogForm
    };
})();

// Initialize meal log functionality
$(document).ready(function() {
    AthleteMealLog.initializeMealLogForm();
    AthleteMealLog.refreshRecentMeals();
});
	
	// AthleteWorkoutLog module
	const AthleteWorkoutLog = (function() {
		function refreshRecentWorkouts() {
			$.ajax({
				url: CONFIG.ajaxUrl,
				type: 'POST',
				data: {
					action: 'athlete_dashboard_get_recent_workouts',
					nonce: CONFIG.nonce
				},
				success: function(response) {
					if (response.success) {
						displayRecentWorkouts(response.data.workouts);
					} else {
						console.error('Error refreshing recent workouts:', response.data.message);
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX error refreshing recent workouts:', error);
				}
			});
		}

		function displayRecentWorkouts(workouts) {
			const $container = $('#recent-workouts');
			$container.empty();

			if (workouts.length === 0) {
				$container.append('<p class="no-workouts">No recent workouts found.</p>');
				return;
			}

			const $scrollableContainer = $('<div class="workout-list-scrollable"></div>');
			const $list = $('<div class="workout-list"></div>');

			workouts.forEach(function(workout, index) {
				const $item = $(`
					<div class="workout-card">
						<div class="workout-header">
							<h4 class="workout-date">${workout.date}</h4>
							<span class="workout-type">${workout.type}</span>
						</div>
						<div class="workout-details">
							<p><strong>Duration:</strong> ${workout.duration} minutes</p>
							<p><strong>Intensity:</strong> ${workout.intensity}/10</p>
							${workout.notes ? `<p><strong>Notes:</strong> ${workout.notes}</p>` : ''}
						</div>
					</div>
				`);
				$list.append($item);
			});

			$scrollableContainer.append($list);
			$container.append($scrollableContainer);
		}
		return {
			refreshRecentWorkouts,
			displayRecentWorkouts
		};
	})();
	
    // AthleteHelpers module
    const AthleteHelpers = (function() {
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function logNonce() {
            console.log('Current nonce:', CONFIG.nonce);
        }

        return {
            debounce,
            logNonce
        };
    })();
	
function refreshRecentWorkouts() {
    $.ajax({
        url: athleteDashboard.ajax_url,
        type: 'POST',
        data: {
            action: 'athlete_dashboard_get_recent_workouts',
            nonce: athleteDashboard.nonce
        },
        success: function(response) {
            if (response.success) {
                $('#recent-workouts').html(response.data.html);
            } else {
                console.error('Error refreshing recent workouts:', response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error refreshing recent workouts:', error);
        }
    });
}	
	
	// Initialization function
	function initializeAllComponents() {
		console.log('Initializing all components');

		// Initialize UI components
		AthleteUI.initializeExerciseTabs();
		AthleteUI.initializeGroupToggles();
		AthleteUI.restoreSectionStates();

		// Initialize Profile components
		AthleteProfile.initializeProfilePictureUpload();
		AthleteProfile.initializeProfileEdit();

		// Initialize Charts
		if (document.getElementById('bodyWeightProgressChart')) {
			AthleteCharts.initializeBodyWeightProgressChart();
		}
		if (document.getElementById('squatProgressChart')) {
			AthleteCharts.initializeSquatProgressChart();
		}
		if (document.getElementById('benchPressProgressChart')) {
			AthleteCharts.initializeBenchPressProgressChart();
		}
		if (document.getElementById('deadliftProgressChart')) {
			AthleteCharts.initializeDeadliftProgressChart();
		}
		if (document.getElementById('comprehensiveBodyCompositionChart')) {
			AthleteCharts.initializeComprehensiveBodyCompositionChart();
		}

		// Initialize Forms
		AthleteForm.initializeBodyWeightProgressForm();
		AthleteForm.initializeSquatProgressForm(); 
		AthleteForm.initializeBenchPressProgressForm();
		AthleteForm.initializeDeadliftProgressForm();
		AthleteForm.initializeExerciseProgressForm();
		AthleteForm.initializeComprehensiveBodyCompositionForm();
		AthleteForm.initializeWorkoutLogForm();
		AthleteForm.initializeMealLogForm();

		// Initialize Exercise Charts
		Object.keys(CONFIG.exerciseTests).forEach(function(key) {
			if (document.getElementById(key + '-chart')) {
				AthleteCharts.initializeExerciseChart(key);
				AthleteCharts.updateExerciseChart(key);
			}
		});

		// Update initial chart data
		if (document.getElementById('bodyWeightProgressChart')) {
			AthleteCharts.updateBodyWeightProgressChart();
		}
		if (document.getElementById('squatProgressChart')) {
			AthleteCharts.updateSquatProgressChart();
		}
		if (document.getElementById('benchPressProgressChart')) {
			AthleteCharts.updateBenchPressProgressChart();
		}
		if (document.getElementById('deadliftProgressChart')) {
			AthleteCharts.updateDeadliftProgressChart();
		}
		if (document.getElementById('comprehensiveBodyCompositionChart')) {
			AthleteCharts.updateComprehensiveBodyCompositionChart();
		}

		// Initialize Logs
		AthleteWorkoutLog.refreshRecentWorkouts();
		AthleteMealLog.refreshRecentMeals();

		// Initialize Workout Journey
		AthleteWorkoutJourney.init();

		// Initialize Nutrition Journey
		AthleteNutritionJourney.init();

		// Initialize Messaging
		if (typeof AthleteMessaging !== 'undefined') {
			AthleteMessaging.initialize();
		}

		console.log('All components initialized');
	}

    // Set up event listeners
    function setupEventListeners() {
        console.log('Setting up event listeners');

        $(document).on('click', '.toggle-btn', function(event) {
            event.preventDefault();
            AthleteUI.toggleSection(this);
        });
;

        $(window).on('resize', AthleteHelpers.debounce(function() {
            console.log('Window resized');
            AthleteCharts.resizeAllCharts();
        }, 250));

        // Messaging Preview Functionality
        $('#view-all-messages').on('click', function() {
            window.location.href = athleteDashboard.messagingPageUrl;
        });

        $('.message-preview').on('click', function() {
            var conversationId = $(this).data('conversation-id');
            window.location.href = athleteDashboard.messagingPageUrl + '?conversation=' + conversationId;
        });

        console.log('Event listeners set up');
    }

    // Initialize everything
    initializeAllComponents();
    setupEventListeners();

    // Set up periodic updates
    setInterval(function() {
        console.log('Updating Body Weight Progress Chart');
        AthleteCharts.updateBodyWeightProgressChart();
    }, CONFIG.updateInterval);
    
    setInterval(function() {
        console.log('Updating Squat Progress Chart');
        AthleteCharts.updateSquatProgressChart();
    }, CONFIG.updateInterval);
    
    setInterval(function() {
        console.log('Updating Bench Press Progress Chart');
        AthleteCharts.updateBenchPressProgressChart();
    }, CONFIG.updateInterval);
    
    setInterval(function() {
        console.log('Updating Deadlift Progress Chart');
        AthleteCharts.updateDeadliftProgressChart();
    }, CONFIG.updateInterval);
    
    setInterval(function() {
        console.log('Refreshing Recent Workouts');
        AthleteWorkoutLog.refreshRecentWorkouts();
    }, CONFIG.updateInterval);
    
    setInterval(function() {
        console.log('Refreshing Recent Meals');
        AthleteMealLog.refreshRecentMeals();
    }, CONFIG.updateInterval);

    // Log nonce periodically (for debugging)
    AthleteHelpers.logNonce();
    setInterval(AthleteHelpers.logNonce, CONFIG.updateInterval);

    console.log('custom-scripts.js fully loaded and executed');
});