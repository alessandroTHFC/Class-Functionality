<script setup lang="ts">
import { computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { BookOpen, LogOut, Users, BarChart2, Settings } from "lucide-vue-next";
import { useAuthStore } from "@/stores/useAuthStore";
import { getInitials } from "@/lib/utils";
import Popover from "@/components/ui/Popover.vue";
import PopoverTrigger from "@/components/ui/PopoverTrigger.vue";
import PopoverContent from "@/components/ui/PopoverContent.vue";
import TooltipProvider from "@/components/ui/TooltipProvider.vue";
import Tooltip from "@/components/ui/Tooltip.vue";
import TooltipTrigger from "@/components/ui/TooltipTrigger.vue";
import TooltipContent from "@/components/ui/TooltipContent.vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const initials = computed(() => getInitials(authStore.user?.name ?? ""));

// Format a role slug into a human-readable label.
// e.g. "school-admin" → "School Admin", "teachers-assistant" → "Teachers Assistant"
const formattedRoles = computed(() =>
  (authStore.user?.roles ?? []).map((r) =>
    r.replace(/-/g, " ").replace(/\b\w/g, (c) => c.toUpperCase()),
  ),
);

const navItems = [
  { name: "classes", label: "Class Dashboard", icon: BookOpen },
];

const placeholderItems = [
  { name: "students", label: "Students", icon: Users },
  { name: "reports", label: "Reports", icon: BarChart2 },
  { name: "settings", label: "Settings", icon: Settings },
];

async function handleLogout() {
  await authStore.logout();
  router.push({ name: "login" });
}
</script>

<template>
  <div class="flex min-h-screen">
    <!-- ── 88px icon-only sidebar ────────────────────────────────────────── -->
    <aside
      class="w-[88px] flex-shrink-0 bg-sidebar flex flex-col items-center py-4"
    >
      <TooltipProvider>
        <!-- Logo mark -->
        <div
          class="w-11 h-11 rounded-sm bg-teal flex items-center justify-center font-bold text-white text-base mb-6"
        >
          CH
        </div>

        <!-- Navigation icons -->
        <nav class="flex flex-col items-center gap-1 flex-1">
          <!-- Active nav items — link to real routes -->
          <Tooltip v-for="item in navItems" :key="item.name">
            <TooltipTrigger>
              <RouterLink
                :to="{ name: item.name }"
                :class="[
                  'flex items-center justify-center w-11 h-11 rounded-sm transition-colors',
                  route.name === item.name
                    ? 'bg-white/10 text-white border-l-2 border-teal'
                    : 'text-white/50 hover:text-white hover:bg-white/5',
                ]"
              >
                <component :is="item.icon" class="w-5 h-5" />
              </RouterLink>
            </TooltipTrigger>
            <TooltipContent side="right">{{ item.label }}</TooltipContent>
          </Tooltip>

          <!-- Placeholder nav items — future pages, no action on click -->
          <Tooltip v-for="item in placeholderItems" :key="item.name">
            <TooltipTrigger>
              <div
                class="flex items-center justify-center w-11 h-11 rounded-sm text-white/25 cursor-default"
              >
                <component :is="item.icon" class="w-5 h-5" />
              </div>
            </TooltipTrigger>
            <TooltipContent side="right">{{ item.label }}</TooltipContent>
          </Tooltip>
        </nav>

        <!-- User avatar + logout — pinned to the bottom -->
        <div class="flex flex-col items-center gap-3 mt-auto">
          <!-- Avatar with popover showing name, role, and tenant -->
          <Popover>
            <PopoverTrigger
              class="w-9 h-9 rounded-full bg-teal flex items-center justify-center text-white text-xs font-semibold hover:opacity-80 transition-opacity"
            >
              {{ initials }}
            </PopoverTrigger>
            <PopoverContent
              side="right"
              :side-offset="12"
              align="end"
              class="w-56"
            >
              <div class="space-y-3">
                <!-- Name + tenant -->
                <div>
                  <p class="text-sm font-semibold text-text-primary">
                    {{ authStore.user?.name }}
                  </p>
                  <p class="text-xs text-text-secondary mt-0.5">
                    {{ authStore.user?.tenant?.name }}
                  </p>
                </div>

                <div class="border-t border-brand-border" />

                <!-- Roles -->
                <div>
                  <p
                    class="text-xs font-medium text-text-secondary uppercase tracking-wide mb-1.5"
                  >
                    Role
                  </p>
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="role in formattedRoles"
                      :key="role"
                      class="inline-flex items-center rounded-full bg-teal-light px-2 py-0.5 text-xs font-medium text-teal"
                    >
                      {{ role }}
                    </span>
                  </div>
                </div>
              </div>
            </PopoverContent>
          </Popover>

          <!-- Logout icon button -->
          <Tooltip>
            <TooltipTrigger>
              <button
                class="flex items-center justify-center w-11 h-11 rounded-sm text-white/50 hover:text-white hover:bg-white/5 transition-colors"
                @click="handleLogout"
              >
                <LogOut class="w-5 h-5" />
              </button>
            </TooltipTrigger>
            <TooltipContent side="right">Sign out</TooltipContent>
          </Tooltip>
        </div>
      </TooltipProvider>
    </aside>

    <!-- ── Main content area ─────────────────────────────────────────────── -->
    <div class="flex-1 bg-app-bg overflow-auto">
      <slot />
    </div>
  </div>
</template>
