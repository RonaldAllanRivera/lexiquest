# LexiQuest Modular Plugin Architecture & Interaction

This document describes the architecture of the LexiQuest AI suite and how each plugin interacts within the WordPress environment.

---

## Overview
- LexiQuest is split into 5 plugins for clear separation of concerns and ease of maintenance.
- All plugins depend on `lexiquest-core` for shared data structures, roles, and settings.
- Plugins communicate via WordPress hooks, custom REST API endpoints, and shared DB tables.

## Plugin Responsibilities
- **lexiquest-core:** Registers all CPTs, roles, global settings, and DB tables. Provides utility functions for other modules.
- **lexiquest-ai-generator:** Handles AI-powered story and quiz generation. Saves output to CPTs and uses core settings for API keys.
- **lexiquest-image-fetcher:** Fetches images from external APIs and attaches them to stories/books. Reads provider/API key from core settings.
- **lexiquest-quiz-system:** Manages quiz logic, answer validation, and writes scores to the core DB table.
- **lexiquest-library-ui:** Displays stories/books for students in a searchable library. Reads from CPTs and uses REST API endpoints.

## Plugin Interaction
- All plugins use the same CPTs and DB tables registered by the core.
- Data flows from AI/image plugins → CPTs → quiz system → library UI.
- Plugins use actions/filters to extend or react to each other’s functionality (e.g., after a story is generated, trigger image fetch and quiz generation).
- REST API endpoints (provided by core and/or modules) allow AJAX and frontend components to interact with backend data.

## Security & Extensibility
- Only the core plugin handles sensitive settings and DB schema.
- All plugins check for the presence of core before running.
- Future plugins can hook into the same architecture with minimal changes.

---

**Update this file as the architecture evolves or new plugins are added.**
