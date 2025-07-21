<?php
/**
 * LexiQuest AI Logic
 * Handles OpenAI prompt, parsing, and story/quiz generation.
 */
class LexiQuest_AI {
    /**
     * Build the OpenAI prompt for safe, age-appropriate story and quiz generation.
     */
    public static function build_prompt($lexile, $grade, $theme) {
        return "You are an expert children's author and educator. Write an original, positive, age-appropriate story for a student in grade {$grade} with a Lexile level of {$lexile}. The story must be safe for children: no violence, fear, bullying, or inappropriate content. The story should be about {$theme}. If the story title or theme is not familiar or not a known story, ALWAYS create an original story with that title or theme. Never refuse or output an error—always generate a story, even if you have to invent it. Length: about 300 words. After the story, create 5 multiple-choice comprehension questions (4 options each), with the correct answer and a 1-sentence explanation. Output MUST be valid JSON with these keys: story_title, story_text, quiz_title, questions (array of question, choices, answer, explanation). If you cannot answer, output a JSON error object: {\"error\": \"reason\"}.";
    }

    /**
     * Call the OpenAI API and return parsed result or error.
     */
    public static function generate_story_and_quiz($lexile, $grade, $theme) {
        $prompt = self::build_prompt($lexile, $grade, $theme);
        error_log('LexiQuest DEBUG: OpenAI prompt: ' . $prompt);
        $openai_key = get_option('lexiquest_openai_api_key');
        error_log('LexiQuest DEBUG: OpenAI key is ' . ($openai_key ? 'SET' : 'NOT SET'));
        if (!$openai_key) {
            error_log('LexiQuest ERROR: OpenAI API key not set.');
            return ['error' => 'OpenAI API key not set.'];
        }
        error_log('LexiQuest DEBUG: About to call OpenAI API');
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
        error_log('LexiQuest DEBUG: OpenAI API call complete');
        error_log('LexiQuest DEBUG: Raw OpenAI API response: ' . print_r($openai_response, true));
        if (is_wp_error($openai_response)) {
            error_log('LexiQuest ERROR: OpenAI API wp_remote_post error: ' . $openai_response->get_error_message());
            return ['error' => $openai_response->get_error_message()];
        }
        $body_str = wp_remote_retrieve_body($openai_response);
        error_log('LexiQuest DEBUG: OpenAI API response body: ' . $body_str);
        if (!$body_str) {
            error_log('LexiQuest ERROR: OpenAI API response body is empty');
            return ['error' => 'OpenAI API response body is empty'];
        }
        $body = json_decode($body_str, true);
        if (!is_array($body)) {
            error_log('LexiQuest ERROR: OpenAI API response body is not an array: ' . $body_str);
            return ['error' => 'OpenAI API response body is not valid JSON'];
        }
        if (isset($body['choices'][0]['message']['content'])) {
            $json_str = $body['choices'][0]['message']['content'];
            error_log('LexiQuest DEBUG: OpenAI message content: ' . $json_str);
            $json = json_decode($json_str, true);
            if ($json && isset($json['error'])) {
                error_log('LexiQuest OpenAI error: ' . $json['error']);
                return ['error' => 'OpenAI error: ' . $json['error']];
            } elseif ($json && isset($json['story_title'], $json['story_text'], $json['questions'])) {
                error_log('LexiQuest DEBUG: Successfully parsed OpenAI story/quiz JSON');
                return [
                    'story' => [
                        'title' => $json['story_title'],
                        'text' => $json['story_text'],
                    ],
                    'quiz' => [
                        'title' => $json['quiz_title'] ?? 'Comprehension Quiz',
                        'questions' => $json['questions'],
                    ]
                ];
            } else {
                error_log('LexiQuest ERROR: OpenAI response could not be parsed as expected. Raw message content: ' . $json_str);
                return ['error' => 'OpenAI response could not be parsed as expected.'];
            }
        } else {
            error_log('LexiQuest ERROR: No content returned from OpenAI. Full body: ' . print_r($body, true));
            return ['error' => 'No content returned from OpenAI.'];
        }
    }
}
