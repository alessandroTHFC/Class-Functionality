# Frontend Design

This document is the reference for all frontend work (Phases 10–13). It is derived from the provided design mockups and design system specification, adjusted for the actual data and scope of this project.

---

## Design System

### Typography
- **Font:** Inter (fallback: sans-serif)
- **H1:** 32px / 700 / line-height 40px — page titles
- **H2:** 24px / 600 — section titles
- **H3:** 20px / 600 — card titles
- **Body:** 14px / 400
- **Small label:** 12px / 500

### Colour Palette

| Token | Value | Usage |
|---|---|---|
| Primary Teal | `#0B6B6F` | Buttons, links, selected states, active nav |
| Primary Teal Hover | `#09585C` | |
| Primary Teal Active | `#074649` | |
| Sidebar | `#042F33` | Sidebar background |
| App Background | `#F8FAFB` | Page background |
| Card Background | `#FFFFFF` | All cards |
| Border | `#E7EDF0` | Card borders, dividers |
| Primary Text | `#172B36` | Headings, primary body text |
| Secondary Text | `#637381` | Subtitles, metadata, placeholders |
| Success Background | `#E9F7EE` | Success badges/alerts |
| Success Text | `#2B8A57` | |
| Warning Background | `#FFF8EE` | Warning alerts |
| Warning Text | `#D9822B` | |
| Danger Background | `#FDE8E8` | Delete buttons, danger alerts |
| Danger Text | `#D14343` | |
| Purple Background | `#F2EDFF` | NCCD category badges |
| Purple Text | `#6941C6` | |

### Border Radius
- Small: `8px`
- Medium: `12px`
- Large: `16px`
- Avatar: `999px` (full circle)

### Shadows
- Card default: `0 1px 3px rgba(0,0,0,0.05)`
- Card hover: `0 4px 12px rgba(0,0,0,0.08)`

### Icons
- **Library:** Lucide Icons (`lucide-vue-next`)

---

## Tech Stack (Frontend)

| Layer | Choice |
|---|---|
| Framework | Vue 3 (Composition API, `<script setup>`) |
| Language | **TypeScript** |
| Styling | Tailwind CSS |
| Components | shadcn-vue (owned) |
| Icons | lucide-vue-next |
| HTTP | Axios |
| State | Pinia |
| Routing | Vue Router 4 |

---

## Avatars (No Photo Storage)

No user or student photos are stored. All avatars are generated from initials using the shadcn `Avatar` component with `AvatarFallback`.

**Teachers / Staff:**
- Initials from name (e.g. "SJ" for Sarah Jones)
- Background: teal tint (`#E0F2F2`)
- Text: primary teal (`#0B6B6F`)

**Students:**
- Initials from name (e.g. "ES" for Emily Smith)
- Background: purple tint (`#F2EDFF`)
- Text: purple (`#6941C6`)

This creates a consistent visual distinction between staff and students throughout the UI without requiring any image storage.

---

## Application Shell

### Layout Structure
```
┌──────────────────────────────────────────────┐
│  Sidebar (88px) │  Content Area (remaining)  │
└──────────────────────────────────────────────┘
```

### Sidebar (`AppSidebar.vue`)
- Background: `#042F33`
- Width: 88px, fixed, full height
- Icons only — no labels

**Navigation items (top to bottom):**
- Logo / brand mark at top
- **Home** (Lucide: `Home`) — navigates to `/classes` (the dashboard)
- **Account** (Lucide: `User`) — navigates to `/account` or shows a dropdown with user info
- Spacer (push remaining to bottom)
- **Logout** (Lucide: `LogOut`) — calls `POST /api/logout`, clears token, redirects to `/login`

Active item: teal left border highlight, slightly lighter background tint.

**shadcn:** custom sidebar, Tooltip (show label on hover)

---

## Page Header (`PageHeader.vue`)
Shared component used at the top of each page.

**Left:** Page icon (rounded square, teal tint) + Page title (H1) + subtitle (secondary text)

**Right:** User initials avatar + user name + role + dropdown chevron

**shadcn:** Avatar, DropdownMenu

---

## Stat Card (`StatCard.vue`)
Reusable card used in stat summary rows.

**Structure:** Icon → Large number (H2) → Label → Subtext

