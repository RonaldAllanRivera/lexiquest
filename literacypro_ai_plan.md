# Project Name: **LexiQuest AI**

LexiQuest AI is a modular WordPress plugin suite designed to clone and enhance the Scholastic Literacy Pro system. It leverages OpenAI for dynamic story generation and uses public image APIs for visual content. LexiQuest AI maintains familiar education-centric terminology like "Lexile Level," "Reading Progress," and "Student Quizzes" to help students grow as readers in a personalized and scalable way.

---

## 📁 Repository Structure

```bash
lexiquest-ai/
├── plugins/
│   ├── lexiquest-core/
│   │   ├── lexiquest-core.php
│   │   ├── includes/
│   │   └── assets/
│   ├── lexiquest-ai-generator/
│   ├── lexiquest-image-fetcher/
│   ├── lexiquest-quiz-system/
│   └── lexiquest-library-ui/
├── docs/
│   ├── README.md
│   └── PLAN.md
└── .env.example
```

---

## 📦 Plugin Breakdown

### 1. `lexiquest-core`

Handles base functionality and registers:

- Custom Post Types: `ai_story`, `quiz`, `student_profile`
- Custom Roles: `Student`, `Teacher`
- Global admin settings (API keys, access levels)

### 2. `lexiquest-ai-generator`

- Integrates OpenAI (GPT-4)
- Generates stories based on child searches
- Generates quizzes based on generated stories
- Saves output to CPT: `ai_story` and `quiz`

### 3. `lexiquest-image-fetcher`

- Connects to free stock APIs (Unsplash, Pexels, or Pixabay)
- Pulls relevant images for story topics
- Saves image links to CPT `ai_story`

### 4. `lexiquest-quiz-system`

- Manages quiz questions, correct answers, and explanations
- Validates user answers and records score
- Tracks student quiz history and comprehension

### 5. `lexiquest-library-ui`

- Displays generated content in a searchable, filterable kid-friendly library
- Includes: story preview, reading level, illustrations, and quiz access

---

## 🔐 API Keys

Stored securely using WP Options or .env (via `wp-config.php`). Admin UI allows easy key input.

- OpenAI Key
- Unsplash / Pexels / Pixabay API Key

---

## 📊 User Roles

| Role    | Access Permissions                         |
| ------- | ------------------------------------------ |
| Admin   | Full access to all features                |
| Teacher | Manage students, assign content            |
| Student | Read stories, take quizzes, track progress |

---

## 📈 Data Flow

```
Student Search ➝ AI Story + Quiz ➝ Image Fetcher ➝ Save to DB ➝ Library UI
```

---

## 🧠 AI Customization

Prompt templates will allow fine-tuned generation such as:

- "Create a 2nd grade level story about {{topic}}. Include a moral lesson."
- "Generate 5 reading comprehension questions with one correct answer each."

---

## 📁 Storage Schema

### Custom Post Types

- `ai_story`
  - Title
  - Content
  - Lexile Estimate
  - Age Group
  - Tags (theme/topic)
  - Featured Image URL
- `quiz`
  - Linked to `ai_story`
  - Questions (JSON)
  - Answer key
  - Explanations
- `student_profile`
  - Linked to WP user
  - Lexile Level
  - Quiz History (custom table or post meta)

---

## 🛠️ Next Steps

1. Build `lexiquest-core`
2. Setup database structure and CPTs
3. Build admin UI for OpenAI and Image API key inputs
4. Implement AI generator and story saver
5. Connect image fetcher
6. Create student dashboard UI
7. Create teacher dashboard for assignments
8. Add quiz tracker and Lexile report generator

---

## ✅ Output Formats

- Library search UI (shortcode or block)
- JSON REST API for story data
- PDF export (optional)
- CSV quiz score export

---

## 📝 Deployment Notes

- Compatible with WordPress 6.0+
- Uses standard WP Hooks and REST APIs
- Avoids altering core behavior or themes
- Easily extendable for multi-language

