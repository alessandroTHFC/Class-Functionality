<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/useAuthStore";
import Button from "@/components/ui/Button.vue";
import Input from "@/components/ui/Input.vue";
import Label from "@/components/ui/Label.vue";

const router = useRouter();
const auth = useAuthStore();

const email = ref("");
const password = ref("");
const error = ref<string | null>(null);
const loading = ref(false);

async function handleLogin() {
  error.value = null;
  loading.value = true;
  try {
    await auth.login(email.value, password.value);
    router.push({ name: "classes" });
  } catch (e: any) {
    if (e.response?.status === 401) {
      error.value = "Incorrect email or password.";
    } else if (e.response?.status === 422) {
      const firstError = Object.values(
        e.response.data.errors ?? {},
      )[0] as string[];
      error.value = firstError?.[0] ?? "Validation error.";
    } else {
      error.value = "Something went wrong. Please try again.";
    }
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex bg-app-bg">
    <!-- Left branding panel -->
    <div
      class="hidden lg:flex w-[400px] flex-shrink-0 flex-col justify-between p-10 bg-sidebar"
    >
      <div>
        <div class="flex items-center gap-3 mb-12">
          <div
            class="w-9 h-9 rounded-sm flex items-center justify-center font-bold text-white bg-teal"
          >
            CH
          </div>
          <span class="text-white font-semibold text-xl tracking-tight"
            >ClassHub</span
          >
        </div>
        <h2 class="text-white text-3xl font-bold leading-snug mb-4">
          Class management<br /><span class="text-[#6941C6]">made simple.</span>
        </h2>
        <p class="text-white/55 text-sm leading-relaxed">
          Manage classes, track student enrolments, and record notes — all in
          one place.
        </p>
      </div>
      <p class="text-white/30 text-xs">
        © {{ new Date().getFullYear() }} ClassHub
      </p>
    </div>

    <!-- Right form panel -->
    <div class="flex-1 flex items-center justify-center p-8">
      <div class="w-full max-w-sm">
        <!-- Mobile logo -->
        <div class="flex items-center gap-2 mb-8 lg:hidden">
          <div
            class="w-8 h-8 rounded-sm flex items-center justify-center font-bold text-white bg-teal"
          >
            C
          </div>
          <span class="font-semibold text-xl text-text-primary">ClassHub</span>
        </div>

        <div class="mb-8">
          <h1 class="text-2xl font-semibold text-text-primary mb-1">Sign in</h1>
          <p class="text-sm text-text-secondary">
            Enter your school account credentials
          </p>
        </div>

        <form class="space-y-5" @submit.prevent="handleLogin">
          <div class="space-y-1.5">
            <Label for="email">Email address</Label>
            <Input
              id="email"
              v-model="email"
              type="email"
              placeholder="teacher@springfield.demo"
              :disabled="loading"
              required
            />
          </div>

          <div class="space-y-1.5">
            <Label for="password">Password</Label>
            <Input
              id="password"
              v-model="password"
              type="password"
              placeholder="••••••••"
              :disabled="loading"
              required
            />
          </div>

          <div
            v-if="error"
            class="text-sm text-danger-text bg-danger-bg rounded-sm px-3 py-2"
          >
            {{ error }}
          </div>

          <Button type="submit" class="w-full" :disabled="loading">
            {{ loading ? "Signing in…" : "Sign in" }}
          </Button>
        </form>
      </div>
    </div>
  </div>
</template>
