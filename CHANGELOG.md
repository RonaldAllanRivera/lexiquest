# Changelog

## 2025-07-31
- Added story source tracking (AJAX/REST) to all archived stories.
- Admin UI now displays a 'Source' column in the LexiQuest Story Archive for each story.
- Fixed REST API fatal error by ensuring WordPress media upload functions are loaded for REST requests.
- Improved reliability and diagnostics for story archiving and media uploads.

## 2025-07-19 (Core Plugin v0.1.0)
- Initial release of LexiQuest Core plugin:
  - Registration of all custom post types (ai_story, quiz, student_profile, book, teacher, class)
  - Custom user roles (Student, Teacher)
  - Admin settings page for API keys and preferences
  - Creation of custom DB tables for user scores and book assignments
  - Full PHPDoc documentation and maintainers’ notes
