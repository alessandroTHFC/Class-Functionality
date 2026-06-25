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
| Purple Background | `#F2EDFF` | **Secondary colour — students concept** |
| Purple Text | `#6941C6` | |

**Colour semantics:** Teal is the primary brand colour (classes, actions, navigation). Purple is the secondary colour and represents everything student-related — student stat cards, student count columns, student avatar fallbacks, enrolled student badges. This distinction is applied consistently across all pages and components.

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

## Responsive Design

**Target:** Desktop and tablet only. Mobile is explicitly out of scope — this application is used by school staff on desktops and laptops, with tablet as a secondary target.

**Breakpoints (Tailwind):**
| Breakpoint | Width | Target |
|---|---|---|
| `lg` | ≥1024px | Desktop — primary design target |
| `md` | 768px–1023px | Tablet — must be usable, layout adjusts |
| `sm` and below | <768px | Not supported |

**Layout behaviour at tablet (`md`):**

| Element | Desktop | Tablet |
|---|---|---|
| Sidebar | 88px fixed, always visible | 88px fixed, always visible |
| Content padding | 32px | 24px |
| Dashboard stat row | 3 cards in a row | 3 cards in a row (may compress) |
| Class table | All columns visible | Hide "Teachers Assigned" column if needed |
| Class Detail two-pane | Side by side (40/60 split) | Stack vertically — student list above, profile below |
| Class Detail actions | Three buttons in a row | Stack or condense if needed |

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
- Icons only — hover tooltips use shadcn `Tooltip` components (TooltipProvider wraps the entire aside)

**Navigation items (top to bottom):**
- Logo mark ("C" initials, teal background) at top
- **Class Dashboard** (Lucide: `BookOpen`) — navigates to `/classes`; active state: `bg-white/10 text-white border-l-2 border-teal`; inactive: `text-white/50 hover:text-white hover:bg-white/5`
- **Students** (Lucide: `Users`) — placeholder, no route; `text-white/25 cursor-default`
- **Reports** (Lucide: `BarChart2`) — placeholder, no route; `text-white/25 cursor-default`
- **Settings** (Lucide: `Settings`) — placeholder, no route; `text-white/25 cursor-default`
- Spacer (flex-1 pushes bottom items down)
- User initials avatar (`bg-teal text-white`, 36px circle) — clicking opens a shadcn Popover (right side, offset 12px) showing full name, tenant name, and role badges (teal-light pill)
- **Logout** (Lucide: `LogOut`) — calls `POST /api/logout`, clears token, redirects to `/login`; tooltip: "Sign out"

**shadcn:** TooltipProvider, Tooltip, TooltipTrigger, TooltipContent, Popover, PopoverTrigger, PopoverContent

Active item: `bg-white/10 text-white border-l-2 border-teal`. Inactive: `text-white/50 hover:text-white hover:bg-white/5`.

**Avatar initials:** Derived from `authStore.user?.name` — split on spaces, first character of each word, uppercased, max 2 characters.

**Avatar popover:** Uses shadcn `Popover` (uncontrolled). `PopoverTrigger` renders the avatar button directly (no nested button). Content shows: name (semibold), tenant name (secondary), divider, role section with formatted role badges (slug converted to title case, e.g. "school-admin" → "School Admin").

---

## Page Header (`PageHeader.vue`)
Shared component used at the top of each page.

**Left:** Page icon (rounded square, teal tint) + Page title (H1) + subtitle (secondary text)

**Right:** User initials avatar + user name + role + dropdown chevron

**shadcn:** Avatar, DropdownMenu

---

## Stat Card
Stat cards use shadcn `Card`/`CardHeader`/`CardContent`/`CardTitle` components.

**Structure:** Icon (coloured square bg, 36×36) → Stat number → Label

**Icon colour:** All stat card icons use `bg-purple-bg` with `text-purple-text` — purple is applied uniformly across the stat row as a design choice.

---

## Role-Based UI Visibility

The user's roles are returned on login and stored in the auth store (`useAuthStore`). Components use the stored role to conditionally render action buttons. The backend enforces authorization independently — this is a UX convenience to avoid showing buttons the user can't use, not a security boundary.

### Helper (in `useAuthStore.ts`)

```ts
const hasRole = (...roles: string[]) => roles.includes(user.value?.role)
```

### Visibility Rules

| UI Element | Visible to |
|---|---|
| "Create Class" button (dashboard) | `school-admin`, `coordinator`, `teacher` |
| "Edit Class" button (class detail) | `school-admin`, `coordinator`, `teacher` |
| "Delete Class" button (class detail) | `school-admin`, `coordinator` |
| "Add Note" / "Add Multiple Notes" buttons | `school-admin`, `coordinator`, `teacher`, `teachers-assistant` |
| All class list and detail content | All authenticated roles |

