<?php
/**
 * LexiQuest Core Class
 * Handles plugin init, hooks, and dependency loading.
 */
class LexiQuestAIGenerator_Core {
    public static function init() {
        // Register tags for attachments
        add_action('init', function() {
            register_taxonomy_for_object_type('post_tag', 'attachment');
        });
        // Register shortcodes and assets
        add_action('init', [__CLASS__, 'register_shortcodes_and_assets']);
        // Register AJAX and REST endpoints
        LexiQuest_AJAX::register_endpoints();
    }
    public static function register_shortcodes_and_assets() {
        add_shortcode('lexiquest_student_ui', [__CLASS__, 'student_ui_shortcode']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    public static function student_ui_shortcode($atts) {
        return '<div id="lexiquest-student-ui"></div>';
    }
    public static function enqueue_assets() {
        $plugin_url = plugin_dir_url(dirname(__FILE__));
        wp_enqueue_style('lexiquest-student-ui-css', $plugin_url . 'assets/student-ui.css', [], '1.0.0');
        wp_enqueue_script('lexiquest-student-ui-js', $plugin_url . 'assets/student-ui.js', ['jquery'], '1.0.0', true);
            wp_localize_script('lexiquest-student-ui-js', 'lexiquest_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('lexiquest_ajax_nonce'),
                'is_admin' => is_admin(),
            ]);
    }
}

