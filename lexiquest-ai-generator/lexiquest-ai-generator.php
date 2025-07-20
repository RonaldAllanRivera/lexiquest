<?php
/*
Plugin Name: LexiQuest AI Generator
Description: AI-powered story and quiz generator for LexiQuest AI
Version: 0.1.0
Author: Ronald Allan Rivera
*/

// Silence is golden. Base plugin file.

// --- LexiQuest: Register tags for attachments ---
add_action('init', function() {
    register_taxonomy_for_object_type('post_tag', 'attachment');
});

// === LexiQuest Student UI Shortcode and Asset Loader ===
function lexiquest_student_ui_shortcode($atts) {
    // Enqueue assets
    lexiquest_student_ui_enqueue_assets();
    
    // Output container div for JS UI
    return '<div id="lexiquest-student-ui"></div>';
}

function lexiquest_student_ui_enqueue_assets() {
    static $enqueued = false;
    
    if ($enqueued) {
        return; // Prevent double enqueue
    }
    
    $plugin_url = plugin_dir_url(__FILE__);
    $version = '1.0.0';
    
    // Enqueue CSS
    wp_enqueue_style(
        'lexiquest-student-ui-css', 
        $plugin_url . 'student-ui.css', 
        [], 
        $version
    );
    
    // Enqueue JS with jQuery dependency
    wp_enqueue_script(
        'lexiquest-student-ui-js', 
        $plugin_url . 'student-ui.js', 
        ['jquery'], 
        $version, 
        true
    );
    
    // Localize the script with the AJAX URL and nonce
    $localized_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('lexiquest_ajax_nonce'),
        'is_admin' => current_user_can('manage_options')
    ];
    
    wp_localize_script(
        'lexiquest-student-ui-js', 
        'lexiquest_ajax', 
        $localized_data
    );
    
    // Debug output
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('LexiQuest: Script localized with: ' . print_r($localized_data, true));
    }
    
    $enqueued = true;
}
// === End Student UI Shortcode ===

// Register the shortcode
add_action('init', function() {
    add_shortcode('lexiquest_student_ui', 'lexiquest_student_ui_shortcode');
});

// Register AJAX handlers
add_action('wp_ajax_lexiquest_generate_content', 'lexiquest_handle_generate_content');
add_action('wp_ajax_nopriv_lexiquest_generate_content', 'lexiquest_handle_generate_content');
add_action('wp_ajax_lexiquest_submit_quiz', 'lexiquest_handle_submit_quiz');
add_action('wp_ajax_nopriv_lexiquest_submit_quiz', 'lexiquest_handle_submit_quiz');

// Handle content generation request
function lexiquest_handle_generate_content() {
    try {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lexiquest_ajax_nonce')) {
            throw new Exception('Invalid nonce');
        }
        
        // Get and sanitize form data
        $form_data = [];
        
        // Check if we have form_data (from jQuery.serialize()) or direct form fields
        if (isset($_POST['form_data']) && is_string($_POST['form_data'])) {
            parse_str($_POST['form_data'], $form_data);
        } elseif (isset($_POST['lexile']) || isset($_POST['grade'])) {
            // If form was submitted directly (not serialized)
            $form_data = [
                'lexile' => $_POST['lexile'] ?? '',
                'grade' => $_POST['grade'] ?? '',
                'interests' => $_POST['interests'] ?? ''
            ];
        } else {
            throw new Exception('No valid form data received');
        }
        
        if (empty($form_data)) {
            throw new Exception('Form data is empty');
        }
        
        $lexile_level = isset($form_data['lexile']) ? intval($form_data['lexile']) : 0;
        $grade_level = isset($form_data['grade']) ? intval($form_data['grade']) : 0;
        $interests = isset($form_data['interests']) ? sanitize_text_field($form_data['interests']) : '';
    
        // Validate input
        if ($lexile_level <= 0) {
            throw new Exception('Please provide a valid Lexile level');
        }
        
        if ($grade_level <= 0) {
            throw new Exception('Please provide a valid grade level');
        }
        
        // Generate content (in a real implementation, this would call your AI service)
        $response = [
            'story' => [
                'title' => 'The Adventure Begins',
                'content' => "Once upon a time, there was a student in grade {$grade_level} who loved learning. This is a sample story generated based on your input.\n\nThe student's Lexile level was {$lexile_level}, and they were interested in {$interests}.",
                'lexile_level' => $lexile_level
            ],
            'quiz' => [
                'questions' => [
                    [
                        'question' => 'What grade level was this story written for?',
                        'options' => [
                            'Kindergarten',
                        "Grade {$grade_level}",
                        'College',
                        'Not specified'
                    ],
                    'correct_answer' => 1,
                    'explanation' => 'The story was specifically written for your grade level.'
                ]
            ]
        ],
            'image_url' => (function() use ($interests) {
                $keyword = lexiquest_get_safe_kids_keyword($interests, $story_title ?? '');
                $local_url = lexiquest_fetch_and_save_pixabay_image($keyword, $story_title ?? '');
                return $local_url ? $local_url : lexiquest_get_fallback_image_url();
            })()
        ];
        
        wp_send_json_success($response);
    } catch (Exception $e) {
        error_log('LexiQuest Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => $e->getMessage(),
            'code' => $e->getCode() ?: 500
        ], $e->getCode() ?: 500);
    }
}