Teachers-assistants and read-only users see the class list and class detail but have no create, edit, or delete buttons rendered. Read-only users also have no note-creation buttons.

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
Layout: 12-column CSS grid — Search (`col-span-5`) — Teacher filter (`col-span-3`) — Year Level filter (`col-span-3`) — Create button (`col-span-1`)

**shadcn:** Input, Select, Button

| Element | shadcn | Detail |
|---|---|---|
| Search | Input | Left icon: `Search`, placeholder: "Search classes by name..." |
| Teacher filter | Select | Populated from `GET /api/users`; null maps to `"all"` sentinel |
| Year level filter | Select | Populated from `GET /api/year_levels`; null maps to `"all"` sentinel |
| Create Class | Button (primary) | Icon: `Plus`, text: "Create Class" |

> **Select null/string conversion:** Radix Vue Select requires non-empty string values. Nullable filter refs use `"all"` as the sentinel value, converted via `computed` get/set wrappers in the component.

**Filter trigger behaviour — no search button:**
- **Text search:** debounced — the API call fires automatically ~300ms after the user stops typing. Clears results and resets to page 1 on each new search.
- **Teacher filter:** fires immediately on dropdown selection change.
- **Year Level filter:** fires immediately on dropdown selection change.
- All three filters are combined in a single `GET /api/classes` call with the active params. Changing any filter resets pagination to page 1.
- A "Clear filters" link appears when any filter is active, resetting all fields and reloading the full list.

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

Triggered by "Create Class" or "Edit" from the actions dropdown. All data is saved in a single API call when the user clicks Save — no intermediate saves occur on individual selections.

**shadcn:** Dialog, Input, Select, Button, Badge, ScrollArea

### Dialog dimensions
- Width: `w-[90vw] max-w-6xl`
- Height: `max-h-[88vh]`
- Teleported to `<body>` via Vue `<Teleport>`; fade `<Transition>` on open/close

### Layout — 2 columns inside the dialog (CSS grid, col-span-5 / col-span-7)

```
┌─────────────────────────────────────────────────────────────┐
│  Left column (class details)  │  Right column (student list) │
│                               │                              │
│  Class Name [_____________]   │  [Search students...      ]  │
│                               │  ┌──────────────────────┐    │
│  [Assign Staff▼] [Yr Lvl▼]   │  │ Bart Simpson    QDTP +│   │
│                               │  │ Lisa Simpson    Supp ✓│   │
│  Enrolled students:           │  │ Emily Clarke    Subs +│   │
│  [Bart ×] [Emily ×]           │  │ ...                   │   │
│                               │  └──────────────────────┘    │
│              [Cancel]  [Save] │  (paginated, 10/page)        │
└─────────────────────────────────────────────────────────────┘
```

### Left Column — Class Details

The left column uses a nested 2-column grid for the Assign Staff and Year Level row.

| Field | Component | Source | Notes |
|---|---|---|---|
| Class Name | Input | — | Required; full width |
| Assign Staff | Select (multiple) | `GET /api/users` | Multi-select; trigger shows "No staff assigned" / staff name / "N staff members" |
| Year Level | Select | `GET /api/year_levels` | Optional; "none" sentinel for null |
| Enrolled students | Badge list | Local state | Populated by the right-column student picker |

**Assign Staff and Year Level** are placed side-by-side in a `grid grid-cols-2 gap-3` row beneath Class Name.

**Multi-select implementation:** The shadcn `Select` component supports a `multiple` prop. Internally, when `multiple` is true, `Select` uses `PopoverRoot` (open/close) + `ListboxRoot` (multi-selection via Radix Vue) instead of `SelectRoot`. `SelectTrigger`, `SelectContent`, and `SelectItem` all detect the mode via Vue `provide/inject` and render the appropriate Radix primitive. The trigger displays a computed label; `string[]` values are converted to/from `number[]` in the form via a computed wrapper.

Each selected student appears as a `<Badge variant="purple">` (pill shape) with an `×` icon. Clicking `×` removes them from local selection with no API call.

### Right Column — Student Picker

