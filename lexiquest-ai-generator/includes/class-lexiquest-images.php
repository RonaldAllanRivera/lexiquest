<?php
/**
 * LexiQuest Image Logic
 * Handles Pixabay API, media library, and fallback image logic.
 */
class LexiQuest_Images {
    /**
     * Returns a safe-for-kids keyword from interests or a default.
     */
    public static function get_safe_kids_keyword($interests, $story_title = '') {
        $whitelist = [
            'animals','nature','reading','books','school','sports','adventure','science','art','music','friendship','kindness','history','space','math','technology','robot','garden','tree','forest','mountain','ocean','sea','river','flower','insect','bird','dog','cat','horse','dinosaur','transportation','train','car','plane','boat','exploration','discovery','imagination','fun','learning','play','children','kids','student','story','library','teacher','classroom','puzzle','game','drawing','painting','craft','lego','block','magic','superhero','princess','castle','knight','dragon','pirate','detective','mystery','holiday','festival','celebration','family','community','help','respect','courage'
        ];
        if ($story_title) {
            $title = strtolower($story_title);
            foreach ($whitelist as $safe) {
                if (strpos($title, $safe) !== false) {
                    return $safe;
                }
            }
            return $title;
        }
        $interests = strtolower($interests);
        $interestArr = preg_split('/[\s,;]+/', $interests);
        foreach ($interestArr as $interest) {
            if (in_array($interest, $whitelist)) {
                return $interest;
            }
        }
        return 'children books';
    }

    /**
     * Returns the local fallback image URL from plugin assets.
     */
    public static function get_fallback_image_url() {
        $fallback_rel = 'wp-content/plugins/lexiquest-ai-generator/assets/fallback.jpg';
        $fallback_abs = ABSPATH . $fallback_rel;
        $fallback_url = site_url(str_replace(ABSPATH, '', $fallback_abs));
        if (file_exists($fallback_abs)) {
            return $fallback_url;
        }
        return plugin_dir_url(__FILE__) . '../assets/fallback.jpg';
    }

    /**
     * Search the Media Library for an attachment matching the keyword/tag.
     * Returns the URL if found, or false if not found.
     */
    public static function find_media_library_image($keyword) {
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
        ];
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            $attachment = $query->posts[0];
            return wp_get_attachment_url($attachment->ID);
        }
        return false;
    }

    /**
     * Downloads a random Pixabay image for a given keyword and saves it to the Media Library.
     * Returns the local URL or false on failure. Deduplication by tag and source URL.
     */
    public static function fetch_and_save_pixabay_image($keyword, $story_title = '') {
        error_log('LexiQuest: Attempting to fetch Pixabay image for keyword: ' . $keyword);
        // 1. Deduplication: Search Media Library first
        $existing_url = self::find_media_library_image($keyword);
        if ($existing_url) {
            error_log('LexiQuest: Found existing Media Library image for keyword: ' . $keyword);
            return $existing_url;
        }
        $api_key = get_option('lexiquest_pixabay_api_key');
        if (!$api_key) {
            error_log('LexiQuest: Pixabay API key not set.');
            return self::get_fallback_image_url();
        }
        $safe_keyword = $keyword;
        $url = 'https://pixabay.com/api/?key=' . $api_key . '&q=' . urlencode($safe_keyword) . '&image_type=photo&orientation=horizontal&per_page=5&safesearch=true';
        $response = wp_remote_get($url, ['timeout' => 15]);
        if (is_wp_error($response)) {
            error_log('LexiQuest: Pixabay API error: ' . $response->get_error_message());
            return self::get_fallback_image_url();
        }
        $json = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($json['hits']) || !isset($json['hits'][0]['largeImageURL'])) {
            error_log('LexiQuest: No Pixabay image found for keyword: ' . $safe_keyword);
            $fallback_url = self::get_fallback_image_url();
            error_log('LexiQuest: No suitable image found. Returning static fallback asset: ' . $fallback_url);
            return $fallback_url;
        }
        $image_url = $json['hits'][0]['largeImageURL'];
        $fallback_url = self::get_fallback_image_url();
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
        // 3. Download and upload image
        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            error_log('LexiQuest: Error downloading image: ' . $tmp->get_error_message());
            return $fallback_url;
        }
        $file = [
            'name'     => basename($image_url),
            'type'     => 'image/jpeg',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp),
        ];
        // Ensure media_handle_sideload and dependencies are loaded
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
        $id = media_handle_sideload($file, 0);
        if (is_wp_error($id)) {
            error_log('LexiQuest: Error uploading image to Media Library: ' . $id->get_error_message());
            @unlink($tmp);
            return $fallback_url;
        }
        // Tag and add meta
        wp_set_post_terms($id, [$safe_keyword], 'post_tag', true);
        update_post_meta($id, '_lexiquest_pixabay_keyword', $safe_keyword);
        update_post_meta($id, '_lexiquest_pixabay_source_url', $image_url);
        $url = wp_get_attachment_url($id);
        error_log('LexiQuest: Uploaded new Pixabay image: ' . $url);
        return $url;
    }
}
