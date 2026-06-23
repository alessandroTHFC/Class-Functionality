// ─── Auth ────────────────────────────────────────────────────────────────────

export interface AuthUser {
  id: number
  name: string
  email: string
  roles: string[]
  tenant: { id: string; name: string }
}

// The login response only includes a subset of AuthUser (no tenant).
// Pick<> lets us express that without duplicating fields.
export interface LoginResponse {
  token: string
  user: Pick<AuthUser, 'id' | 'name' | 'email' | 'roles'>
}

// ─── Shared primitives ────────────────────────────────────────────────────────

export interface YearLevel {
  id: number
  description: string
  sort_order?: number
}

export interface UserSummary {
  id: number
  name: string
  roles: string[]
}

// ─── Pagination ───────────────────────────────────────────────────────────────

export interface PaginatedMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginatedMeta
}

// ─── Classes — list ───────────────────────────────────────────────────────────

export interface ClassSummary {
  total_students: number
  teachers_assigned: number
}

// ClassListMeta extends PaginatedMeta to include the tenant-wide summary
// injected by ClassListCollection::paginationInformation() on the backend.
export interface ClassListMeta extends PaginatedMeta {
  summary: ClassSummary
}

export interface ClassListItem {
  id: number
  name: string
  year_level: YearLevel | null
  created_by: { id: number; name: string }
  assigned_users: UserSummary[]
  student_count: number
}

// ─── Classes — detail ─────────────────────────────────────────────────────────

export interface NccdSummary {
  QDTP: number
  Supplementary: number
  Substantial: number
  Extensive: number
}

export interface StudentDetail {
  id: number
  full_name: string
  given_name: string
  family_name: string
  year_level: YearLevel | null
  nccd_level: string | null
  nccd_category: string | null
  primary_disability: string | null
  primary_disability_level_formalised: boolean
}

export interface ClassDetail {
  id: number
  name: string
  year_level: YearLevel | null
  created_by: { id: number; name: string }
  assigned_users: UserSummary[]
  nccd_summary: NccdSummary
  students: StudentDetail[]
}

// ─── Students ─────────────────────────────────────────────────────────────────

export interface StudentListItem {
  id: number
  full_name: string
  given_name: string
  family_name: string
  year_level: YearLevel | null
}

// ─── Notes ────────────────────────────────────────────────────────────────────

export interface StudentNote {
  id: number
  note_text: string
  note_date: string
  confidentiality_level: string | null
  author: { id: number; name: string }
  class: { id: number; name: string }
  created_at: string
}

// ─── API request payloads ─────────────────────────────────────────────────────

export interface StoreClassPayload {
  name: string
  year_level_id: number | null
  user_ids: number[]
  student_ids: number[]
}

export interface UpdateClassPayload extends StoreClassPayload {}

export interface StoreNotePayload {
  student_ids: number[]
  class_id: number
  note_text: string
  note_date: string
  confidentiality_level: string | null
}