// Handle quiz submission
function lexiquest_handle_submit_quiz() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lexiquest_ajax_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }
    
    // In a real implementation, you would process the quiz answers here
    // For now, we'll just return a success response
    $response = [
        'score' => 1,
        'total' => 1,
        'questions' => [
            [
                'question' => 'What grade level was this story written for?',
                'correct_answer' => 'Grade ' . (isset($_POST['form_data']['grade']) ? intval($_POST['form_data']['grade']) : 'N/A'),
                'user_answer' => 'Your answer would be processed here'
            ]
        ]
    ];
    
    wp_send_json_success($response);
}

/**
 * LexiQuest AI Generator - Automated Backend Scaffolding
 *
 * Registers REST API endpoint for automated story, quiz, and image generation based on student profile.
 */
add_action('rest_api_init', function () {
    register_rest_route('lexiquest/v1', '/generate', array(
        'methods' => 'POST',
        'callback' => 'lexiquest_ai_generate_content',
        'permission_callback' => 'lexiquest_ai_generate_permission_check',
    ));
});

/**
 * Permission check for AI content generation endpoint.
 * Only allow logged-in students or teachers.
 */
function lexiquest_ai_generate_permission_check() {
    if ( !is_user_logged_in() ) return false;
    $user = wp_get_current_user();
    if ( in_array('lexiquest_student', $user->roles) || in_array('lexiquest_teacher', $user->roles) ) {
        return true;
    }
    return false;
}


