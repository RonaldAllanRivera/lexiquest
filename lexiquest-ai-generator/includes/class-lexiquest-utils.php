<?php
/**
 * LexiQuest Utilities
 * Helper functions for validation, logging, etc.
 */
class LexiQuest_Utils {
    /**
     * Validate and sanitize form data.
     */
    public static function sanitize_form_data($data) {
        return [
            'lexile' => isset($data['lexile']) ? intval($data['lexile']) : 0,
            'grade' => isset($data['grade']) ? intval($data['grade']) : 0,
            'interests' => isset($data['interests']) ? sanitize_text_field($data['interests']) : '',
            'story_title' => isset($data['story_title']) ? sanitize_text_field($data['story_title']) : '',
        ];
    }

    /**
     * Log errors if WP_DEBUG is enabled.
     */
    public static function log($msg) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($msg);
        }
    }

    /**
     * Simple array filter for non-empty values.
     */
    public static function array_filter_non_empty($arr) {
        return array_filter($arr, function($v) { return !empty($v); });
    }
}
