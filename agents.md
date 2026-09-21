# Project Specification: AKTIVITY (Kanban Board Application)

## 1. System Architecture & Environment
- **Hosting:** Active24.cz Smart Hosting (`/microview.cz/aktivity`).
- **Backend:** PHP (8.1+ strictly).
- **Database:** MariaDB. Require execution of completely new DB schema scripts (legacy scripts strictly prohibited).
- **Frontend:** HTML5, CSS3, vanilla JavaScript (ES6+), Fetch API for asynchronous operations.
- **External API:** ChatGPT API (credentials and model specification stored securely in `config.php`).

## 2. Directory & Database Structure
- **Target File Architecture:**
  - `/api/` - Endpoint handlers for Fetch API requests (JSON responses).
  - `/assets/` - CSS styling, JS scripts, fonts.
  - `/core/` - DB connection singleton, authentication logic, helper functions.
  - `config.php` - Environment variables, API keys, SMTP credentials (must be isolated).
  - `index.php` - Main Kanban UI.
  - `reset_pw.php` - Password reset landing page.
- **Database Entities (Strict Relational Model):**
  - `users` (id, email, password_hash, created_at)
  - `user_settings` (user_id, language, theme)
  - `cards` (id, user_id, title, body, status_column, order_index, created_at, deleted_at)
  - `card_history` (id, card_id, action_type, previous_state, new_state, timestamp)

## 3. Security & Development Standards
- **Development Workflow:** Strict iterative approach. Each individual feature must be fully coded, tested, and validated before initiating work on the subsequent feature.
- **Backend Security Architecture:**
  - **Authentication:** Password hashing must use `Argon2id` (PHP `PASSWORD_ARGON2ID`).
  - **Session Management:** Secure sessions with `HttpOnly`, `Secure`, and `SameSite=Strict` cookie flags. Hard expiration set to 2 hours of inactivity.
  - **CSRF Protection:** Synchronizer Token Pattern. Every state-changing HTTP request (POST/PUT/DELETE) must validate a cryptographically secure, per-session CSRF token.
  - **Brute-Force Mitigation:** Implement rate limiting at the database level for login and password reset endpoints.
  - **Data Sanitization:** Strict use of PDO prepared statements for all SQL queries. Context-aware output encoding (XSS prevention) using `htmlspecialchars()` for rendering user input.

## 4. User & Account Management
- **Registration Constraints:**
  - Username: Must pass strict valid email format validation.
  - Password: Minimum 8 characters, mandatory combination of uppercase, lowercase, numbers, and special symbols. Case sensitive.
- **Password Reset Protocol:**
  - Trigger: User inputs email, system generates secure cryptographically random token.
  - Delivery: Email dispatched from `postmaster@microview.cz` (SMTP details in `config.php`).
  - Constraint: Reset link expiration hardcoded to exactly 10 minutes.
  - Action: Link redirects to `/microview.cz/aktivity/reset_pw.php` for new password formulation.
- **Data Isolation:** All database records must be strictly isolated per user account based on active session ID.
- **Initialization:** Auto-generate specific test account:
  - Username: `falconeyex@gmail.com`
  - Password: `Ferdicek2026*`
  - Content: Populate initial state with 20 dummy test cards distributed across columns.

## 5. UI / UX Specifications
- **Design Philosophy:** Top-tier, professional, highly appealing but strictly functional and non-distracting UI. 
- **Responsiveness:** Fluid grid architecture. Columns must gracefully degrade on mobile viewports.
- **Global Toggles:**
  - Theme: Light / Dark mode switcher (persist preference in `user_settings` table).
  - Localization: Czech / English switch (translates UI elements and system functions only; user-generated card content remains untranslated).
- **Error Handling:** Centralized UI toast notification system for Fetch API errors (network failure, 4xx/5xx HTTP statuses).

## 6. Core Kanban Functionality
- **Columns Structure (Strict Naming):**
  1. Backlog, 2. To Do, 3. In Progress, 4. Review, 5. Done, 6. Postponed
- **Interactivity:** Seamless Drag & Drop logic for cards between columns and reordering within columns, executed asynchronously via Fetch API (no page reloads).
- **Column UI:** Each column requires a persistent "Add Card" action button.
- **Card Data Model:**
  - `Title` (String, max 255 chars).
  - `Body` (Formatted HTML). 
  - `Subtitle` (Timestamp: "card created: [datetime]").
- **Content Editing:** Integrate a lightweight, reliable WYSIWYG editor for the card `Body`. Ensure server-side HTML sanitization (e.g., HTMLPurifier) before DB insertion.
- **History Log:** Dedicated UI modal accessible from each card to display its comprehensive `card_history` audit trail (edits and column movements).
- **Card Functions:** Move to Recycle Bin, Add to AI Chat.
- **Recycle Bin Mechanics:**
  - Storage for user-deleted cards.
  - Required actions per card: 1. Restore to exact previous column and `order_index`, 2. Delete permanently from database.

## 7. AI Agent Integration
- **Function:** Contextual integration with ChatGPT API.
- **UI Interface:** A persistent sidebar or a draggable floating window for the AI chat. 
- **Card-Level Action ("Add to AI chat"):** Background execution appending exact card content and full history log to the active AI chat context. UI feedback: Display non-intrusive "card added to chat" toast notification.
- **Bulk Actions:**
  - Column/Selection selection: Ability to append an entire column or multiple selected cards to the chat context simultaneously.
  - Board-Level Action ("Add all to chat"): Appends the entire current state of the Kanban board to the chat context.
- **Chat Management:** "Remove all content" function to purge current chat context in the UI and reset the API memory state.

## 8. Deployment Documentation
- Output requirement: Cursor must generate a separate `DEPLOYMENT.md` detailing the upload process to Active24.cz, necessary PHP version configurations, DB import procedures, and permission setups for production.