<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

const props = withDefaults(
  defineProps<{
    places?: Array<{
      id: number;
      source_org_id: number;
      title: string | null;
      status: string;
      status_value: number;
      badge_variant: string;
      reviews_count: number;
    }>;
  }>(),
  { places: () => [] }
);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Отзывы',
    href: '/reviews',
  },
];
</script>

<template>
  <Head title="Отзывы" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <div>
        <h1 class="text-2xl font-bold text-foreground">Отзывы</h1>
        <p class="mt-1 text-sm text-muted-foreground">Список мест и их отзывов</p>
      </div>

      <div class="rounded-lg border border-border">
        <table class="w-full text-sm">
          <thead class="border-b border-border bg-muted/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-foreground">ID</th>
              <th class="px-4 py-3 text-left font-medium text-foreground">Title</th>
              <th class="px-4 py-3 text-left font-medium text-foreground">Status</th>
              <th class="px-4 py-3 text-left font-medium text-foreground">Отзывов</th>
              <th class="px-4 py-3 text-right font-medium text-foreground">View</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="place in places"
              :key="place.id"
              class="border-b border-border last:border-0 hover:bg-muted/30 transition-colors"
            >
              <td class="px-4 py-3 text-muted-foreground">{{ place.id }}</td>
              <td class="px-4 py-3 font-medium text-foreground">
                {{ place.title || `Место #${place.source_org_id}` }}
              </td>
              <td class="px-4 py-3">
                <Badge :variant="place.badge_variant as 'default' | 'secondary' | 'destructive' | 'outline'">
                  {{ place.status }}
                </Badge>
              </td>
              <td class="px-4 py-3 text-muted-foreground">{{ place.reviews_count }}</td>
              <td class="px-4 py-3 text-right">
                <Button as-child variant="ghost" size="sm">
                  <Link :href="`/reviews/${place.id}`" class="inline-flex items-center gap-1.5">
                    <Eye class="w-4 h-4" />
                    View
                  </Link>
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="places.length === 0" class="py-12 text-center text-muted-foreground">
        Нет мест
      </div>
    </div>
  </AppLayout>
</template>
