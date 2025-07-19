# LexiQuest AI Suite – Manual Testing Checklist

Use this checklist to verify the core setup, plugin activation, and basic functionality of your LexiQuest WordPress suite after installation or major changes.

---

## 1. Plugin Activation & Environment
- [x] All LexiQuest plugins activate without errors
- [x] No PHP errors or warnings in the WordPress admin
- [x] WordPress version is compatible (6.0+)

## 2. Core Plugin (lexiquest-core)
- [x] All custom post types appear in the admin dashboard:
    - [x] AI Stories
    - [x] Quizzes
    - [x] Student Profiles
    - [x] Books
    - [x] Teachers
    - [x] Classes
- [x] Custom roles (Student, Teacher) are registered
- [x] LexiQuest settings page appears in Settings menu
- [x] API keys and preferences can be saved and retrieved
- [x] Custom DB tables (`wp_litpro_user_scores`, `wp_litpro_book_assignments`) are created

## 3. Documentation & Files
- [x] `FEATURES.md` is up to date
- [x] `plan.md` reflects current project status
- [x] `README.md` and `CHANGELOG.md` are present and accurate

## 4. General
- [x] .gitignore is correctly ignoring everything except LexiQuest plugins and docs
- [x] No per-plugin documentation files exist (unless explicitly needed)

---

**Tip:** Run through this checklist after every major update or before deployment. Add more items as new features are developed.
