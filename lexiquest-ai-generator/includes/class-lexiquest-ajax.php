<?php
/**
 * LexiQuest AJAX/REST Handlers
 * Handles AJAX and REST API endpoints for frontend/backend.
 */
class LexiQuest_AJAX {
    /**
     * Register AJAX and REST API endpoints.
     */
    public static function register_endpoints() {
        // AJAX handlers
        add_action('wp_ajax_lexiquest_generate_content', [__CLASS__, 'handle_generate_content']);
        add_action('wp_ajax_nopriv_lexiquest_generate_content', [__CLASS__, 'handle_generate_content']);
        add_action('wp_ajax_lexiquest_submit_quiz', [__CLASS__, 'handle_submit_quiz']);
        add_action('wp_ajax_nopriv_lexiquest_submit_quiz', [__CLASS__, 'handle_submit_quiz']);
        // REST API endpoint
        add_action('rest_api_init', function () {
            register_rest_route('lexiquest/v1', '/generate', array(
                'methods' => 'POST',
                'callback' => [__CLASS__, 'rest_generate_content'],
                'permission_callback' => [__CLASS__, 'rest_permission_check'],
            ));
        });
    }

    /**
     * AJAX handler for content generation.
     */
    public static function handle_generate_content() {
        try {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lexiquest_ajax_nonce')) {
                throw new Exception('Invalid nonce');
            }
            $form_data = [];
            if (isset($_POST['form_data']) && is_string($_POST['form_data'])) {
                parse_str($_POST['form_data'], $form_data);
            } elseif (isset($_POST['lexile']) || isset($_POST['grade'])) {
                $form_data = [
                    'lexile' => $_POST['lexile'] ?? '',
                    'grade' => $_POST['grade'] ?? '',
                    'interests' => $_POST['interests'] ?? '',
                    'story_title' => $_POST['story_title'] ?? '',
                ];
            }
            error_log('LexiQuest DEBUG: Raw form_data: ' . print_r($form_data, true));
            error_log('LexiQuest DEBUG: Received lexile=' . ($form_data['lexile'] ?? 'NULL') . ', grade=' . ($form_data['grade'] ?? 'NULL'));
            $data = LexiQuest_Utils::sanitize_form_data($form_data);
            error_log('LexiQuest DEBUG: Sanitized data: ' . print_r($data, true));
            if (empty($form_data)) {
                throw new Exception('No valid form data received');
            }
            if (empty($form_data)) {
                throw new Exception('Form data is empty');
            }
            $lexile_level = $data['lexile'];
            $grade_level = $data['grade'];
            $interests = $data['interests'];
            $story_title = $data['story_title'];
            // Build keyword for image search
            $keyword = LexiQuest_Images::get_safe_kids_keyword($interests, $story_title);
            // Generate story and quiz
            $ai_result = LexiQuest_AI::generate_story_and_quiz($lexile_level, $grade_level, $keyword);
            $story = $ai_result['story'] ?? null;
            $quiz = $ai_result['quiz'] ?? null;
            $openai_error = $ai_result['error'] ?? null;
            // Fetch image (Pixabay, deduplication)
            $image_url = LexiQuest_Images::fetch_and_save_pixabay_image($keyword, $story_title);
            $response = [
                'story' => $story,
                'quiz' => $quiz,
                'image_url' => $image_url,
                'lexile' => $lexile_level,  // Add lexile to response
                'grade' => $grade_level,    // Add grade to response
                'errors' => LexiQuest_Utils::array_filter_non_empty([
                    'openai' => $openai_error,
                ]),
            ];
            wp_send_json_success($response);
        } catch (Exception $e) {
            LexiQuest_Utils::log('LexiQuest AJAX error: ' . $e->getMessage());
            wp_send_json_error(['error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX handler for quiz submission.
     */
    public static function handle_submit_quiz() {
        // Placeholder for quiz submission logic. Implement as needed.
        wp_send_json_success(['message' => 'Quiz submitted (stub).']);
    }

    /**
     * Permission check for REST API endpoint.
     */
    public static function rest_permission_check() {
        if (!is_user_logged_in()) return false;
        $user = wp_get_current_user();
        return (in_array('lexiquest_student', $user->roles) || in_array('lexiquest_teacher', $user->roles));
    }

    /**
     * Main handler for AI-powered content generation (REST API).
     */
    public static function rest_generate_content($request) {
        $params = $request->get_json_params();
        $lexile = $params['lexile'] ?? null;
        $grade = $params['grade'] ?? null;
        $interests = $params['interests'] ?? '';
        $story_title = $params['story_title'] ?? '';
        $student_id = $params['student_id'] ?? null;
        $errors = [];
        if (empty($lexile)) $errors[] = 'Lexile is required.';
        if (empty($grade)) $errors[] = 'Grade is required.';
        if (empty($student_id)) $errors[] = 'Student ID is required.';
        if (!empty($errors)) {
            return new WP_REST_Response([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $errors,
            ], 400);
        }
        $keyword = LexiQuest_Images::get_safe_kids_keyword($interests, $story_title);
        $ai_result = LexiQuest_AI::generate_story_and_quiz($lexile, $grade, $keyword);
        $story = $ai_result['story'] ?? null;
        $quiz = $ai_result['quiz'] ?? null;
        $openai_error = $ai_result['error'] ?? null;
        $image_url = LexiQuest_Images::fetch_and_save_pixabay_image($keyword, $story_title);
        // Save to CPTs if desired (not implemented in this stub)
        return new WP_REST_Response([
            'status' => ($story && $quiz) ? 'ok' : 'error',
            'message' => ($story && $quiz) ? 'AI content generated.' : 'Failed to generate content.',
            'input' => [
                'lexile' => $lexile,
                'grade' => $grade,
                'interests' => $interests,
                'story_title' => $story_title,
                'student_id' => $student_id,
            ],
            'story' => $story,
            'quiz' => $quiz,
            'image' => $image_url,
            'errors' => LexiQuest_Utils::array_filter_non_empty([
                'openai' => $openai_error,
            ]),
        ], ($story && $quiz) ? 200 : 500);
    }
}