- Filter row uses a `grid grid-cols-5 gap-2`: search Input on `col-span-3`, year level Select wrapper on `col-span-2`
- No outer border on the table container — the table sits directly in the column
- shadcn Table (TableHeader/TableBody/TableRow/TableHead/TableCell) with `px-4 py-3` cell padding
- Pagination: shadcn Pagination/PaginationPrevious/PaginationNext components (wrapping Radix Vue `PaginationRoot`/`PaginationPrev`/`PaginationNext`). `PaginationRoot` receives `total` (item count) and `items-per-page`; `PaginationPrev`/`PaginationNext` auto-disable at page boundaries via context.
- `overflow-hidden` on the right column div is required so the inner `flex-1` table area is properly bounded by the dialog height, keeping the pagination footer pinned to the bottom via `flex-shrink-0`

**Student row states:**
| State | Icon | Behaviour |
|---|---|---|
| Not selected | `Plus` (teal) | Click → appends student to local selected array, icon changes to tick |
| Selected | `Check` (greyed, disabled) | Non-interactive — prevents double-selection |

Clicking `Plus` does **not** make an API call. It only appends the student to a local `selectedStudentIds` array. The actual save happens when the user clicks Save.

### Edit Mode

When opened in edit mode, the dialog is pre-populated using data already in the store — no additional API call is made on open. The class detail page loads `GET /api/classes/{id}` on mount, which includes `assigned_users` and `students`. The edit dialog reads directly from that cached response.

- Name, year level, and teacher fields filled from the store
- All currently enrolled students shown as badges in the left column
- Those same students shown with a tick icon in the right-hand student list

If the user cancels the dialog, local state is discarded and the store data remains unchanged.

### Save Behaviour

On Save, a single API call is made:
- **Create:** `POST /api/classes` — sends `name`, `year_level_id`, `user_ids`, `student_ids`
- **Edit:** `PUT /api/classes/{class}` — sends the same fields; backend syncs users and students (adds new, removes omitted)

**Actions:** Cancel (outline) + Save / Update (primary, shows spinner while request is in-flight)

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

| Requirement | Endpoint | Status |
|---|---|---|
| Dashboard stats totals | `GET /api/classes` | Documented — `summary` object in `meta` |
| NCCD student count | `GET /api/classes/{id}` | Documented — `nccd_summary` in `ClassDetailResource` |

---

## Action Feedback Patterns

All user-initiated actions follow a consistent feedback model. This section is the source of truth for what happens after every action in the application.

### Toast Notifications

Use **Sonner** (the shadcn-vue toast library) for all non-blocking feedback. Toasts appear top-right, auto-dismiss after 4 seconds.

**shadcn:** `Sonner` / `useToast`

Four variants used:
| Variant | When |
|---|---|
| `success` | Action completed as expected |
| `error` | Server error, network failure, or permission denied |
| `warning` | Action completed but with a caveat |
| `info` | Neutral informational feedback (rarely needed) |

---

### Confirmation Dialogs

Destructive actions (delete, remove) always require a confirmation dialog before the request is sent. The user must explicitly confirm — no undo available.

**shadcn:** `Dialog` (confirmation variant — no form fields, just title + description + Cancel / Confirm buttons)

Confirm button is styled with the danger colour (`#D14343`).

---

### Loading States

Three distinct loading contexts exist in this application. Each uses a different pattern.

---

#### 1. Button Loading (action in progress)

All action buttons show a loading spinner and are disabled while the request is in progress. This prevents double-submission.

**shadcn:** `Button` with `disabled` prop + Lucide `Loader2` icon (animated spin class)

---

#### 2. Page Loading (initial data fetch)

When a page is navigating to for the first time and its data has not yet loaded, show a skeleton layout rather than a spinner. Skeletons match the approximate shape of the real content so the layout does not shift when data arrives.

| Page | Skeleton |
|---|---|
| Class Dashboard (`/classes`) | Stat card skeletons (3 cards, fixed height) + table row skeletons (5–6 rows, alternating lines) |
| Class Detail (`/classes/:id`) | Stat card skeletons (2 cards) + Class Info block skeleton + two-pane layout skeletons (student list rows left, empty panel right) |

**shadcn:** `Skeleton` component. Apply `animate-pulse` via Tailwind.

The skeleton is shown while the composable's `isLoading` ref is `true`. Once the data resolves, the real content replaces it. There is no spinner for page-level loading — skeletons only.

---

#### 3. Dialog Loading (ClassFormDialog)

`ClassFormDialog` has two internal skeleton states, both using `Skeleton` rows:

| State | Trigger | Skeleton content |
|---|---|---|
| `loadingStudents` | Dialog opens (any mode) — `GET /api/students` in flight | Table header + 6 skeleton rows (Name, Year Level, Enrol columns) in the right-column student picker |
| `loadingDetail` | Edit mode only — `GET /api/classes/{id}` in flight | Full two-column skeleton: left column shows label + input skeletons for Name, Assign Staff, Year Level, and 3 badge pill skeletons for Enrolled Students; right column shows label, filter row, and 6 row skeletons |

