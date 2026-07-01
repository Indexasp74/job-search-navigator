# Backlog — Job Search Navigator

Prioritized feature roadmap for future development.

## 1. Stale Application Tracking

**Problem**: Active applications that have been open for 30+ days are increasingly unlikely to convert, but users keep them "active" hoping for movement. No clear signal for applications without an applied date.

**Solution**: Introduce "stale" status for long-stalled applications and orphaned records.

### Acceptance Criteria

- [ ] Applications in "active" status for >30 days show a "Mark as Stale" control
  - Icon: TBD (suggest: ⏳ hourglass or 🔄 cycle icon)
  - Title attribute: "Mark as Stale (retains active status)"
  - Action: Move to stale status without changing active count
- [ ] Applications without a `date_applied` show the same "Mark as Stale" control
- [ ] Stale applications appear below all standard active apps in the Applications tab
- [ ] Stale applications have a "Mark as Rejected" control
  - Icon: Different from stale control (suggest: ✕ or 🚫)
  - Title attribute: "Mark as Rejected"
  - Action: Moves to "no" status
- [ ] Sorting/filtering includes stale status as visual distinct group

### Database Schema Update

```sql
ALTER TABLE applications 
MODIFY status ENUM('active','stale','screen','interview','offer','no','canceled','hold') DEFAULT 'active';
```

