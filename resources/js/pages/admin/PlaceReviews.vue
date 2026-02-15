<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';

const props = withDefaults(
  defineProps<{
    place: {
      id: number;
      source_org_id: number;
      title: string | null;
    };
    reviews?: Array<{
      id: number;
      user_name: string;
      rating: number;
      review: string | null;
      published_at: string | null;
    }>;
  }>(),
  { reviews: () => [] }
);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Отзывы',
    href: '/reviews',
  },
  {
    title: props.place.title || `Место #${props.place.source_org_id}`,
    href: `/reviews/${props.place.id}`,
  },
];

const renderStars = (rating: number) => {
  return Array.from({ length: 5 }, (_, i) => i < rating);
};

const expandedReviews = ref<Set<number>>(new Set());
const textMaxLength = 200;

const toggleExpand = (reviewId: number) => {
  if (expandedReviews.value.has(reviewId)) {
    expandedReviews.value.delete(reviewId);
  } else {
    expandedReviews.value.add(reviewId);
  }
};

const isExpanded = (reviewId: number) => expandedReviews.value.has(reviewId);
const shouldShowExpand = (text: string) => text.length > textMaxLength;
const getDisplayText = (text: string, reviewId: number) => {
  if (isExpanded(reviewId) || !shouldShowExpand(text)) return text;
  return text.substring(0, textMaxLength) + '...';
};
</script>

<template>
  <Head :title="place.title || `Место #${place.source_org_id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Button as-child variant="ghost" size="sm">
            <Link href="/reviews" class="inline-flex items-center gap-2">
              <ArrowLeft class="w-4 h-4" />
              Назад
            </Link>
          </Button>
          <div>
            <h1 class="text-2xl font-bold text-foreground">
              {{ place.title || `Место #${place.source_org_id}` }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
              Отзывов: {{ reviews.length }}
            </p>
          </div>
        </div>
      </div>

      <div class="h-px bg-border" />

      <div class="space-y-4">
        <div
          v-for="review in reviews"
          :key="review.id"
          class="rounded-lg border border-border bg-card p-4 shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="mb-3 flex items-start justify-between gap-2">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-semibold text-foreground">{{ review.user_name }}</span>
              <span class="text-xs text-muted-foreground">{{ review.published_at }}</span>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <svg
                v-for="(filled, index) in renderStars(review.rating)"
                :key="index"
                class="w-4 h-4"
                :class="filled ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'"
                viewBox="0 0 20 20"
              >
                <path
                  d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                />
              </svg>
            </div>
          </div>
          <div>
            <p class="text-sm text-foreground leading-relaxed whitespace-pre-line">
              {{ getDisplayText(review.review ?? '', review.id) }}
            </p>
            <button
              v-if="shouldShowExpand(review.review ?? '')"
              @click="toggleExpand(review.id)"
              class="mt-1 text-sm text-primary hover:text-primary/80 font-medium transition-colors"
            >
              {{ isExpanded(review.id) ? 'Свернуть' : 'Развернуть' }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="reviews.length === 0" class="py-12 text-center text-muted-foreground">
        Нет отзывов
      </div>
    </div>
  </AppLayout>
</template>
