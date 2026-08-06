# SkillQ
A web based Skill Monitor for Eve Online.

[![Login with EVE Online](./img/ssologin.png)](https://skillq.net/login-check)

### What is SkillQ?
SkillQ lets you monitor all your EVE Online characters in one place from any browser.

**Everything is stored locally in your browser.** SkillQ does not run a backend database or user-account system. Your character data, wallet history, and settings are kept entirely in your browser's IndexedDB and are never sent to any SkillQ server. Logging out erases all locally stored data from the device.

### Features

- **Multi-character dashboard**: view all your characters on one page with live training countdowns and wallet balances
- **Skill overview**: browse every trained skill grouped by category, with queue and training-in-progress highlights
- **Skill queue**: see the full active queue with finish times and SP/hour rates
- **Training advisor**: ranked list of skills to train next based on your current attributes and implants
- **Optimize section (Train tab)**: per-character neural remap recommendations shown next to Implants, calculated from your active queue, attributes, implants, and current skill SP state
- **Booster detection and badges**: improved booster inference with queue row badges for affected skills and character-level booster status in header/card views
- **Queue expiry badges**: highlights for queue ending within 24 hours, ending within 7 days, and recent no-active-training states
- **Wallet journal**: recent wallet transactions with party name resolution
- **Per-character notes**: keep local reminders and plans for each character, with encrypted backup support and explicit opt-in sharing
- **Character groups and ordering**: organise characters into named groups and sort by SP, ISK, queue finish time, or a custom order
- **Shareable character links**: generate a signed, compressed share URL that lets anyone view a snapshot of your skills and queue (automatically invalidates if you change corporations)
- **Encrypted backups**: export characters, refresh tokens, and local settings into a password-protected backup zip and import it later
- **Fully local storage**: all data lives in your browser's IndexedDB; no SkillQ account or server-side database required
- **Dark / light / system theme**: choose your preferred colour scheme from Settings
- **Restricted or fluid layout**: fixed three-column card layout or fluid full-width mode

---

### Shares

SkillQ can generate a snapshot link from a character overview page.

> **Important share caveats**
>
> - **Snapshot-based**: the share represents one point in time, not a live feed.
> - **Queue cap**: only the first 25 queue entries are included in the share payload.
> - **Completed queue rows are hidden from the queue table** on shared pages.
> - **Completed queue progress is folded into Shared Skills levels** (example: if snapshot shows III but queued IV has already finished by view time, Shared Skills shows IV as completed).
> - **Notes are private by default**: the Notes share checkbox starts off and must be explicitly enabled for each link.
> - **Expiry**: shares expire after 30 days.
> - **Corporation binding**: shares invalidate if the character changes corporations.
> - **Tampering warning**: URL data can be edited by a user, so treat shared data as informational.

---

### Encrypted Backups

SkillQ can export your local browser state to an encrypted backup zip from **Settings**.

What is included:

- Logged-in characters and their refresh tokens
- Active character selection
- Per-character notes
- SkillQ settings (theme, layout, manage/group/order settings)
- SkillQ local UI state stored under `skillq:` localStorage keys

How it works:

- You choose a backup password during export
- Backup payload is encrypted in-browser before download
- Import decrypts in-browser using your password and restores data locally

Important backup caveats:

- If you lose the backup password, the backup cannot be recovered
- Import currently **merges** backup data into your current local SkillQ data
- A wrong password or tampered backup will fail safely during import

---

### Example screenshots:

![Dashboard](./img/skillq_dash.png)

![Character Page](./img/skillq_example.png)
