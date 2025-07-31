<?php
/**
 * LexiQuest REST API Handler
 *
 * Registers and handles all REST API endpoints for LexiQuest AI Generator.
 * This class is separated from AJAX/archive logic for modularity and stability.
 */
class LexiQuest_REST {
    /**
     * Register REST API endpoints.
     */
    public static function register_endpoints() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    /**
     * Register all LexiQuest REST API routes.
     */
    public static function register_routes() {
        register_rest_route('lexiquest/v1', '/generate', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_generate_content'],
            'permission_callback' => [__CLASS__, 'permission_check'],
        ]);
        // Add future REST routes here if needed
    }

    /**
     * Permission callback for REST API.
     * Harden for production as needed.
     */
    public static function permission_check($request) {
        // For local dev, allow all logged-in users. Harden for production.
        return is_user_logged_in();
    }

    /**
     * Main handler for AI-powered content generation (REST API).
     * Mirrors AJAX logic but does not interfere with it.
     */
    public static function handle_generate_content($request) {
        // Use the same logic as LexiQuest_AJAX::rest_generate_content
        if (method_exists('LexiQuest_AJAX', 'rest_generate_content')) {
            return call_user_func(['LexiQuest_AJAX', 'rest_generate_content'], $request);
        }
        return new WP_Error('not_implemented', 'REST generation not implemented.', ['status' => 501]);
    }
}

// Register endpoints on plugin load
LexiQuest_REST::register_endpoints();