**Spec:** Height 140px, border radius 16px, padding 24px, card default shadow.

**shadcn:** Card

---

## Pages

---

## Class Dashboard (`ClassDashboard.vue`)

### Route
`/classes`

### Layout
```
PageHeader
StatRow (3 × StatCard)
FilterBar
ClassTable
Pagination
```

### Stat Row — 3 Cards
| Stat | Icon | Subtext |
|---|---|---|
| Total Classes | `BookOpen` | Across all year levels |
| Total Students | `Users` | Across all classes |
| Teachers Assigned | `User` | Across all classes |

> **API note:** These three totals need to come from the backend. Add a `summary` object to the `GET /api/classes` response meta, or create a dedicated `GET /api/dashboard/stats` endpoint.

### Filter Bar
Layout: Search (40%) — Teacher filter (25%) — Year Level filter (25%) — Create button (10%)

**shadcn:** Card, Input, Select, Button

| Element | shadcn | Detail |
|---|---|---|
| Search | Input | Left icon: `Search`, placeholder: "Search classes by name..." |
| Teacher filter | Select | Populated from `GET /api/users` |
| Year level filter | Select | Populated from year levels list |
| Create Class | Button (primary) | Icon: `Plus`, text: "Create Class" |

### Class Table
**shadcn:** Table

| Column | Content |
|---|---|
| Class Name | Coloured icon + name (bold) |
| Total Students | "{n} students" |
| Teachers Assigned | Staff initials avatar (teal) + teacher name — show first assigned user, "+N more" if multiple |
| Year Level | Badge, colour-coded |
| Actions | DropdownMenu: View, Edit, Delete |

### Pagination
- Shows: "Showing 1 to 6 of 24 classes"
- **shadcn:** Pagination

---

## Create / Edit Class Dialog (`ClassFormDialog.vue`)

Triggered by "Create Class" or "Edit" from the actions dropdown.

**shadcn:** Dialog, Input, Select, Button, Combobox (for multi-select)

**Fields:**
- Class name (Input, required)
- Year level (Select)
- Assign staff (multi-select from `GET /api/users`)
- Enrol students (multi-select from `GET /api/students`)

**Actions:** Cancel (outline) + Save / Create (primary)

---

## Class Detail (`ClassDetail.vue`)

### Route
`/classes/:id`

### Layout
```
Breadcrumb
PageTitle + Actions
StatRow
Split Layout
  ├── StudentListPanel (40%)
  └── StudentProfilePanel (60%)
```

### Breadcrumb
"Classes" (link to `/classes`) › Class name (non-link)

### Page Title Area
- Class name (H1)
- "Teacher: {name}" in teal below

**Right — action buttons:**

| Button | Variant | Icon | Behaviour |
|---|---|---|---|
| Add Multiple Notes | Primary | `FileText` | Opens `BulkNoteModal` |
| Edit Class | Outline | `Pencil` | Opens `ClassFormDialog` pre-populated |
| Delete Class | Outline (danger) | `Trash2` | Confirmation → `DELETE /api/classes/{id}` |

### Stat Row — 2 Cards + Info Block
| Section | Content |
|---|---|
| Students | Count + "View all students" subtext |
| NCCD Students | Count + "X% of class" subtext |
| Class Info | Year Level, Last Updated — displayed as a compact info block |

> **Removed from design:** "High Support" count and "Notes Activity %" have been removed — we do not have the data to support these.

> **API note:** NCCD student count (students where `nccd_level` is not null) should be added to `ClassDetailResource`.

---

## Student List Panel (`StudentListPanel.vue`)

**shadcn:** Card, Input, ScrollArea, Pagination

**Structure:**
- Search input (placeholder: "Search students...") + filter icon
- Scrollable list of `StudentListItem` components
- Pagination ("1–5 of 24")

### Student List Item (`StudentListItem.vue`)

**States:**
- Default: white background
- Selected: 3px left teal border + background `#F5FBFB`

**Structure:**
- Student initials avatar (purple tint)
- Student name (bold)
- NCCD level (coloured text)
- Diagnosis (secondary text, small)
- "View" button (outline, small)

