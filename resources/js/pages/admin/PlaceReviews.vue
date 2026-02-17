<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { ArrowLeft, ChevronLeft, ChevronRight, MapPin } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

type Review = {
    id: number;
    user_name: string;
    rating: number;
    review: string | null;
    published_at: string | null;
};

type PaginatedReviews = {
    data: Review[];
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
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
        place: {
            id: number;
            source_org_id: number;
            title: string | null;
            source_url?: string | null;
            rating_value?: number | null;
            rating_count?: number | null;
        };
        reviews?: PaginatedReviews;
        filters?: { rating?: string };
    }>(),
    {
        reviews: () => ({
            data: [],
            links: {} as PaginatedReviews['links'],
            meta: {} as PaginatedReviews['meta'],
        }),
        filters: () => ({}),
    },
);

const reviewsList = computed(() => props.reviews?.data ?? []);
const pagination = computed(() => props.reviews?.meta ?? null);

const ratingFilter = ref(props.filters?.rating ?? '');

const ratingOptions = [
    { value: '', label: 'Все рейтинги' },
    { value: '5', label: '5 звёзд' },
    { value: '4', label: '4 звезды' },
    { value: '3', label: '3 звезды' },
    { value: '2', label: '2 звезды' },
    { value: '1', label: '1 звезда' },
];

const placeId = computed(() => props.place?.id);

function buildParams(extra?: Record<string, string | number>) {
    const params: Record<string, string | number> = { ...extra };
    if (ratingFilter.value) params.rating = ratingFilter.value;
    return params;
}

function applyRatingFilter() {
    const id = placeId.value;
    if (id == null) return;
    router.get(`/reviews/${id}`, buildParams(), { preserveState: true });
}

watch(
    () => props.filters?.rating,
    (v) => {
        ratingFilter.value = v ?? '';
    },
    { immediate: true },
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Отзывы',
        href: '/reviews',
    },
    {
        title:
            props.place?.title || `Место #${props.place?.source_org_id ?? ''}`,
        href: placeId.value != null ? `/reviews/${placeId.value}` : '/reviews',
    },
]);

const renderStars = (rating: number) => {
    return Array.from({ length: 5 }, (_, i) => i < rating);
};

/** Возвращает 'full' | 'half' | 'empty' для каждой из 5 звёзд. Если avg > 4 — 4.5 звёзды, если 4 — 4 звёзды. */
const renderAverageStars = (
    avg: number | null | undefined,
): ('full' | 'half' | 'empty')[] => {
    if (avg == null || avg <= 0)
        return ['empty', 'empty', 'empty', 'empty', 'empty'];
    if (avg >= 4.5) return ['full', 'full', 'full', 'full', 'half'];
    if (avg > 4) return ['full', 'full', 'full', 'full', 'half'];
    if (avg >= 4) return ['full', 'full', 'full', 'full', 'empty'];
    const full = Math.floor(avg);
    const hasHalf = avg - full >= 0.5;
    return Array.from({ length: 5 }, (_, i) => {
        if (i < full) return 'full';
        if (i === full && hasHalf) return 'half';
        return 'empty';
    });
};