/**
 * Main handler for AI-powered content generation.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function lexiquest_ai_generate_content($request) {
    // Extract student profile info
    $params = $request->get_json_params();
    $lexile = $params['lexile'] ?? null;
    $grade = $params['grade'] ?? null;
    $interests = $params['interests'] ?? [];
    $student_id = $params['student_id'] ?? null;

    // Input validation
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

    // TODO: Log usage (rate limiting, etc)

    // --- 1. Build safe, age-appropriate prompt for OpenAI ---
    $theme = !empty($interests) ? implode(', ', $interests) : 'general';
    // Senior prompt engineering: explicit, safe, robust, with required JSON format
// Example required output:
// {
//   "story_title": "...",
//   "story_text": "...",
//   "quiz_title": "...",
//   "questions": [
//     {
//       "question": "...",
//       "choices": ["...", "...", "...", "..."],
//       "answer": "...",
//       "explanation": "..."
//     }
//   ]
// }
$prompt = "You are an expert children's author and educator. Write an original, positive, age-appropriate story for a student in grade {$grade} with a Lexile level of {$lexile}. The story must be safe for children: no violence, fear, bullying, or inappropriate content. The story should be about {$theme}. Length: about 300 words. After the story, create 5 multiple-choice comprehension questions (4 options each), with the correct answer and a 1-sentence explanation. Output MUST be valid JSON with these keys: story_title, story_text, quiz_title, questions (array of question, choices, answer, explanation). If you cannot answer, output a JSON error object: {\"error\": \"reason\"}";

    // --- 2. Call OpenAI API (GPT-4, safe prompt) ---
    $openai_key = get_option('lexiquest_openai_api_key');
    $story = null;
    $quiz = null;
    $openai_error = null;
    if ($openai_key) {
        $openai_response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $openai_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful, safe, age-appropriate children\'s story and quiz generator.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 2048,
                'temperature' => 0.8,
            ]),
            'timeout' => 30,
        ]);
        if (is_wp_error($openai_response)) {
            $openai_error = $openai_response->get_error_message();
        } else {
            $body = json_decode(wp_remote_retrieve_body($openai_response), true);
            if (isset($body['choices'][0]['message']['content'])) {
                $json = json_decode($body['choices'][0]['message']['content'], true);
if ($json && isset($json['error'])) {
    error_log('LexiQuest OpenAI error: ' . $json['error']);
    $openai_error = 'OpenAI error: ' . $json['error'];
} elseif ($json && isset($json['story_title'], $json['story_text'], $json['questions'])) {
                    $story = [
                        'title' => $json['story_title'],
                        'text' => $json['story_text'],
                    ];
                    $quiz = [
                        'title' => $json['quiz_title'] ?? 'Comprehension Quiz',
                        'questions' => $json['questions'],
                    ];
                } else {
    error_log('LexiQuest: OpenAI response could not be parsed as expected. Raw: ' . $body['choices'][0]['message']['content']);
    $openai_error = 'OpenAI response could not be parsed as expected.';
}
            } else {
                $openai_error = 'No content returned from OpenAI.';
            }
        }
    } else {
        $openai_error = 'OpenAI API key not set.';
    }

    // --- 3. Fetch relevant image from Unsplash ---
    $unsplash_key = get_option('lexiquest_image_api_key');
    $image_url = null;
    $image_error = null;
    if ($unsplash_key && $story) {
        $query = urlencode(($theme !== 'general' ? $theme : 'children story') . ' ' . ($story['title'] ?? 'book'));
        $unsplash_url = "https://api.unsplash.com/photos/random?query={$query}&orientation=landscape&client_id={$unsplash_key}";
        $img_response = wp_remote_get($unsplash_url, [ 'timeout' => 15 ]);
        if (is_wp_error($img_response)) {
            $image_error = $img_response->get_error_message();
        } else {
            $img_body = json_decode(wp_remote_retrieve_body($img_response), true);
            if (isset($img_body['urls']['regular'])) {
                $image_url = $img_body['urls']['regular'];
            } else {
                $image_error = 'No image found for this story.';
            }
        }
    } elseif (!$unsplash_key) {
        $image_error = 'Unsplash Access Key not set.';
    }

    // --- 4. Save generated content to CPTs and link to student profile ---
    $story_post_id = null;
    $quiz_post_id = null;
    $story_url = null;
    $quiz_url = null;
    $save_error = null;
    if ($story && $quiz) {
        // 1. Save AI Story CPT
        $story_post_id = wp_insert_post([
            'post_type' => 'ai_story',
            'post_title' => wp_strip_all_tags($story['title']),
            'post_content' => $story['text'],
            'post_status' => 'publish',
            'meta_input' => [
                'lexile' => $lexile,
                'grade' => $grade,
                'student_id' => $student_id,
                'ai_generated' => 1,
                'image_url' => $image_url,
            ],
        ], true);
        if (is_wp_error($story_post_id)) {
            $save_error = 'Failed to save story: ' . $story_post_id->get_error_message();
            $story_post_id = null;
        } else {
            $story_url = get_permalink($story_post_id);
        }
        // 2. Save Quiz CPT
        $quiz_post_id = wp_insert_post([
            'post_type' => 'quiz',
            'post_title' => wp_strip_all_tags($quiz['title']),
            'post_content' => '', // Optionally store quiz as JSON or custom fields
            'post_status' => 'publish',
            'meta_input' => [
                'lexile' => $lexile,
                'grade' => $grade,
                'student_id' => $student_id,
                'ai_generated' => 1,
                'questions' => maybe_serialize($quiz['questions']),
                'story_post_id' => $story_post_id,
            ],
        ], true);
        if (is_wp_error($quiz_post_id)) {
            $save_error = 'Failed to save quiz: ' . $quiz_post_id->get_error_message();
            $quiz_post_id = null;
        } else {
            $quiz_url = get_permalink($quiz_post_id);
        }
        // 3. Optionally, link quiz to story
        if ($story_post_id && $quiz_post_id) {
            update_post_meta($story_post_id, 'quiz_post_id', $quiz_post_id);
        }
    }

    // --- 5. Return all generated data, links, and errors ---
    return new WP_REST_Response([
        'status' => ($story && $quiz && $story_post_id && $quiz_post_id) ? 'ok' : 'error',
        'message' => ($story && $quiz && $story_post_id && $quiz_post_id) ? 'AI content generated and saved.' : 'Failed to generate or save content.',
        'input' => [
            'lexile' => $lexile,
            'grade' => $grade,
            'interests' => $interests,
            'student_id' => $student_id,
        ],
        'story' => $story,
        'quiz' => $quiz,
        'image' => $image_url,
        'story_post_id' => $story_post_id,
        'quiz_post_id' => $quiz_post_id,
        'story_url' => $story_url,
        'quiz_url' => $quiz_url,
        'errors' => array_filter([
            'openai' => $openai_error,
            'image' => $image_error,
            'save' => $save_error,
        ]),
    ], ($story && $quiz && $story_post_id && $quiz_post_id) ? 200 : 500);
}

// --- LexiQuest: Safe Keyword Filtering and Unsplash Image Sideloading ---
/**
 * Returns a safe-for-kids keyword from interests or a default.
 */
