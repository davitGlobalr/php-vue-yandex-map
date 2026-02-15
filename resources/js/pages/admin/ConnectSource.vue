<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Подключить Яндекс',
    href: '/connect',
  },
];

const exampleUrl = 'https://yandex.com/maps/org/grill_am/215796158208/reviews/?ll=44.568090%2C40.201624&z=17';

const form = useForm({
  source_url: exampleUrl,
});

const successMessage = computed(() => page.props.flash?.success as string | undefined);

const submit = () => {
  form.post('/connect', {
    preserveScroll: true,
    onSuccess: () => {
      // Оставляем форму с примером URL после успешной отправки
      form.source_url = exampleUrl;
    },
  });
};
</script>

<template>
  <Head title="Подключить Яндекс" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
      <div>
        <h1 class="text-2xl font-bold text-foreground">Подключить Яндекс</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Укажите ссылку на Яндекс, пример
          <a
            :href="exampleUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="text-primary hover:underline"
          >
            {{ exampleUrl }}
          </a>
        </p>
      </div>

      <div class="h-px bg-border" />

      <div
        v-if="successMessage"
        class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4"
      >
        <p class="text-sm font-medium text-green-800 dark:text-green-200">
          {{ successMessage }}
        </p>
      </div>

      <div class="max-w-2xl">
        <form @submit.prevent="submit" class="space-y-4">
          <div class="space-y-2">
            <Label for="source_url">Ссылка на Яндекс</Label>
            <Input
              id="source_url"
              v-model="form.source_url"
              type="url"
              :placeholder="exampleUrl"
              class="w-full"
              :class="{ 'border-destructive': form.errors.source_url }"
            />
            <p v-if="form.errors.source_url" class="text-sm text-destructive">
              {{ form.errors.source_url }}
            </p>
          </div>

          <Button
            type="submit"
            :disabled="form.processing"
            class="w-full sm:w-auto"
          >
            <span v-if="form.processing">Сохранение...</span>
            <span v-else>Сохранить</span>
          </Button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