Once both fetches resolve, the real form content replaces the skeleton. The two fetches run concurrently.

---

#### 4. Student Panel Loading (student selection)

When the user clicks a different student in the student list, the student panel on the right must reload with that student's data (NCCD details + notes). The student list remains visible and interactive during this load — only the panel content changes.

**Behaviour:**
- The panel area shows a skeleton while the student data is fetching
- The previously selected student remains highlighted in the list during the load
- The skeleton matches the panel layout: avatar + name block at top, info rows below, then a notes list skeleton
- Once data resolves, the skeleton is replaced with the real student panel

**Implementation note:** The student detail data is already included in `GET /api/classes/{id}` (all enrolled students are embedded in the response). The panel load is therefore instant for NCCD data — no second API call needed. The only case requiring a loading state is the notes fetch, which is a separate `GET /api/students/{id}/notes` call triggered when a student is selected.

For notes: show a small skeleton (3–4 note row skeletons) inside the Notes tab while `isLoadingNotes` is `true`.

---

### Form Validation

Two layers of validation exist in `ClassFormDialog`:

**Layer 1 — Frontend (before API call)**

A `validate()` function runs on save. If mandatory fields are empty it:
1. Populates the `errors` ref with field-level messages (displayed inline beneath each field in red)
2. Fires `toast.warning("Please fill in all mandatory fields before saving.")` — a yellow Sonner toast
3. Returns `false`, aborting the API call

| Field | Rule |
|---|---|
| Class name | Required — cannot be blank |
| Assign Staff | Min 1 staff member must be selected |
| Enrolled Students | Min 1 student must be enrolled |
| Year Level | Optional — no validation |

**Layer 2 — Backend (422 response)**

When a `POST` or `PUT` returns a `422`, Laravel's field errors are mapped into the same `errors` ref and shown inline beneath each field. The `errors` ref is cleared at the start of every save attempt, so stale messages never persist.

---

### Full Action Response Reference

#### Classes

| Action | Trigger | Success Toast | Error Handling |
|---|---|---|---|
| Create class | Submit ClassFormDialog | `"'{name}' has been created."` (success) — then re-fetches `GET /api/classes` to refresh the list | 422 → inline field errors in dialog. 500 → error toast: `"Something went wrong. Please try again."` |
| Edit class | Submit ClassFormDialog (edit mode) | `"'{name}' has been updated."` (success) | 422 → inline field errors. 500 → error toast |
| Delete class | Confirm deletion dialog | `"Class deleted successfully."` (success) | 403 → error toast: `"You don't have permission to delete this class."`. 500 → error toast |

#### Student Enrolment

| Action | Trigger | Success Toast | Error Handling |
|---|---|---|---|
| Remove student (individual) | Confirmation dialog on class detail | `"Student removed from class."` (success) | 500 → error toast |

> Staff and student changes made inside the Create/Edit dialog are covered by the Create class and Edit class toasts above — they are part of the same `POST` or `PUT` request, not separate actions.

#### Notes

| Action | Trigger | Success Toast | Error Handling |
|---|---|---|---|
| Save note (single) | Submit NoteComposer | `"Note saved."` (success) | 422 → inline error beneath textarea. 500 → error toast |
| Save bulk notes | Submit BulkNoteModal | `"Note saved for {n} student(s)."` (success) | 422 → inline errors. 500 → error toast |

#### Authentication

| Action | Trigger | Success Toast | Error Handling |
|---|---|---|---|
| Login | Submit login form | None — redirect to `/classes` | 401 → inline error beneath form: `"Incorrect email or password."`. 500 → error toast |
| Logout | Click logout in sidebar | None — redirect to `/login` | 500 → error toast, still clear token and redirect |

---

### Global Error Handling (Axios Interceptor)

An Axios response interceptor in `src/lib/axios.ts` handles errors globally so individual composables don't need to repeat this logic:

| HTTP Status | Behaviour |
|---|---|
| `401` | Clear auth token, redirect to `/login` |
| `403` | Show error toast: `"You don't have permission to do this."` |
| `422` | Return the validation errors to the calling composable — handled inline in the form |
| `500` | Show error toast: `"Something went wrong. Please try again."` |
| Network error | Show error toast: `"Unable to connect. Check your connection."` |

The `422` case is intentionally **not** handled globally — validation errors are returned to the composable and displayed inline in the relevant form.

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
