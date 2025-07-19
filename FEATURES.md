# LexiQuest AI Suite – Features Overview

This document provides a high-level breakdown of all features and modules in the LexiQuest AI plugin suite for WordPress.

---

## Core Features (lexiquest-core)
- Registers all custom post types: `ai_story`, `quiz`, `student_profile`, `book`, `teacher`, `class`
- Registers custom roles: Student, Teacher
- Global admin settings for API keys and preferences
- Creates custom database tables for user scores and book assignments
- Provides secure admin-only settings page

## AI Generator (lexiquest-ai-generator)
- Integrates with OpenAI (GPT-4) for dynamic story and quiz generation
- Generates age-appropriate stories and quizzes
- Saves generated content to CPTs
- Rate-limits generation to prevent abuse

## Image Fetcher (lexiquest-image-fetcher)
- Connects to Unsplash, Pexels, or Pixabay APIs
- Fetches relevant images for stories/books
- Admin-selectable provider and API key

## Quiz System (lexiquest-quiz-system)
- Manages quiz questions, answers, and explanations
- Validates answers and tracks scores
- Records quiz history to custom table

## Library UI (lexiquest-library-ui)
- Displays stories/books in a searchable, filterable, kid-friendly library
- Shows previews, Lexile levels, illustrations, and quizzes
- Provides filters for Lexile, genre, interests, etc.

## Security & Analytics
- All sensitive settings are admin-only
- No API keys exposed on frontend
- Tracks reading progress, Lexile growth, and assignments
- Exports data as CSV/PDF

## Gamification
- Badges and achievements for milestones
- Progress tracker for students and classes

## Extensibility
- Multi-language support (future)
- Text-to-speech and accessibility (future)
- Mobile/PWA frontend (future)

## Documentation Standards
- This `FEATURES.md` is the only maintained features reference for the LexiQuest suite.
- Per-plugin documentation files are NOT required or maintained; all documentation should be consolidated here.
- All plugins should maintain a root `README.md` and `CHANGELOG.md` for versioning and update history.
- Documentation should be updated with every significant code or feature change to ensure maintainability.

---

**Tip:** This project-level file is the primary reference for the overall system.
