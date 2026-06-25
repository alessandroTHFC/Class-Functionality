import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/lib/axios";
import type { AuthUser, LoginResponse } from "@/types";

export const useAuthStore = defineStore("auth", () => {
  // Token and user are initialised from localStorage so they survive page refreshes.
  const token = ref<string | null>(localStorage.getItem("auth_token"));
  const user = ref<AuthUser | null>(
    JSON.parse(localStorage.getItem("auth_user") ?? "null"),
  );
  console.log("user", user.value);

  // isAuthenticated is derived from the token — no token means not logged in.
  const isAuthenticated = computed(() => !!token.value);

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
    await api.post("/logout");
    token.value = null;
    user.value = null;
    localStorage.removeItem("auth_token");
    localStorage.removeItem("auth_user");
  }

  return { token, user, isAuthenticated, login, logout };
});