const averageRating = computed(() => props.place?.rating_value ?? null);
const totalReviews = computed(
    () => props.place?.rating_count ?? pagination.value?.total ?? 0,
);

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
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button as-child variant="ghost" size="sm">
                        <Link
                            href="/reviews"
                            class="inline-flex items-center gap-2"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Назад
                        </Link>
                    </Button>
                    <h1 class="text-2xl font-bold text-foreground">
                        {{ place.title || `Место #${place.source_org_id}` }}
                    </h1>
                </div>
            </div>

            <div class="h-px bg-border" />
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <select
                        id="rating-filter"
                        v-model="ratingFilter"
                        @change="applyRatingFilter"
                        class="h-9 min-w-[140px] rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1"
                    >
                        <option
                            v-for="opt in ratingOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
                <a
                    :href="place.source_url ?? `https://yandex.ru/maps/org/${place.source_org_id}/`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-white px-3 py-1.5 text-sm font-medium text-foreground shadow-xs transition-colors outline-none hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <svg
                        width="13"
                        height="16"
                        viewBox="0 0 13 16"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M6.0209 0C2.69556 0 0 2.69556 0 6.0209C0 7.68297 0.673438 9.1879 1.76262 10.2774C2.8521 11.3675 5.41881 12.9449 5.56934 14.6007C5.5919 14.849 5.77164 15.0523 6.0209 15.0523C6.27017 15.0523 6.4499 14.849 6.47247 14.6007C6.62299 12.9449 9.1897 11.3675 10.2792 10.2774C11.3684 9.1879 12.0418 7.68297 12.0418 6.0209C12.0418 2.69556 9.34625 0 6.0209 0Z"
                            fill="#FF4433"
                        />
                    </svg>
                    <svg
                        width="5"
                        height="5"
                        viewBox="0 0 5 5"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                        style="position: relative; bottom: 1px; right: 15px"
                    >
                        <path
                            d="M2.10732 4.21463C3.27116 4.21463 4.21461 3.27115 4.21461 2.10732C4.21461 0.943476 3.27116 0 2.10732 0C0.943485 0 0 0.943476 0 2.10732C0 3.27115 0.943485 4.21463 2.10732 4.21463Z"
                            fill="white"
                        />
                    </svg>
                    <span>Яндекс Карты</span>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_280px]">
                <!-- Левая колонка: список отзывов -->
                <div class="flex min-w-0 flex-col gap-4">
                    <!--                    <div class="flex flex-wrap items-center gap-4"></div>-->

                    <div class="space-y-4">
                        <div
                            v-for="review in reviewsList"
                            :key="review.id"
                            class="rounded-xl bg-white p-3 shadow-sm"
                        >
                            <div class="rounded-lg bg-[#F6F8FA] p-4">
                                <div
                                    class="mb-3 flex items-start justify-between gap-2"
                                >
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="text-sm leading-relaxed font-bold whitespace-pre-line text-foreground"
                                            >{{ review.published_at }}</span
                                        >
                                        <span
                                            class="text-sm leading-relaxed font-bold whitespace-pre-line text-foreground"
                                            >{{ place.title }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-shrink-0 items-center gap-1"
                                    >
                                        <svg
                                            v-for="(
                                                filled, index
                                            ) in renderStars(review.rating)"
                                            :key="index"
                                            class="h-4 w-4"
                                            :class="
                                                filled
                                                    ? 'fill-current text-yellow-400'
                                                    : 'fill-current text-gray-300'
                                            "
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                            />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="text-sm leading-relaxed whitespace-pre-line text-foreground"
                                    >
                                        {{ review.user_name }}
                                        <br />

                                        {{
                                            getDisplayText(
                                                review.review ?? '',
                                                review.id,
                                            )
                                        }}
                                    </p>
                                    <button
                                        v-if="
                                            shouldShowExpand(
                                                review.review ?? '',
                                            )
                                        "
                                        @click="toggleExpand(review.id)"
                                        class="mt-1 text-sm font-medium text-primary transition-colors hover:text-primary/80"
                                    >
                                        {{
                                            isExpanded(review.id)
                                                ? 'Свернуть'
                                                : 'Развернуть'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="reviewsList.length === 0"
                        class="py-12 text-center text-muted-foreground"
                    >
                        Нет отзывов
                    </div>

                    <div
                        v-if="pagination && pagination.last_page > 1"
                        class="flex flex-wrap items-center justify-between gap-4"
                    >
                        <p class="text-sm text-muted-foreground">
                            Показано {{ pagination.from ?? 0 }}–{{
                                pagination.to ?? 0
                            }}
                            из {{ pagination.total }}
                        </p>
                        <div class="flex items-center gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!reviews?.links?.prev"
                                @click="
                                    reviews?.links?.prev &&
                                    router.visit(reviews.links.prev)
                                "
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <span class="px-3 text-sm text-muted-foreground">
                                {{ pagination.current_page }} /
                                {{ pagination.last_page }}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!reviews?.links?.next"
                                @click="
                                    reviews?.links?.next &&
                                    router.visit(reviews.links.next)
                                "
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Правая колонка: плашка с рейтингом -->
                <div class="lg:sticky lg:top-6 lg:self-start">
                    <div
                        class="rounded-lg border border-border bg-card p-5 shadow-sm"
                    >
                        <div
                            class="flex flex-col items-center gap-3 text-center"
                        >
                            <div class="flex items-center gap-0.5">
                                <span
                                    v-if="averageRating != null"
                                    class="pr-2 text-3xl font-bold text-foreground"
                                >
                                    {{ Number(averageRating).toFixed(1) }}
                                </span>
                                <template
                                    v-for="(type, idx) in renderAverageStars(
                                        averageRating ?? 0,
                                    )"
                                    :key="idx"
                                >
                                    <svg
                                        v-if="type === 'full'"
                                        class="h-6 w-6 fill-current text-yellow-400"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        />
                                    </svg>
                                    <svg
                                        v-else-if="type === 'half'"
                                        class="h-6 w-6 text-yellow-400"
                                        viewBox="0 0 20 20"
                                    >
                                        <defs>
                                            <linearGradient
                                                :id="`half-star-${placeId ?? ''}`"
                                            >
                                                <stop
                                                    offset="50%"
                                                    stop-color="currentColor"
                                                />
                                                <stop
                                                    offset="50%"
                                                    stop-color="#d1d5db"
                                                    stop-opacity="1"
                                                />
                                            </linearGradient>
                                        </defs>
                                        <path
                                            :fill="`url(#half-star-${placeId ?? ''})`"
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-6 w-6 fill-current text-gray-300"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                        />
                                    </svg>
                                </template>
                            </div>
                            <div class="w-full border-t border-border" />
                            <p class="text-sm text-muted-foreground">
                                Всего отзывов:
                                {{ totalReviews.toLocaleString('ru-RU') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
