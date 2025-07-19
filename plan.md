# Scholastic Literacy Pro Clone Plan

## Notes
- The project is a WordPress-based clone of Scholastic Literacy Pro, focusing on adaptive reading assessment, personalized recommendations, reading tracking, and gamification.
- The system will use a modular plugin architecture with 5 plugins: core, AI generator, image fetcher, quiz system, and library UI.
- Core features include user roles (admin, teacher, student), Lexile-based assessments, book library, reading/quiz workflow, gamification, dashboards, admin panel, notifications, and optional parent portal.
- AI integration will generate stories, quizzes, and estimate Lexile levels using OpenAI API.
- Free image APIs (Unsplash, Pexels, Pixabay) will be supported, with admin-selectable source.
- Security: API keys stored securely, rate limits on AI generation, and public/private content toggles.
- Plugin names (lexiquest-core, lexiquest-ai-generator, etc.) are confirmed by the user.
- User is seeking advice on whether to start with folder structure/base plugin files or focus on a specific module.
- Core plugin now implements CPTs (ai_story, quiz, student_profile), custom roles, and admin settings page for API keys (admin-only access).
- All major CPTs (ai_story, quiz, student_profile, book, teacher, class) are now registered in the core plugin.
- Custom post types: book, quiz, student, teacher, class, ai_story.
- Custom tables for performance: user scores, book assignments.
- Frontend: React/Vue components or shortcodes; Backend: REST API endpoints.
- Data export (PDF/CSV), gamification engine for badges, and extensibility for future features (multi-language, TTS, etc).
- Project-level FEATURES.md and plan.md are the only maintained documentation files for features and planning; per-plugin documentation is NOT required or maintained. All updates and feature tracking should be consolidated in these root files.
- Documentation standards: update only FEATURES.md, plan.md, README.md, and CHANGELOG.md in the root/plugins directory. Do not create or maintain per-plugin docs or changelogs.

## Task List
- [x] Scaffold folder structure and base plugin files
- [x] Develop core plugin: CPTs, roles, and settings page
- [x] Add CPTs: book, teacher, class
- [x] Implement custom database tables for user scores and book assignments
- [x] Analyze and document all required features and modules
- [x] Design the modular plugin architecture and plugin responsibilities
- [x] Define custom post types and custom database tables
- [ ] Plan AI integration for story and quiz generation
- [ ] Plan image API integration and admin settings
- [ ] Plan user roles and onboarding flows
- [ ] Plan frontend UI/UX components and dashboard layouts
- [ ] Plan backend REST API endpoints and logic
- [ ] Plan security, rate limiting, and data export features
- [ ] Maintain and update MANUAL_TEST_CHECKLIST.md and PLUGIN_ARCHITECTURE.md as project evolves

## Current Goal
Plan AI integration for story and quiz generation

---

### AI Integration Planning (Story & Quiz Generation)

**Requirements:**
- Use OpenAI API (GPT-4 or later) to generate:
  - Original reading passages/stories (age/grade-appropriate, Lexile-leveled)
  - Comprehension quizzes (multiple choice, short answer, etc.)
- Admin can trigger story/quiz generation from the backend (future: allow teacher/student triggers)
- Generated content is saved to custom post types (`ai_story`, `quiz`)
- Support for prompt customization (theme, grade, genre, length, etc.)

**API Usage:**
- Store OpenAI API key securely (already handled in settings)
- Use server-side requests to avoid exposing keys
- Handle rate limiting, error reporting, and retries gracefully

**Security:**
- Only authenticated users (admin/teacher) can generate content
- Enforce usage limits to prevent abuse

**Extensibility:**
- Allow for future support of other AI providers (Anthropic, Gemini, etc.)
- Modularize prompts and result parsing for easy updates

**Next Steps & Detailed Steps:**

1. **Design Admin UI for AI Generation**
   - Add a submenu page under LexiQuest or Tools for "AI Story/Quiz Generator"
   - UI fields:
     - Content type (Story/Quiz)
     - Grade/age level (dropdown)
     - Lexile range (input)
     - Theme/genre (dropdown or text)
     - Story length/quiz length (input)
     - Prompt preview (readonly)
     - Generate button
   - Display loader/spinner and show results or errors inline
   - Save generated content directly to CPTs or provide review/edit before saving

2. **Define Data Structure for Prompts & Results**
   - Prompts:
     - type: story | quiz
     - grade_level: int/string
     - lexile_range: string/int
     - theme: string
     - length: int (words/questions)
     - custom_instructions: string (optional)
   - Results (Story):
     - story_title: string
     - story_text: string
     - lexile_estimate: int
     - ai_metadata: array
   - Results (Quiz):
     - quiz_title: string
     - questions: array (question, choices, answer, explanation)
     - ai_metadata: array

3. **Implement Server-side API Integration & Error Handling**
   - Use WordPress AJAX or REST endpoint for generation requests
   - Validate user permissions (admin/teacher only)
   - Build OpenAI API request with prompt structure
   - Handle API errors, rate limits, and retries
   - Return structured results for UI display and CPT creation

4. **Document Endpoints & Flows in PLUGIN_ARCHITECTURE.md**
   - List all REST endpoints and their payloads
   - Document UI-to-server and server-to-OpenAI flow
   - Note error handling and extensibility hooks