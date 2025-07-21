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
                    <label>Lexile Level: <input type="number" name="lexile" required min="0" value="50" /></label><br>
                    <label>Grade: <input type="number" name="grade" required min="1" max="12" value="3" /></label><br>
                    <label>Story Title (optional): <input type="text" name="story_title" placeholder="e.g. The Very Hungry Caterpillar" /></label><br>
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
        const story_title = formData.get('story_title')?.trim();

        // Lexile and Grade are always required
        if (!formData.get('lexile') || isNaN(lexile) || lexile <= 0) {
            showError('Lexile Level is required and must be a positive number.');
            return;
        }
        if (!formData.get('grade') || isNaN(grade) || grade < 1 || grade > 12) {
            showError('Grade is required and must be between 1 and 12.');
            return;
        }
        
        // Prepare AJAX data
        const data = {
            action: 'lexiquest_generate_content',
            nonce: lexiquest_ajax.nonce,
            lexile,
            grade,
            interests: formData.get('interests'),
        };
        if (story_title) {
            data.story_title = story_title;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Generating...';
        statusElement.textContent = 'Creating your personalized story and quiz...';
        statusElement.className = 'status-loading';
        
        // Vanilla JS AJAX (fetch)
        fetch(lexiquest_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams(data).toString()
        })
        .then(response => response.json())
        .then(response => {
            if (response && response.success) {
                statusElement.textContent = '';
                statusElement.className = '';
                renderStoryAndQuiz(response.data);
            } else {
                const errorMsg = (response && response.data && response.data.message) || 'An unexpected error occurred. Please try again.';
                showError(errorMsg);
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            showError('Failed to connect to the server.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    function renderStoryAndQuiz(data) {
        const uiContainer = document.getElementById('lexiquest-student-ui');
        if (!data || !data.story) {
            uiContainer.innerHTML = '<div class="lexiquest-story"><p><em>Sorry, no story could be generated. Please try again.</em></p></div>';
            return;
        }
        
        // Build the story HTML
        let html = `<div class="lexiquest-story">
            <h2>${(data.story && data.story.title) ? data.story.title : 'No Title Available'}</h2>
            <div class="lexiquest-story-meta">
                <span class="lexile-level">Lexile Level: ${data.lexile || (data.story && data.story.lexile_level) || 'N/A'}</span>
                <span class="grade-level">Grade: ${data.grade || 'N/A'}</span>
            </div>
            ${data.image_url ? `<div class="story-image"><img src="${data.image_url}${data.image_url.includes('?') ? '&' : '?'}t=${Date.now()}" alt="${data.story.title}" onerror="this.onerror=null;this.src='https://via.placeholder.com/800x400?text=No+Image+Available';"></div>` : ''}
            <div class="story-content">
                ${Array.isArray(data.story?.text) && data.story.text.length ?
                    data.story.text.map((paragraph, i, arr) => {
                        // Insert second image before the last paragraph
                        if (i === arr.length - 1 && data.second_image_url) {
                            return `<div class="story-image"><img src="${data.second_image_url}${data.second_image_url.includes('?') ? '&' : '?'}t=${Date.now()}" alt="Story Image" style="display:block;margin:0 auto;max-width:90%;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:1.5em;" onerror="this.onerror=null;this.src='https://via.placeholder.com/800x400?text=No+Image+Available';"></div><p>${paragraph}</p>`;
                        }
                        return `<p>${paragraph}</p>`;
                    }).join('')
                : (data.story && typeof data.story.text === 'string' && data.story.text.trim() !== '' ? `<p>${data.story.text}</p>` : '<p><em>Sorry, no story could be generated. Please try again.</em></p>')}
            </div>
        </div>`;
        
        html += `
            ${(data.quiz && Array.isArray(data.quiz.questions) && data.quiz.questions.length > 0) ? `
            <div class="lexiquest-quiz">
                <h3>Quiz Time!</h3>
                <form id="lexiquest-quiz-form">
                    ${data.quiz.questions.map((question, qIndex) => `
                        <div class="quiz-question">
                            <p><strong>${qIndex + 1}. ${question.question || 'Question ' + (qIndex + 1)}</strong></p>
                            <div class="quiz-options">
                                ${(question.choices && Array.isArray(question.choices) ? question.choices : (question.options && Array.isArray(question.options) ? question.options : [])).map((option, oIndex) => `
                                    <label>
                                        <input type="radio" name="q${qIndex}" value="${oIndex}">
                                        ${option || 'Option ' + (oIndex + 1)}
                                    </label><br>
                                `).join('')}
                                ${(!(question.choices && question.choices.length) && !(question.options && question.options.length)) ? 
                                    '<p><em>No options available for this question.</em></p>' : ''}
                            </div>
                        </div>
                    `).join('')}
                    <button type="submit" class="button">Submit Quiz</button>
                </form>
            </div>` : 
            '<div class="lexiquest-quiz"><p><em>No quiz available at this time.</em></p></div>'}
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