function lexiquest_get_safe_kids_keyword($interests, $story_title = '') {
    $whitelist = [
        'animals','nature','reading','books','school','sports','adventure','science','art','music','friendship','kindness','history','space','math','technology','robot','garden','tree','forest','mountain','ocean','sea','river','flower','insect','bird','dog','cat','horse','dinosaur','transportation','train','car','plane','boat','exploration','discovery','imagination','fun','learning','play','children','kids','student','story','library','teacher','classroom','puzzle','game','drawing','painting','craft','lego','block','magic','superhero','princess','castle','knight','dragon','pirate','detective','mystery','holiday','festival','celebration','family','community','help','respect','courage'
    ];
    // If story_title is provided, try to use it as a keyword if it's safe
    if ($story_title) {
        $title = strtolower($story_title);
        foreach ($whitelist as $safe) {
            if (strpos($title, $safe) !== false) {
                return $safe;
            }
        }
        // Use the title as-is if not obviously unsafe
        return $title;
    }
    $interests = strtolower($interests);
    $interestArr = preg_split('/[\s,;]+/', $interests);
    foreach ($interestArr as $interest) {
        if (in_array($interest, $whitelist)) {
            return $interest;
        }
    }
    return 'children books'; // fallback default
}

/**
 * Returns the local fallback image URL from plugin assets.
 */
function lexiquest_get_fallback_image_url() {
    // Adjust this path if your fallback image is in a different location
    $fallback_rel = 'wp-content/plugins/lexiquest-ai-generator/assets/fallback.jpg';
    $fallback_abs = ABSPATH . $fallback_rel;
    $fallback_url = site_url(str_replace(ABSPATH, '', $fallback_abs));
    if (file_exists($fallback_abs)) {
        return $fallback_url;
    }
    // If missing, return a generic placeholder
    return 'https://via.placeholder.com/800x400?text=Image+Not+Available';
}

/**
 * Downloads a random Pixabay image for a given keyword and saves it to the Media Library.
 * Returns the local URL or false on failure.
 */
/**
 * Search the Media Library for an attachment matching the keyword/tag.
 * Returns the URL if found, or false if not found.
 */
