<?php
/*
Plugin Name: LexiQuest Story Archive
Description: Archives all generated LexiQuest stories, images, and quizzes in the database, with AI-generated categories. Includes admin UI for management.
Version: 1.0.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Main plugin class
class LexiQuest_Story_Archive {
    public function __construct() {
        add_action('init', [$this, 'register_story_post_type']);
        add_action('init', [$this, 'register_story_category_taxonomy']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function register_story_post_type() {
        register_post_type('lexiquest_story', [
            'labels' => [
                'name' => 'LexiQuest Stories',
                'singular_name' => 'LexiQuest Story',
            ],
            'public' => false,
            'show_ui' => true,
            'has_archive' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'menu_icon' => 'dashicons-book',
        ]);
    }

    public function register_story_category_taxonomy() {
        register_taxonomy('lexiquest_story_category', 'lexiquest_story', [
            'labels' => [
                'name' => 'Story Categories',
                'singular_name' => 'Story Category',
            ],
            'public' => false,
            'show_ui' => true,
            'hierarchical' => true,
        ]);
    }

    public function add_admin_menu() {
        add_menu_page(
            'LexiQuest Story Archive',
            'Story Archive',
            'manage_options',
            'lexiquest-story-archive',
            [$this, 'render_admin_page'],
            'dashicons-book',
            26
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>LexiQuest Story Archive</h1>';
        echo '<p>All generated stories, images, quizzes, and categories are archived here.</p>';
        // Filter/search form
        echo '<form method="get" style="margin-bottom:1em;">';
        echo '<input type="hidden" name="page" value="lexiquest-story-archive">';
        echo '<input type="text" name="s" placeholder="Search by title..." value="' . esc_attr($_GET['s'] ?? '') . '" style="margin-right:1em;">';
        // Category filter
        $terms = get_terms(['taxonomy'=>'lexiquest_story_category','hide_empty'=>false]);
        echo '<select name="category"><option value="">All Categories</option>';
        foreach($terms as $term) {
            $selected = (isset($_GET['category']) && $_GET['category']==$term->slug) ? 'selected' : '';
            echo '<option value="'.esc_attr($term->slug).'" '.$selected.'>' . esc_html($term->name) . '</option>';
        }
        echo '</select>';
        echo '<button type="submit" class="button">Filter</button>';
        echo '</form>';
        // Query stories
        $args = [
            'post_type' => 'lexiquest_story',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        if (!empty($_GET['s'])) {
            $args['s'] = sanitize_text_field($_GET['s']);
        }
        if (!empty($_GET['category'])) {
            $args['tax_query'] = [[
                'taxonomy'=>'lexiquest_story_category',
                'field'=>'slug',
                'terms'=>sanitize_text_field($_GET['category'])
            ]];
        }
        $q = new WP_Query($args);
        echo '<table class="widefat fixed striped"><thead><tr><th>Title</th><th>Categories</th><th>Date</th><th>Source</th><th>Actions</th></tr></thead><tbody>';
        if ($q->have_posts()) {
            while($q->have_posts()) { $q->the_post();
                $cats = get_the_terms(get_the_ID(), 'lexiquest_story_category');
                $catnames = $cats && !is_wp_error($cats) ? implode(', ', wp_list_pluck($cats, 'name')) : '-';
                echo '<tr>';
                echo '<td><strong>' . esc_html(get_the_title()) . '</strong></td>';
                echo '<td>' . esc_html($catnames) . '</td>';
                echo '<td>' . esc_html(get_the_date()) . '</td>';
                $source = get_post_meta(get_the_ID(), '_lexiquest_source', true);
                $source_label = $source ? esc_html(ucfirst($source)) : '<span style="color:#888">Unknown</span>';
                echo '<td>' . $source_label . '</td>';
                echo '<td><a href="#" class="button view-story-details" data-id="'.get_the_ID().'">View</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5"><em>No stories found.</em></td></tr>';
        }
        echo '</tbody></table>';
        wp_reset_postdata();
        // Modal for details (JS powered)
        echo '<div id="lq-story-modal" style="display:none;position:fixed;top:10vh;left:50%;transform:translateX(-50%);background:#fff;max-width:700px;width:90%;padding:2em;z-index:9999;box-shadow:0 8px 40px rgba(0,0,0,0.25);border-radius:12px;overflow:auto;max-height:80vh;"></div>';
        ?>
        <script>
        document.addEventListener("DOMContentLoaded",function(){
            document.querySelectorAll(".view-story-details").forEach(function(btn){
                btn.addEventListener("click",function(e){
                    e.preventDefault();
                    var pid = this.getAttribute("data-id");
                    fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=lexiquest_story_details&id="+pid)
                        .then(r=>r.text()).then(function(html){
                            var modal=document.getElementById("lq-story-modal");
                            modal.innerHTML = html + '<br><button onclick="document.getElementById(\'lq-story-modal\').style.display=\'none\'" class="button">Close</button>';
                            modal.style.display = 'block';
                        });
                });
            });
        });
        </script>
        <?php
        echo '</div>';
    }
}

new LexiQuest_Story_Archive();

// Register REST API endpoint for archiving stories
add_action('rest_api_init', function() {
    register_rest_route('lexiquest/v1', '/archive', [
        'methods' => 'POST',
        'callback' => 'lexiquest_rest_archive_story',
        'permission_callback' => function($request) {
            // Require nonce for frontend, or logged-in for admin
            $nonce = $request->get_header('X-WP-Nonce');
            if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
                return true;
            }
            return current_user_can('edit_posts');
        },
        'args' => [
            'title' => ['required' => true],
            'text' => ['required' => true],
            'images' => ['required' => false],
            'quiz' => ['required' => false],
            'categories' => ['required' => false],
        ]
    ]);
});

function lexiquest_rest_archive_story($request) {
    $params = $request->get_json_params();
    if (empty($params['title']) || empty($params['text'])) {
        return new WP_Error('missing_fields', 'Title and text are required.', ['status' => 400]);
    }
    $story_data = [
        'title' => sanitize_text_field($params['title']),
        'text' => is_array($params['text']) ? array_map('sanitize_text_field', $params['text']) : sanitize_text_field($params['text']),
        'images' => isset($params['images']) ? $params['images'] : [],
        'quiz' => isset($params['quiz']) ? $params['quiz'] : [],
        'categories' => isset($params['categories']) ? array_map('sanitize_text_field', $params['categories']) : [],
    ];
    $post_id = lexiquest_archive_story($story_data);
    if ($post_id) {
        return ['success' => true, 'post_id' => $post_id];
    } else {
        return new WP_Error('archive_failed', 'Failed to archive story.', ['status' => 500]);
    }
}

// Utility: Save story, images, quiz, and categories
function lexiquest_archive_story($story_data) {
    // $story_data: ['title', 'text', 'images', 'quiz', 'categories']
    $post_id = wp_insert_post([
        'post_type' => 'lexiquest_story',
        'post_title' => $story_data['title'],
        'post_content' => is_array($story_data['text']) ? implode("\n\n", $story_data['text']) : $story_data['text'],
        'post_status' => 'publish',
    ]);
    if (!$post_id) return false;

    // Set images and quiz as post meta
    if (!empty($story_data['images'])) {
        update_post_meta($post_id, '_lexiquest_images', $story_data['images']);
    }
    if (!empty($story_data['quiz'])) {
        update_post_meta($post_id, '_lexiquest_quiz', $story_data['quiz']);
    }

    // AI-generate and assign categories
    if (!empty($story_data['categories'])) {
        foreach ($story_data['categories'] as $cat) {
            // Check if category exists, if not, create it
            $term = term_exists($cat, 'lexiquest_story_category');
            if (!$term) {
                $term = wp_insert_term($cat, 'lexiquest_story_category');
            }
        }
        wp_set_object_terms($post_id, $story_data['categories'], 'lexiquest_story_category', false);
    }
    // Save source (ajax/rest) as post meta if provided
    if (!empty($story_data['source'])) {
        update_post_meta($post_id, '_lexiquest_source', $story_data['source']);
    }
    return $post_id;
}

// AJAX handler for viewing story details in the admin modal
add_action('wp_ajax_lexiquest_story_details', function() {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) die('<em>Invalid story ID.</em>');
    $post = get_post($id);
    if (!$post || $post->post_type !== 'lexiquest_story') die('<em>Story not found.</em>');
    echo '<h2>' . esc_html($post->post_title) . '</h2>';
    $cats = get_the_terms($id, 'lexiquest_story_category');
    if ($cats && !is_wp_error($cats)) {
        echo '<p><strong>Categories:</strong> ' . esc_html(implode(', ', wp_list_pluck($cats, 'name'))) . '</p>';
    }
    echo '<p><strong>Date:</strong> ' . esc_html(get_the_date('', $id)) . '</p>';
    echo '<hr>';
    $content = apply_filters('the_content', $post->post_content);
    echo '<div style="margin-bottom:1em;">' . $content . '</div>';
    $images = get_post_meta($id, '_lexiquest_images', true);
    if ($images && is_array($images)) {
        echo '<div style="margin-bottom:1em;"><strong>Images:</strong><br>';
        foreach($images as $label=>$url) {
            echo '<div style="margin:0.5em 0;"><em>'.esc_html(ucfirst($label)).':</em> <img src="'.esc_url($url).'" alt="'.esc_attr($label).'" style="max-width:180px;vertical-align:middle;margin-left:1em;"></div>';
        }
        echo '</div>';
    }
    $quiz = get_post_meta($id, '_lexiquest_quiz', true);
    if ($quiz && is_array($quiz) && !empty($quiz['questions'])) {
        echo '<div style="margin-bottom:1em;"><strong>Quiz:</strong>';
        if (!empty($quiz['title'])) echo '<div><em>' . esc_html($quiz['title']) . '</em></div>';
        echo '<ol>';
        foreach($quiz['questions'] as $q) {
            echo '<li><strong>' . esc_html($q['question']) . '</strong><br>';
            if (!empty($q['choices'])) {
                echo 'Choices: ' . esc_html(implode(', ', $q['choices'])) . '<br>';
            }
            echo 'Answer: <span style="color:#155724;font-weight:bold;">' . esc_html($q['answer']) . '</span><br>';
            if (!empty($q['explanation'])) {
                echo '<span style="color:#888;">' . esc_html($q['explanation']) . '</span>';
            }
            echo '</li>';
        }
        echo '</ol></div>';
    }
    die();
});

