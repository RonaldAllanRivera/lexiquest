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
        return "You are an expert children's author and educator. Write an original, positive, age-appropriate story for a student in grade {$grade} with a Lexile level of {$lexile}. The story must be safe for children: no violence, fear, bullying, or inappropriate content. The story should be about {$theme}.\n\n- The story must be about 1000 words, split into 4 or 5 paragraphs.\n- For each paragraph, provide a short and clear image prompt/description that best represents the content or main idea of that paragraph. Do NOT include actual images, only a description per paragraph.\n- Output the story as a JSON object with these keys:\n  - \"story_title\": (string)\n  - \"story_text\": (array of 4–5 strings, each string is a paragraph)\n  - \"paragraph_images\": (array of 4–5 strings, each is an image prompt/description for the corresponding paragraph)\n  - \"quiz_title\": (string)\n  - \"questions\": (array of question, choices, answer, explanation)\nIf you cannot answer, output a JSON error object: {\"error\": \"reason\"}.";
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
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful, safe, age-appropriate children\'s story and quiz generator.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 1200,
                'temperature' => 0.8,
            ]),
            'timeout' => 30,
        ]);
        error_log('LexiQuest DEBUG: OpenAI API call complete');
        error_log('LexiQuest DEBUG: Raw OpenAI API response: ' . print_r($openai_response, true));
        if (is_wp_error($openai_response)) {
            error_log('LexiQuest ERROR: OpenAI API wp_remote_post error: ' . $openai_response->get_error_message());
            // Hard fallback story
            return self::get_fallback_story('API error: ' . $openai_response->get_error_message());
        }
        $body_str = wp_remote_retrieve_body($openai_response);
        error_log('LexiQuest DEBUG: OpenAI API response body: ' . $body_str);
        if (!$body_str) {
            error_log('LexiQuest ERROR: OpenAI API response body is empty');
            return self::get_fallback_story('API response body is empty');
        }
        $body = json_decode($body_str, true);
        if (!is_array($body)) {
            error_log('LexiQuest ERROR: OpenAI API response body is not an array: ' . $body_str);
            // Retry with a simpler prompt (single paragraph, no quiz, no images)
            $simple_prompt = "Write a fun, safe, age-appropriate story for a child. Make it positive, about 200 words, no quiz, no images, just the story.";
            $retry_response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openai_key,
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful, safe, age-appropriate children\'s story generator.'],
                        ['role' => 'user', 'content' => $simple_prompt],
                    ],
                    'max_tokens' => 400,
                    'temperature' => 0.8,
                ]),
                'timeout' => 20,
            ]);
            $retry_body_str = wp_remote_retrieve_body($retry_response);
            error_log('LexiQuest DEBUG: OpenAI retry response body: ' . $retry_body_str);
            $retry_body = json_decode($retry_body_str, true);
            if (isset($retry_body['choices'][0]['message']['content'])) {
                $raw_story = trim($retry_body['choices'][0]['message']['content']);
                return [
                    'story' => [
                        'title' => 'A Fun Story',
                        'text' => [$raw_story],
                        'paragraph_images' => [],
                    ],
                    'quiz' => [
                        'title' => '',
                        'questions' => [],
                    ]
                ];
            }
            // Final fallback
            return self::get_fallback_story('Retry parse failed');
        }
        if (isset($body['choices'][0]['message']['content'])) {
            $json_str = $body['choices'][0]['message']['content'];
            error_log('LexiQuest DEBUG: OpenAI message content: ' . $json_str);
            $json = json_decode($json_str, true);
            if ($json && isset($json['error'])) {
                error_log('LexiQuest OpenAI error: ' . $json['error']);
                return self::get_fallback_story('OpenAI error: ' . $json['error']);
            } elseif ($json && isset($json['story_title'], $json['story_text'], $json['questions'])) {
                error_log('LexiQuest DEBUG: Successfully parsed OpenAI story/quiz JSON');
                return [
                    'story' => [
                        'title' => $json['story_title'],
                        'text' => $json['story_text'],
                        'paragraph_images' => $json['paragraph_images'] ?? [],
                    ],
                    'quiz' => [
                        'title' => $json['quiz_title'] ?? 'Comprehension Quiz',
                        'questions' => $json['questions'],
                    ]
                ];
            } elseif (!empty($json_str)) {
                // If JSON parse fails but raw text exists, use as single-paragraph story
                return [
                    'story' => [
                        'title' => 'A Fun Story',
                        'text' => [$json_str],
                        'paragraph_images' => [],
                    ],
                    'quiz' => [
                        'title' => '',
                        'questions' => [],
                    ]
                ];
            } else {
                error_log('LexiQuest ERROR: OpenAI response could not be parsed as expected. Raw message content: ' . $json_str);
                return self::get_fallback_story('OpenAI parse failed, no text');
            }
        } else {
            error_log('LexiQuest ERROR: No content returned from OpenAI. Full body: ' . print_r($body, true));
            return ['error' => 'No content returned from OpenAI.'];
        }
    }

    /**
     * Always return a hardcoded fallback story for kids if AI fails.
     */
    public static function get_fallback_story($reason = '') {
        error_log('LexiQuest FALLBACK: Returning default story. Reason: ' . $reason);
        $default_story = [
            'story' => [
                'title' => 'The Magical Playground',
                'text' => [
                    "Once upon a time, in a colorful playground, children from all around came to play and make friends. There were slides, swings, and a sandbox full of hidden treasures.",
                    "One sunny day, Mia and her puppy Max discovered a sparkling key buried in the sand. Curious, they asked their friends to help search for what it might unlock.",
                    "Together, they found a tiny, magical door behind the big oak tree. Using the key, they opened it and entered a world of bouncing clouds and giggling butterflies.",
                    "After a day of magical adventures, everyone returned to the playground, promising to always help each other and share their discoveries.",
                ],
                'paragraph_images' => [
                    '', '', '', ''
                ],
            ],
            'quiz' => [
                'title' => 'Quiz Time!',
                'questions' => [
                    [
                        'question' => 'What did Mia and Max find in the sandbox?',
                        'choices' => ['A sparkling key', 'A red ball', 'A golden coin', 'A magic wand'],
                        'answer' => 'A sparkling key',
                        'explanation' => 'Mia and Max found a sparkling key buried in the sand.'
                    ],
                    [
                        'question' => 'Where was the magical door?',
                        'choices' => ['In the sandbox', 'Behind the big oak tree', 'On the slide', 'In the clouds'],
                        'answer' => 'Behind the big oak tree',
                        'explanation' => 'The magical door was hidden behind the big oak tree.'
                    ],
                ],
            ]
        ];
        return $default_story;
    }
}

