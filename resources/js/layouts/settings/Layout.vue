<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Palette, Shield, User } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Separator } from '@/components/ui/separator';
import { SIDEBAR_WIDTH } from '@/components/ui/sidebar/utils';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

/** Same width as the main app sidebar (`SIDEBAR_WIDTH`) for visual consistency */
const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
        icon: User,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: Shield,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: Palette,
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col gap-8 lg:flex-row lg:gap-8 lg:gap-12">
            <aside
                class="w-full shrink-0 lg:w-[var(--settings-nav-width)] lg:max-w-none"
                :style="{ '--settings-nav-width': SIDEBAR_WIDTH }"
            >
                <nav
                    class="flex flex-col gap-1.5"
                    aria-label="Settings"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="flex min-h-8 min-w-0 items-center gap-3 overflow-hidden rounded-full px-4 py-2.5 text-left text-sm font-medium transition-colors"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-primary text-primary-foreground shadow-[0_0_10px_rgba(180,0,102,0.28)]'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                    >
                        <component
                            :is="item.icon"
                            class="h-[1.15rem] w-[1.15rem] shrink-0"
                        />
                        <span class="min-w-0 flex-1 truncate">{{ item.title }}</span>
                    </Link>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="min-w-0 flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