### UI/UX Notes
- Stale section should have muted text color to visually de-emphasize
- "Stale" badge similar to existing status badges
- Consider showing "stale since [date]" label on applications
- Preserve view state when marking applications as stale (don't auto-scroll)

---

## 2. Interview Details on Organization Cards

**Problem**: Interview progress is buried in application notes. No dedicated tracking for interview metadata across all roles at an org.

**Solution**: Add lightweight interview detail form accessible from organization card.

### Acceptance Criteria

- [ ] Organization card shows "Add Interview Details" CTA button
  - Position: Near bottom of card, below application counts
  - State: Hidden if no active or screen applications at that org
- [ ] Clicking opens modal/drawer with form fields:
  - Interview date (date picker)
  - Interview type (dropdown: phone, video, in-person, async)
  - Interviewer(s) (free-text, comma-separated names)
  - Interview notes (textarea)
- [ ] Form links to at least one application in that org (dropdown to select)
- [ ] On save, updates that application's `interview_notes` and creates audit trail
- [ ] Show "Next interview: [date]" on organization card if interviews are scheduled

### Database Schema Update

```sql
ALTER TABLE applications ADD COLUMN interview_date DATE NULL;
ALTER TABLE applications ADD COLUMN interview_type VARCHAR(50); 
-- ENUM('phone','video','in-person','async','other')
ALTER TABLE applications ADD COLUMN interviewers TEXT NULL;
-- Comma-separated names
```

### UI/UX Notes
- Pre-select the most recent active/screen application by default
- Show a list of existing interviews for that org (read-only)
- Allow marking interview as "completed" vs "pending"
- Consider Gantt-style timeline of interviews across org's applications

---

## 3. Discovery Role Curation (Voting)

**Problem**: Adzuna sweep produces high-volume, low-relevance role suggestions. Users manually filter noise but signals aren't captured for future sweeps.

**Solution**: Add quick-vote controls (strike, thumbs down, thumbs up) to shape discovery feed.

### Acceptance Criteria

- [ ] Discoveries table adds column between "Company" and "Role"
- [ ] Column contains three controls per row:
  - **Strike/Remove** (icon: ✕ or 🗑)
    - Title: "Remove from discoveries"
    - Action: Soft-delete (mark status 'dismissed') and hide from view
  - **Thumbs Down** (icon: 👎)
    - Title: "Not a fit"
    - Action: Mark status 'dismissed' + record preference signal
  - **Thumbs Up** (icon: 👍)
    - Title: "Good fit"
    - Action: Keep status 'new' (or add 'liked'?) + record preference signal
- [ ] Voting data persists in database for future agent analysis
- [ ] Discovery sweep agent respects user preferences when generating future results
  - Don't surface roles from companies/role-types the user consistently voted down
  - Prioritize roles the user voted up

### Database Schema Update

```sql
ALTER TABLE role_discoveries ADD COLUMN user_vote ENUM('up','down','dismissed') NULL;
ALTER TABLE role_discoveries ADD COLUMN voted_at DATETIME NULL;

CREATE TABLE discovery_preferences (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(255),
  role_keyword VARCHAR(255),
  vote_direction ENUM('up','down'),
  vote_count INT DEFAULT 1,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### UI/UX Notes
- Voting should be immediate (no confirm modal)
- Strikethrough or mute visual of dismissed roles temporarily before hiding
- Show vote count next to controls (e.g., "👍 3" if user has liked similar roles)
- In agent docs, explain that votes feed into discovery curation

---

## 4. Daily Brief Multi-Channel Delivery

**Problem**: Daily brief currently email-only. Users may prefer Telegram, Slack, Discord, or SMS.

**Solution**: Allow users to choose delivery channels and show example brief until first one is generated.

### Acceptance Criteria

- [ ] Daily Brief tab shows example/template brief on first load (before first report is generated)
  - Example should show structure, tone, and format users will receive
  - Clearly label as "Example — your first briefing will appear here"
- [ ] Settings/preferences section to select delivery channel(s):
  - Email (default, already implemented)
  - Telegram (requires user's Telegram ID)
  - Slack (requires Slack webhook URL or bot token)
  - Discord (requires Discord webhook URL)
  - SMS (optional; paid service)
- [ ] Users can enable multiple channels simultaneously
- [ ] On report generation, agent sends to all enabled channels
- [ ] Each channel's message format is optimized for that medium
  - Email: HTML formatted with branding
  - Telegram: Plain text, emoji-enriched
  - Slack: Rich blocks with formatting
  - Discord: Embeds with color-coding

### Database Schema Update

```sql
CREATE TABLE user_preferences (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  delivery_channels JSON NOT NULL, -- {"email": true, "telegram": false, "slack": true, ...}
  telegram_chat_id VARCHAR(255) NULL,
  slack_webhook_url VARCHAR(500) NULL,
  slack_token VARCHAR(255) NULL,
  discord_webhook_url VARCHAR(500) NULL,
  sms_number VARCHAR(20) NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Example Daily Brief

```
📊 Job Search Navigator — Daily Brief
Generated: July 2, 2026

🎯 NEW ROLES DISCOVERED: 7
├─ 3 from target organizations
├─ 4 from adjacent markets
└─ 2 at high-fit companies you've already applied to

🔍 DISCOVERY INSIGHTS
Your search is trending toward Director roles in healthcare tech.
You've seen 23% more MedTech opportunities this week vs. last.

💬 COACHING INSIGHT
Your three most recent interviews were with companies in the $50-100M range.
Consider applying to more Series C/D funded startups — faster hiring cycles, clearer career paths.

📈 PIPELINE STATUS
- Active applications: 45 (↑ 2 from yesterday)
- In screening: 3
- Interview scheduled: 2
- Pending offers: 0

🚀 RECOMMENDED ACTION
Follow up with Netflix TPM role (stale for 112 days).
Re-engagement message drafted and ready to send.

---
See full details: https://yourdomain.com/tracker/
```

### Agent & Cron Updates

- `send-report.php` should dispatch to multiple channels based on user preferences
- New `api/preferences.php` endpoint for managing delivery channels
- Validate Telegram/Slack/Discord credentials before saving
- Add error handling for failed sends (retry, log, notify user)

---

## 5. Coaching Q&A Metadata & Curation

**Problem**: Coaching responses lack context (when was this asked?). No signal to agents about which coaching angles are most valuable.

**Solution**: Add timestamp to Q&A and like/dislike controls for coaching quality feedback.

### Acceptance Criteria

- [ ] Each coaching response shows:
  - **Asked**: [date time] label showing when user submitted the question
  - **Answered**: [date time] label showing when agent responded
  - Time delta: "Answered 3 hours later" or similar
- [ ] Below coaching response text, add vote controls:
  - **Thumbs Up** (icon: 👍)
    - Title: "This was helpful"
    - Action: Mark as liked, record feedback
  - **Thumbs Down** (icon: 👎)
    - Title: "Not helpful"
    - Action: Mark as disliked, record feedback
  - **Flag** (icon: 🚩 or ⚠️)
    - Title: "Inaccurate or misleading"
    - Action: Mark for review, notify user
- [ ] Vote data is captured for agent analysis
- [ ] Agents use vote history to:
  - Double down on question types with high like rates
  - Avoid angles that consistently get disliked
  - Refine coaching style/voice based on feedback

### Database Schema Update

```sql
ALTER TABLE coaching_sessions ADD COLUMN asked_at DATETIME DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE coaching_sessions ADD COLUMN user_rating ENUM('up','down','flagged') NULL;
ALTER TABLE coaching_sessions ADD COLUMN rated_at DATETIME NULL;
ALTER TABLE coaching_sessions ADD COLUMN helpful_count INT DEFAULT 0;
ALTER TABLE coaching_sessions ADD COLUMN unhelpful_count INT DEFAULT 0;
```

### Agent Integration

Update agent definitions to read from `coaching_sessions.helpful_count` and `unhelpful_count`:

```markdown
## Feedback from User Coaching Ratings

Before generating coaching responses, review:
- High-rated topics (user likes these coaching angles)
- Low-rated topics (avoid these approaches)
- Flagged responses (user says these were inaccurate)

Adapt your response style and content focus based on user's coaching preferences.
```

### UI/UX Notes

- Show vote counts next to controls (e.g., "👍 2 people found this helpful")
- Highlight historically high-rated responses with a ⭐ badge
- In agent docs, explain voting system shapes future coaching quality
- Consider showing "Most helpful coaching angles for you: [topics]" analytics

---

## Priority & Effort Estimates

| Feature | Priority | Effort | Impact |
|---------|----------|--------|--------|
| Stale applications | High | Medium | Cleaner pipeline tracking |
| Interview details | High | Medium | Better org-level visibility |
| Discovery voting | Medium | Medium | Smarter sweep filtering |
| Multi-channel delivery | Medium | Medium-High | Better user reach |
| Coaching metadata | Medium | Small | Smarter agent learning |

---

## Getting Started

1. **Pick one feature** (suggest: Stale applications — smallest scope, immediate value)
2. **Open a GitHub issue** with acceptance criteria
3. **Fork the repo** and create a feature branch
4. **Build & test** locally
5. **Submit a PR** with tests and updated docs
6. **Get feedback** from maintainers
7. **Merge & celebrate** 🎉

---

## Questions?

- Check [ARCHITECTURE.md](ARCHITECTURE.md) for database/API design
- Read [SETUP.md](SETUP.md) for local dev environment
- Open an issue to discuss scope, trade-offs, or alternative approaches