function lexiquest_find_media_library_image($keyword) {
    $args = [
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'tax_query'      => [
            [
                'taxonomy' => 'post_tag',
                'field'    => 'name',
                'terms'    => $keyword,
            ],
        ],
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_lexiquest_pixabay_keyword',
                'value'   => $keyword,
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_lexiquest_pixabay_source_url',
                'value'   => '', // fallback: any source
                'compare' => '!=',
            ]
        ],
    ];
    $query = new WP_Query($args);
    if ($query->have_posts()) {
        $attachment_id = $query->posts[0]->ID;
        $url = wp_get_attachment_url($attachment_id);
        if ($url) return $url;
    }
}

 /* 
 * Returns the local URL or false on failure.
 * Deduplication: Checks Media Library for existing image by tag/keyword or source URL before downloading.
 * Tags new uploads for future matching.
 *
 * @param string $keyword
 * @param string $story_title (optional)
 * @return string|false
 */
function lexiquest_fetch_and_save_pixabay_image($keyword, $story_title = '') {
    error_log('LexiQuest: Attempting to fetch Pixabay image for keyword: ' . $keyword);
    // 1. Deduplication: Search Media Library first
    $existing_url = lexiquest_find_media_library_image($keyword);
    if ($existing_url) {
        error_log('LexiQuest: Found existing Media Library image for keyword: ' . $keyword);
        return $existing_url;
    }
    $pixabay_key = get_option('lexiquest_pixabay_api_key');
    if (!$pixabay_key) {
        error_log('LexiQuest: Pixabay API key not set.');
        return lexiquest_get_fallback_image_url();
    }
    // --- Keyword fallback debug ---
    $safe_keyword = $keyword;
    $whitelist = [
        'animals','nature','reading','books','school','sports','adventure','science','art','music','friendship','kindness','history','space','math','technology','robot','garden','tree','forest','mountain','ocean','sea','river','flower','insect','bird','dog','cat','horse','dinosaur','transportation','train','car','plane','boat','exploration','discovery','imagination','fun','learning','play','children','kids','student','story','library','teacher','classroom','puzzle','game','drawing','painting','craft','lego','block','magic','superhero','princess','castle','knight','dragon','pirate','detective','mystery','holiday','festival','celebration','family','community','help','respect','courage','fish'
    ];
    if (!in_array(strtolower($keyword), $whitelist)) {
        error_log('LexiQuest: Keyword "' . $keyword . '" not in whitelist. Falling back to "children books".');
        $safe_keyword = 'children books';
    }
    // Relaxed filters: removed editors_choice and broadened search
$api_url = 'https://pixabay.com/api/?key=' . urlencode($pixabay_key) . '&q=' . urlencode($safe_keyword) . '&safesearch=true&per_page=5&orientation=horizontal&image_type=photo'; // editors_choice removed, per_page increased
// TODO: In the future, ask AI for a list of general/synonym fallback keywords if no result
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        error_log('LexiQuest: Pixabay API request failed: ' . $response->get_error_message());
        return lexiquest_get_fallback_image_url();
    }
    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);
    if (empty($json['hits']) || !isset($json['hits'][0]['largeImageURL'])) {
        error_log('LexiQuest: No Pixabay image found for keyword: ' . $safe_keyword);
        // --- Fallback: always use static asset, never upload fallback image ---
        $fallback_url = lexiquest_get_fallback_image_url();
        error_log('LexiQuest: No suitable image found. Returning static fallback asset: ' . $fallback_url);
        return $fallback_url;
    }
    $image_url = $json['hits'][0]['largeImageURL'];
    // Prevent uploading fallback image: if the image_url is the fallback asset (local or known fallback), just return the fallback asset URL
    $fallback_url = lexiquest_get_fallback_image_url();
    if (strpos($image_url, 'children-books') !== false || $image_url === $fallback_url) {
        error_log('LexiQuest: Pixabay returned fallback image. Returning static fallback asset: ' . $fallback_url);
        return $fallback_url;
    }
    // 2. Deduplication: Check if this image URL has already been uploaded
    $existing_by_url = get_posts([
        'post_type'  => 'attachment',
        'post_status'=> 'inherit',
        'meta_key'   => '_lexiquest_pixabay_source_url',
        'meta_value' => $image_url,
        'numberposts'=> 1
    ]);
    if (!empty($existing_by_url)) {
        $attachment_id = $existing_by_url[0]->ID;
        $url = wp_get_attachment_url($attachment_id);
        error_log('LexiQuest: Found duplicate image by source URL. Returning existing: ' . $url);
        return $url;
    }
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $tmp = download_url($image_url);
    if (is_wp_error($tmp)) {
        error_log('LexiQuest: download_url failed: ' . $tmp->get_error_message());
        return lexiquest_get_fallback_image_url();
    }
    $file_array = [
        'name' => $safe_keyword . '-' . time() . '.jpg',
        'tmp_name' => $tmp
    ];
    $id = media_handle_sideload($file_array, 0);
    if (is_wp_error($id)) {
        error_log('LexiQuest: media_handle_sideload failed: ' . $id->get_error_message());
        @unlink($tmp);
        return lexiquest_get_fallback_image_url();
    }
    // 3. Tagging: Add keyword as tag and meta
    wp_set_post_terms($id, [$safe_keyword], 'post_tag', true);
    update_post_meta($id, '_lexiquest_pixabay_keyword', $safe_keyword);
    update_post_meta($id, '_lexiquest_pixabay_source_url', $image_url);
    $local_url = wp_get_attachment_url($id);
    error_log('LexiQuest: Pixabay image sideloaded successfully. Local URL: ' . $local_url);
    return $local_url;
    error_log('LexiQuest: Attempting to fetch Pixabay image for keyword: ' . $keyword);
    // 1. Deduplication: Search Media Library first
    $existing_url = lexiquest_find_media_library_image($keyword);
    if ($existing_url) {
        error_log('LexiQuest: Found existing Media Library image for keyword: ' . $keyword);
        return $existing_url;
    }
    $pixabay_key = get_option('lexiquest_pixabay_api_key');
    if (!$pixabay_key) {
        error_log('LexiQuest: Pixabay API key not set.');
        return lexiquest_get_fallback_image_url();
    }
    $api_url = 'https://pixabay.com/api/?key=' . urlencode($pixabay_key) . '&q=' . urlencode($keyword) . '&safesearch=true&per_page=3&orientation=horizontal&image_type=photo&editors_choice=true';
    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        error_log('LexiQuest: Pixabay API request failed: ' . $response->get_error_message());
        return lexiquest_get_fallback_image_url();
    }
    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);
    if (empty($json['hits']) || !isset($json['hits'][0]['largeImageURL'])) {
        error_log('LexiQuest: No Pixabay image found for keyword: ' . $keyword);
        return lexiquest_get_fallback_image_url();
    }
    $image_url = $json['hits'][0]['largeImageURL'];
    // 2. Deduplication: Check if this image URL has already been uploaded
    $existing_by_url = get_posts([
        'post_type'  => 'attachment',
        'post_status'=> 'inherit',
        'meta_key'   => '_lexiquest_pixabay_source_url',
        'meta_value' => $image_url,
        'numberposts'=> 1
    ]);
    if (!empty($existing_by_url)) {
        $attachment_id = $existing_by_url[0]->ID;
        $url = wp_get_attachment_url($attachment_id);
        error_log('LexiQuest: Found duplicate image by source URL. Returning existing: ' . $url);
        return $url;
    }
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $tmp = download_url($image_url);
    if (is_wp_error($tmp)) {
        error_log('LexiQuest: download_url failed: ' . $tmp->get_error_message());
        return lexiquest_get_fallback_image_url();
    }
    $file_array = [
        'name' => $keyword . '-' . time() . '.jpg',
        'tmp_name' => $tmp
    ];
    $id = media_handle_sideload($file_array, 0);
    if (is_wp_error($id)) {
        error_log('LexiQuest: media_handle_sideload failed: ' . $id->get_error_message());
        @unlink($tmp);
        return lexiquest_get_fallback_image_url();
    }
    // 3. Tagging: Add keyword as tag and meta
    wp_set_post_terms($id, [$keyword], 'post_tag', true);
    update_post_meta($id, '_lexiquest_pixabay_keyword', $keyword);
    update_post_meta($id, '_lexiquest_pixabay_source_url', $image_url);
    $local_url = wp_get_attachment_url($id);
    error_log('LexiQuest: Pixabay image sideloaded successfully. Local URL: ' . $local_url);
    return $local_url;
}
// --- End LexiQuest Helpers ---