**NCCD Level Text Colours:**
| Level | Colour |
|---|---|
| QDTP | Secondary `#637381` |
| Supplementary | Teal `#0B6B6F` |
| Substantial | Warning `#D9822B` |
| Extensive | Danger `#D14343` |
| Not Recorded | Secondary `#637381` |

---

## Student Profile Panel (`StudentProfilePanel.vue`)

**shadcn:** Card, Avatar, Badge, Tabs, Textarea, Button

### Student Header

**Left:**
- Student initials avatar (large, purple tint)
- Student name (H2)
- Badges: NCCD level badge + NCCD category badge (purple)
- Metadata row: DOB, Year Level, Class name, Diagnosis

**Right:** *(Risk alerts section removed — no data available for allergy alerts, behaviour support plans, or funding reviews)*

### Tabs — Notes / Strategies

**shadcn:** Tabs

| Tab | Content | Status |
|---|---|---|
| Notes | NotesList + NoteComposer | Fully implemented |
| Strategies | StrategiesView | Tab included — functionality to be added later |

---

## Notes List (`NotesList.vue`)

Timeline layout — not a table.

### Note Card (`NoteCard.vue`)

**shadcn:** Card, Avatar, Badge, Button

**Structure:**
- Staff initials avatar (teal tint) + author name (bold) + relative timestamp ("2 hours ago")
- Note body text
- Tag badge (`note_type` field value, if present)
- Bookmark icon (decorative — no bookmark feature)
- More actions menu (DropdownMenu — included for future use, no actions for MVP)

---

## Add Note Composer (`NoteComposer.vue`)

Inline at the bottom of the Notes panel — no modal.

**shadcn:** Textarea, Button

- Full-width Textarea (placeholder: "Add a note...")
- "Save Note" button (primary, right-aligned, icon: `Send`)

Submits to `POST /api/notes` with the current student ID and class ID. Refreshes notes list on success.

---

## Strategies View (`StrategiesView.vue`)

Included as a tab — full functionality to be added in a future phase if time permits.

For MVP, render a single informational card:
- Title: "Strategies"
- Body: "Strategy management will be available in a future update."
- Optional: an "+ Add Strategy" button (disabled or hidden) to match the design visually

---

## Bulk Note Modal (`BulkNoteModal.vue`)

Triggered by "Add Multiple Notes" in the Class Detail header.

**shadcn:** Dialog, Checkbox, Textarea, Button

**Structure:**
- List of enrolled students with checkboxes
- "Select All" toggle
- Textarea for note content
- Submit: "Save Note for {n} Students"

Submits to `POST /api/notes` with `student_ids` array. Closes on success, refreshes notes for the currently selected student if they were included.

---

## Scope Decisions

| Design Element | Decision |
|---|---|
| Classes Active stat card | Removed — replaced with 3-card stat row |
| High Support stat card | Removed — no data |
| Notes Activity % stat card | Removed — no data |
| Risk Alerts (Allergy, BSP, Funding) | Removed — no data |
| Strategies tab | Included — placeholder content for MVP, functionality later |
| User/student photos | Replaced with initials avatars (teal for staff, purple for students) |
| Star / favourite class | Omit entirely |
| Bookmark on notes | Decorative only |
| Class code field | Removed — decorative only, no functional purpose in this app |
| Room field | Removed — not in model |

---

## API Additions Required

| Requirement | Endpoint | Change |
|---|---|---|
| Dashboard stats totals | `GET /api/classes` | Add `summary` to response meta |
| NCCD student count | `GET /api/classes/{id}` | Add to `ClassDetailResource` |

---

## Component Tree

```
App
├── AppSidebar
└── RouterView
    ├── LoginPage
    ├── ClassDashboard
    │   ├── PageHeader
    │   ├── StatCard (×3)
    │   ├── FilterBar
    │   ├── ClassTable → ClassTableRow (×n)
    │   ├── ClassFormDialog
    │   └── Pagination
    └── ClassDetail
        ├── Breadcrumb
        ├── ClassDetailHeader → ClassFormDialog (edit)
        ├── ClassStatRow
        ├── StudentListPanel → StudentListItem (×n)
        ├── StudentProfilePanel
        │   ├── StudentHeader
        │   ├── Tabs
        │   │   ├── NotesList → NoteCard (×n)
        │   │   ├── NoteComposer
        │   │   └── StrategiesView
        └── BulkNoteModal
```
