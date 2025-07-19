# LexiQuest AI

LexiQuest AI is a modular WordPress plugin suite designed to help children grow as readers through personalized, AI-generated stories and quizzes. It leverages OpenAI for dynamic content creation and public image APIs for visual storytelling. LexiQuest AI maintains familiar education-centric terminology like "Lexile Level," "Reading Progress," and "Student Quizzes" to deliver an engaging, scalable, and modern reading experience.

**Note:** All features and documentation are tracked in the root `FEATURES.md` and `plan.md`. Per-plugin documentation files are not maintained. 

---

## 🌟 Features

- GPT-4-powered story generation based on student interest
- Age-appropriate Lexile-level estimations
- Auto-generated reading comprehension quizzes
- Beautiful illustrations pulled from free image APIs (Unsplash, Pexels, Pixabay)
- Child-friendly story library with filters and search
- Gamified reading progress tracker
- Modular plugin architecture for maximum flexibility

---

## 🔌 Plugin Modules

Each plugin is installed separately to allow for selective activation:

- `lexiquest-core`: Base CPTs, settings, and user role management
- `lexiquest-ai-generator`: AI story and quiz generator
- `lexiquest-image-fetcher`: Fetch images from free APIs
- `lexiquest-quiz-system`: Quiz delivery and validation
- `lexiquest-library-ui`: Frontend library browsing experience

---

## 🔧 Setup Instructions

1. **Install all plugins** in `/wp-content/plugins/`
2. Activate `lexiquest-core` first, then others as needed
3. Go to **LexiQuest Settings** in WP Admin
   - Add your OpenAI key
   - Add your image API key (Unsplash/Pexels/Pixabay)
4. Start searching story topics in the student dashboard

---

## 🔐 API Keys

Store them in the plugin settings page or using `.env` for extra security:

```
OPENAI_API_KEY=your-openai-key
UNSPLASH_API_KEY=your-unsplash-key
```

---

## 💾 Storage Structure

- CPT: `ai_story`, `quiz`, `student_profile`
- Custom tables: for quiz results and student progress

---

## 🧪 Development

```bash
git clone https://github.com/your-username/lexiquest-ai.git
cd lexiquest-ai/plugins/lexiquest-core
npm install && npm run dev # if using build tools
```

---

## 📄 License

MIT License. No copyrighted content from Scholastic is used. All generated content is AI-based.

---

## 🧠 Inspired By

Scholastic Literacy Pro, but fully reimagined with OpenAI, WordPress, and modular design.

