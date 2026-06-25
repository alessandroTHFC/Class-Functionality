import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/lib/axios";
import { useReferenceStore } from "@/stores/useReferenceStore";
import type { AuthUser, LoginResponse } from "@/types";

export const useAuthStore = defineStore("auth", () => {
  // Token and user are initialised from localStorage so they survive page refreshes.
  const token = ref<string | null>(localStorage.getItem("auth_token"));
  const user = ref<AuthUser | null>(
    JSON.parse(localStorage.getItem("auth_user") ?? "null"),
  );
  console.log("user", user.value);

  // isAuthenticated is derived from the token — no token means not logged in.
  const isAuthenticated = computed(() => !!token.value)

  // Returns true if the authenticated user holds at least one of the given roles.
  // Pass multiple roles when a feature is shared across role tiers.
  function hasRole(...allowedRoles: string[]): boolean {
    return (user.value?.roles ?? []).some((r) => allowedRoles.includes(r));
  }

  // Pre-computed permissions used across multiple pages — avoids repeating the same
  // hasRole() calls in every component that needs to gate UI by role.
  const canCreate = computed(() => hasRole("school-admin", "coordinator", "teacher"));
  const canEdit   = computed(() => hasRole("school-admin", "coordinator", "teacher"));
  const canDelete = computed(() => hasRole("school-admin", "coordinator"));
  const canAddNotes = computed(() => hasRole("school-admin", "coordinator", "teacher", "teachers-assistant"));

  // Persist token and user to localStorage so the session survives a browser refresh.
  // The Axios interceptor in src/lib/axios.ts reads auth_token on every request.
  async function login(email: string, password: string): Promise<void> {
    const { data } = await api.post<LoginResponse>("/login", {
      email,
      password,
    });

    token.value = data.token;
    localStorage.setItem("auth_token", data.token);

    // Fetch the full user (with tenant) immediately after login so we have
    // the tenant name available throughout the session without a separate call.
    // Laravel's JsonResource wraps single resources in { data: { ... } } — unwrap here.
    const res = await api.get<{ data: AuthUser }>("/user");
    user.value = res.data.data;
    localStorage.setItem("auth_user", JSON.stringify(res.data.data));
  }

  async function logout(): Promise<void> {
    // Clear local state regardless of API response — if the token is already
    // invalid (e.g. after a fresh DB seed) the call will 401 but we still
    // need to drop the session on the frontend side.
    try {
      await api.post("/logout");
    } finally {
      token.value = null;
      user.value = null;
      localStorage.removeItem("auth_token");
      localStorage.removeItem("auth_user");
      useReferenceStore().reset();
    }
  }

  return { token, user, isAuthenticated, hasRole, canCreate, canEdit, canDelete, canAddNotes, login, logout };
});
