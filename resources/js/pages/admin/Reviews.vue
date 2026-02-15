<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Place = {
  id: number;
  source_org_id: number;
  title: string | null;
  status: string;
  status_value: number;
  badge_variant: string;
  reviews_count: number;
};

type PaginatedPlaces = {
  data: Place[];
  links: { first: string; last: string; prev: string | null; next: string | null };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
};

const props = withDefaults(
  defineProps<{
    places?: PaginatedPlaces;
    filters?: { search?: string };
  }>(),
  {
    places: () => ({ data: [], links: {} as PaginatedPlaces['links'], meta: {} as PaginatedPlaces['meta'] }),
    filters: () => ({}),
  }
);

const placesList = computed(() => props.places?.data ?? []);
const pagination = computed(() => props.places?.meta ?? null);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Отзывы',
    href: '/reviews',
  },
];

const search = ref(props.filters?.search ?? '');

function applySearch() {
  const params: Record<string, string | number> = {};
  if (search.value.trim()) params.search = search.value.trim();
  router.get('/reviews', params, { preserveState: true });
}

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(applySearch, 300);
});
</script>

<template>
  <Head title="Отзывы" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <div>
        <h1 class="text-2xl font-bold text-foreground">Отзывы</h1>
        <p class="mt-1 text-sm text-muted-foreground">Список мест и их отзывов</p>
      </div>

      <div class="flex max-w-md items-center gap-2">
        <Input
          id="search"
          v-model="search"
          type="text"
          placeholder="Поиск по Source ID или Title"
          class="h-9"
        />
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
              v-for="place in placesList"
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

      <div v-if="placesList.length === 0" class="py-12 text-center text-muted-foreground">
        Нет мест
      </div>

      <div
        v-if="pagination && pagination.last_page > 1"
        class="flex flex-wrap items-center justify-between gap-4"
      >
        <p class="text-sm text-muted-foreground">
          Показано {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} из {{ pagination.total }}
        </p>
        <div class="flex items-center gap-1">
          <Button
            variant="outline"
            size="sm"
            :disabled="!places.links?.prev"
            @click="places.links?.prev && router.visit(places.links.prev)"
          >
            <ChevronLeft class="h-4 w-4" />
          </Button>
          <span class="px-3 text-sm text-muted-foreground">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <Button
            variant="outline"
            size="sm"
            :disabled="!places.links?.next"
            @click="places.links?.next && router.visit(places.links.next)"
          >
            <ChevronRight class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
