<?php
/*
Plugin Name: LexiQuest Core
Description: Core functionality for LexiQuest AI (CPTs, roles, settings, DB tables, and global settings)
Version: 0.1.0
Author: Ronald Allan Rivera
*/
/**
 * LexiQuest Core Plugin
 *
 * This plugin registers all custom post types, user roles, global settings, and custom database tables
 * required by the LexiQuest AI suite. It is the foundational module for all other LexiQuest plugins.
 *
 * @package   LexiQuest
 * @author    Ronald Allan Rivera
 * @copyright 2025
 * @license   GPL-2.0+
 * @since     0.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main class for LexiQuest Core functionality.
 *
 * Handles registration of CPTs, roles, settings, and custom DB tables.
 *
 * @since 0.1.0
 */
class LexiQuest_Core {
    /**
     * LexiQuest_Core constructor.
     *
     * Hooks all core actions for CPTs, roles, settings, and DB tables.
     *
     * @since 0.1.0
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_custom_post_types' ] );
        add_action( 'init', [ $this, 'register_custom_roles' ] );
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        register_activation_hook( __FILE__, [ $this, 'create_custom_tables' ] );
    }

    /**
     * Create custom database tables for user scores and book assignments
     */
    /**
     * Creates custom DB tables for user scores and book assignments on plugin activation.
     *
     * @since 0.1.0
     */
    public function create_custom_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $user_scores = $wpdb->prefix . 'litpro_user_scores';
        $book_assignments = $wpdb->prefix . 'litpro_book_assignments';

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql1 = "CREATE TABLE $user_scores (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            quiz_id BIGINT UNSIGNED NOT NULL,
            score INT NOT NULL,
            lexile_level INT DEFAULT NULL,
            taken_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY quiz_id (quiz_id)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE $book_assignments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            book_id BIGINT UNSIGNED NOT NULL,
            assigned_to BIGINT UNSIGNED NOT NULL,
            assigned_by BIGINT UNSIGNED DEFAULT NULL,
            due_date DATE DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'assigned',
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY book_id (book_id),
            KEY assigned_to (assigned_to)
        ) $charset_collate;";

        dbDelta( $sql1 );
        dbDelta( $sql2 );
    }

    /**
     * Registers all LexiQuest custom post types.
     *
     * @since 0.1.0
     */
    public function register_custom_post_types() {
        // AI Story
        register_post_type( 'ai_story', [
            'label' => 'AI Stories',
            'public' => true,
            'show_in_menu' => true,
            'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'menu_icon' => 'dashicons-book',
        ] );
        // Quiz
        register_post_type( 'quiz', [
            'label' => 'Quizzes',
            'public' => true,
            'show_in_menu' => true,
            'supports' => [ 'title', 'editor', 'custom-fields' ],
            'menu_icon' => 'dashicons-welcome-learn-more',
        ] );
        // Student Profile
        register_post_type( 'student_profile', [
            'label' => 'Student Profiles',
            'public' => false,
            'show_ui' => true,
            'supports' => [ 'title', 'custom-fields' ],
            'menu_icon' => 'dashicons-id',
        ] );
        // Book
        register_post_type( 'book', [
            'label' => 'Books',
            'public' => true,
            'show_in_menu' => true,
            'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields' ],
            'menu_icon' => 'dashicons-book-alt',
        ] );
        // Teacher
        register_post_type( 'teacher', [
            'label' => 'Teachers',
            'public' => false,
            'show_ui' => true,
            'supports' => [ 'title', 'custom-fields' ],
            'menu_icon' => 'dashicons-businessperson',
        ] );
        // Class
        register_post_type( 'class', [
            'label' => 'Classes',
            'public' => false,
            'show_ui' => true,
            'supports' => [ 'title', 'custom-fields' ],
            'menu_icon' => 'dashicons-groups',
        ] );
    }

    /**
     * Registers LexiQuest custom user roles.
     *
     * @since 0.1.0
     */
    public function register_custom_roles() {
        add_role( 'lexiquest_student', 'Student', [ 'read' => true ] );
        add_role( 'lexiquest_teacher', 'Teacher', [ 'read' => true, 'edit_posts' => true ] );
        // Optionally, add capabilities as needed.
    }

    /**
     * Adds LexiQuest settings page to the WordPress admin.
     *
     * @since 0.1.0
     */
    public function add_settings_page() {
        add_options_page(
            'LexiQuest Settings',
            'LexiQuest Settings',
            'manage_options',
            'lexiquest-settings',
            [ $this, 'settings_page_html' ]
        );
    }

    /**
     * Registers LexiQuest settings for API keys and preferences.
     *
     * @since 0.1.0
     */
    public function register_settings() {
        register_setting( 'lexiquest_settings', 'lexiquest_openai_api_key' );
        register_setting( 'lexiquest_settings', 'lexiquest_image_api_key' );
        register_setting( 'lexiquest_settings', 'lexiquest_image_api_provider' );
    }

    /**
     * Renders the LexiQuest settings page HTML.
     *
     * @since 0.1.0
     */
    public function settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap">
            <h1>LexiQuest API Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'lexiquest_settings' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">OpenAI API Key</th>
                        <td><input type="text" name="lexiquest_openai_api_key" value="<?php echo esc_attr( get_option('lexiquest_openai_api_key') ); ?>" size="50" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Image API Provider</th>
                        <td>
                            <select name="lexiquest_image_api_provider">
                                <?php $provider = esc_attr( get_option('lexiquest_image_api_provider', 'unsplash') ); ?>
                                <option value="unsplash" <?php selected( $provider, 'unsplash' ); ?>>Unsplash</option>
                                <option value="pexels" <?php selected( $provider, 'pexels' ); ?>>Pexels</option>
                                <option value="pixabay" <?php selected( $provider, 'pixabay' ); ?>>Pixabay</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Image API Key</th>
                        <td><input type="text" name="lexiquest_image_api_key" value="<?php echo esc_attr( get_option('lexiquest_image_api_key') ); ?>" size="50" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new LexiQuest_Core();

