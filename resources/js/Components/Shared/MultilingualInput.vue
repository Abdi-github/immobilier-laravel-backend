<script setup lang="ts">
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

interface Props {
    modelValue: Record<string, string>;
    label?: string;
    type?: 'text' | 'textarea';
    required?: boolean;
    errors?: Record<string, string>;
    languages?: string[];
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    required: false,
    languages: () => ['en', 'fr', 'de', 'it'],
});

const emit = defineEmits<{
    'update:modelValue': [value: Record<string, string>];
}>();

const activeTab = ref<string>('en');

const languageLabels: Record<string, string> = {
    en: 'English',
    fr: 'Français',
    de: 'Deutsch',
    it: 'Italiano',
};

function updateValue(lang: string, value: string | undefined) {
    emit('update:modelValue', { ...props.modelValue, [lang]: value ?? '' });
}
</script>

<template>
    <div>
        <label v-if="label" class="mb-1 block text-sm font-medium">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <Tabs v-model:value="activeTab">
            <TabList>
                <Tab v-for="lang in languages" :key="lang" :value="lang">
                    {{ languageLabels[lang] ?? lang.toUpperCase() }}
                </Tab>
            </TabList>
            <TabPanels>
                <TabPanel v-for="lang in languages" :key="lang" :value="lang">
                    <Textarea
                        v-if="type === 'textarea'"
                        :modelValue="modelValue[lang] ?? ''"
                        @update:modelValue="updateValue(lang, $event)"
                        rows="3"
                        class="w-full"
                        :invalid="!!errors?.[`name.${lang}`]"
                        :data-testid="`multilingual-${lang}`"
                    />
                    <InputText
                        v-else
                        :modelValue="modelValue[lang] ?? ''"
                        @update:modelValue="updateValue(lang, $event)"
                        class="w-full"
                        :invalid="!!errors?.[`name.${lang}`]"
                        :data-testid="`multilingual-${lang}`"
                    />
                    <small v-if="errors?.[`name.${lang}`]" class="text-red-500">{{ errors[`name.${lang}`] }}</small>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>
