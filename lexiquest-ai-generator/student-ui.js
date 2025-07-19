// LexiQuest Student UI (Vanilla JS)
(function() {
    // Ensure lexiquest_ajax is defined
    if (typeof lexiquest_ajax === 'undefined') {
        console.warn('lexiquest_ajax is not defined. Using fallback values.');
        window.lexiquest_ajax = {
            ajax_url: window.ajaxurl || '/wp-admin/admin-ajax.php',
            nonce: 'dev_nonce_123',
            is_admin: false
        };
    }
    document.addEventListener('DOMContentLoaded', function() {
        const uiContainer = document.getElementById('lexiquest-student-ui');
        
        // Initial render
        renderForm();
        
        // Event delegation for form submission
        uiContainer.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            
            if (form.id === 'lexiquest-student-form') {
                handleFormSubmit(form);
            } else if (form.id === 'lexiquest-quiz-form') {
                handleQuizSubmit(form);
            }
        });
        
        // Event delegation for retake assessment and other buttons
        uiContainer.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'retake-assessment') {
                e.preventDefault();
                renderForm();
            }
        });
    });

    function renderForm() {
        const uiContainer = document.getElementById('lexiquest-student-ui');
        uiContainer.innerHTML = `
            <div class="lq-ui-inner">
                <h2>LexiQuest Student Portal</h2>
                <div id="lq-status"></div>
                <form id="lexiquest-student-form">
                    <label>Lexile Level: <input type="number" name="lexile" required min="0" /></label><br>
                    <label>Grade: <input type="number" name="grade" required min="1" max="12" /></label><br>
                    <label>Interests (comma separated): <input type="text" name="interests" placeholder="adventure, animals, friendship" /></label><br>
                    <button type="submit">Get My Story & Quiz</button>
                </form>
                <div id="lq-result" style="margin-top:2em;"></div>
            </div>
        `;
    }

    function handleFormSubmit(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        const statusElement = document.getElementById('lq-status');
        
        // Validate form data
        const lexile = parseInt(formData.get('lexile'), 10);
        const grade = parseInt(formData.get('grade'), 10);
        
        if (isNaN(lexile) || lexile <= 0) {
            showError('Please enter a valid Lexile level (must be a positive number)');
            return;
        }
        
        if (isNaN(grade) || grade < 1 || grade > 12) {
            showError('Please enter a valid grade level (1-12)');
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Generating...';
        statusElement.textContent = 'Creating your personalized story and quiz...';
        statusElement.className = 'status-loading';
        
        try {
            // Convert FormData to URL-encoded string
            const formDataString = new URLSearchParams(formData).toString();
            
            // Make AJAX request
            jQuery.ajax({
                url: lexiquest_ajax.ajax_url,
                type: 'POST',
                data: formDataString + '&action=lexiquest_generate_content&nonce=' + encodeURIComponent(lexiquest_ajax.nonce),
                success: function(response) {
                    if (response && response.success) {
                        // Clear any previous status
                        statusElement.textContent = '';
                        statusElement.className = '';
                        // Render the story and quiz
                        renderStoryAndQuiz(response.data);
                    } else {
                        // Show error message from server
                        const errorMsg = (response && response.data && response.data.message) || 
                                      'An unexpected error occurred. Please try again.';
                        showError(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr);
                    let errorMsg = 'Failed to connect to the server. ';
                    
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else if (xhr.status === 0) {
                        errorMsg += 'Please check your internet connection.';
                    } else if (xhr.status === 403) {
                        errorMsg = 'Session expired. Please refresh the page and try again.';
                    } else if (xhr.status >= 500) {
                        errorMsg = 'Server error. Please try again later.';
                    }
                    
                    showError(errorMsg);
                },
                complete: function() {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            });
        } catch (error) {
            console.error('Unexpected error:', error);
            showError('An unexpected error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }

    function renderStoryAndQuiz(data) {
        const uiContainer = document.getElementById('lexiquest-student-ui');
        
        // Build the story HTML
        let html = `
            <div class="lexiquest-story">
                <h2>${data.story.title}</h2>
                <div class="lexiquest-story-meta">
                    <span class="lexile-level">Lexile Level: ${data.story.lexile_level}</span>
                </div>
                ${data.image_url ? `<div class="story-image"><img src="${data.image_url}${data.image_url.includes('?') ? '&' : '?'}t=${Date.now()}" alt="${data.story.title}" onerror="this.onerror=null;this.src='https://via.placeholder.com/800x400?text=No+Image+Available';"></div>` : ''}
                <div class="story-content">
                    ${data.story.content.split('\n').map(paragraph => `<p>${paragraph}</p>`).join('')}
                </div>
            </div>
            <div class="lexiquest-quiz">
                <h3>Quiz Time!</h3>
                <form id="lexiquest-quiz-form">
                    ${data.quiz.questions.map((question, qIndex) => `
                        <div class="quiz-question">
                            <p><strong>${qIndex + 1}. ${question.question}</strong></p>
                            <div class="quiz-options">
                                ${question.options.map((option, oIndex) => `
                                    <label>
                                        <input type="radio" name="q${qIndex}" value="${oIndex}">
                                        ${option}
                                    </label><br>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                    <button type="submit" class="button">Submit Quiz</button>
                </form>
            </div>
            <div class="lexiquest-actions">
                <button id="retake-assessment" class="button">Retake Lexile Assessment</button>
            </div>
        `;
        
        uiContainer.innerHTML = html;
    }

    function handleQuizSubmit(form) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        
        // Get the form data as an object
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });
        
        // Make AJAX request
        jQuery.ajax({
            url: lexiquest_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'lexiquest_submit_quiz',
                nonce: lexiquest_ajax.nonce,
                form_data: data
            },
            success: function(response) {
                if (response.success) {
                    // Show quiz results
                    showQuizResults(response.data);
                } else {
                    // Show error message
                    showError(response.data.message || 'An error occurred. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                showError('Failed to connect to the server. Please check your connection and try again.');
            },
            complete: function() {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    function showQuizResults(data) {
        const uiContainer = document.getElementById('lexiquest-student-ui');
        
        // Build the quiz results HTML
        let html = `
            <div class="lexiquest-quiz-results">
                <h2>Quiz Results</h2>
                <p>Score: ${data.score} / ${data.total}</p>
                ${data.questions.map((question, qIndex) => `
                    <div class="quiz-question">
                        <p><strong>${qIndex + 1}. ${question.question}</strong></p>
                        <p>Correct answer: ${question.correct_answer}</p>
                        <p>Your answer: ${question.user_answer}</p>
                    </div>
                `).join('')}
            </div>
        `;
        
        uiContainer.innerHTML = html;
    }

    function showError(message, isFatal = false) {
        const uiContainer = document.getElementById('lexiquest-student-ui');
        const statusElement = document.getElementById('lq-status');
        
        if (isFatal) {
            // For critical errors, replace the entire content
            uiContainer.innerHTML = `
                <div class="lexiquest-error">
                    <h2>Error</h2>
                    <p>${message}</p>
                    <button id="retry-button" class="button">Try Again</button>
                </div>
            `;
            
            // Add event listener for retry button
            const retryBtn = document.getElementById('retry-button');
            if (retryBtn) {
                retryBtn.addEventListener('click', function() {
                    renderForm();
                });
            }
        } else if (statusElement) {
            // For non-fatal errors, show in status element
            statusElement.textContent = message;
            statusElement.className = 'status-error';
            
            // Auto-clear the error after 5 seconds
            setTimeout(() => {
                if (statusElement.textContent === message) {
                    statusElement.textContent = '';
                    statusElement.className = '';
                }
            }, 5000);
        } else {
            // Fallback if status element doesn't exist
            console.error('Error:', message);
        }
    }
})();
