<script setup lang="ts">
import { VueDatePicker } from '@vuepic/vue-datepicker';
import { enGB } from 'date-fns/locale';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        id: string;
        name?: string;
        required?: boolean;
        disabled?: boolean;
        /** When true, shows time columns (12h) and expects ISO-like strings with time. */
        enableTimePicker?: boolean;
        class?: string;
    }>(),
    {
        name: undefined,
        required: false,
        disabled: false,
        enableTimePicker: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function parseToDate(v: string): Date | null {
    if (!v || typeof v !== 'string') return null;
    const trimmed = v.trim();
    const datePart = trimmed.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!datePart) return null;
    const y = Number(datePart[1]);
    const mo = Number(datePart[2]);
    const d = Number(datePart[3]);
    if (props.enableTimePicker) {
        const time = trimmed.match(/[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
        if (time) {
            const hh = Number(time[1]);
            const mm = Number(time[2]);
            const ss = time[3] ? Number(time[3]) : 0;
            return new Date(y, mo - 1, d, hh, mm, ss);
        }
    }
    return new Date(y, mo - 1, d);
}

function formatToApi(d: Date | null): string {
    if (!d) return '';
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    if (props.enableTimePicker) {
        const hh = String(d.getHours()).padStart(2, '0');
        const mm = String(d.getMinutes()).padStart(2, '0');
        const ss = String(d.getSeconds()).padStart(2, '0');
        return `${y}-${m}-${day}T${hh}:${mm}:${ss}`;
    }
    return `${y}-${m}-${day}`;
}

const internal = computed({
    get: () => parseToDate(props.modelValue),
    set: (v: Date | null) => emit('update:modelValue', formatToApi(v)),
});

const minDate = computed(() => {
    const now = new Date();
    if (props.enableTimePicker) return now;
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
});

function isSameDay(a: Date, b: Date): boolean {
    return (
        a.getFullYear() === b.getFullYear()
        && a.getMonth() === b.getMonth()
        && a.getDate() === b.getDate()
    );
}

const minTime = computed(() => {
    if (!props.enableTimePicker) return undefined;
    const selected = internal.value;
    if (!selected) return undefined;
    const now = new Date();
    if (!isSameDay(selected, now)) return undefined;
    return {
        hours: now.getHours(),
        minutes: now.getMinutes(),
        seconds: 0,
    };
});

const formats = computed(() => ({
    input: props.enableTimePicker ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy',
    preview: props.enableTimePicker ? 'dd/MM/yyyy HH:mm' : 'dd/MM/yyyy',
    month: 'LLLL',
    year: 'yyyy',
    weekDay: 'EEEEE',
    day: 'd',
    quarter: 'QQQ',
}));

const actionRow = {
    showSelect: false,
    showCancel: true,
    showNow: true,
    cancelBtnLabel: 'Clear',
    nowBtnLabel: 'Today',
};

const timeConfig = computed(() =>
    props.enableTimePicker
        ? {
              is24: false,
              enableSeconds: false,
          }
        : undefined,
);

function dayClass(date: Date, _mv: unknown): string {
    const t = new Date();
    t.setHours(0, 0, 0, 0);
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    if (d.getTime() === t.getTime()) return 'tabulator-dp-day--today';
    return '';
}
</script>

<template>
    <div :class="cn('tabulator-datepicker w-full', props.class)">
        <label
            :for="id"
            class="mb-1.5 block text-xs font-medium text-[#594048]"
        >
            {{ label }}
            <span v-if="required" class="text-destructive">*</span>
        </label>
        <VueDatePicker
            v-model="internal"
            :time-picker="enableTimePicker"
            :time-config="timeConfig"
            :min-date="minDate"
            :min-time="minTime"
            :formats="formats"
            :locale="enGB"
            :week-start="0"
            auto-apply
            text-input
            :teleport="true"
            :disabled="disabled"
            :action-row="actionRow"
            :ui="{
                input: 'tabulator-dp__input',
                menu: 'tabulator-dp__menu',
                calendar: 'tabulator-dp__calendar',
                calendarCell: 'tabulator-dp__calendar-cell',
                navBtnNext: 'tabulator-dp__nav-btn',
                navBtnPrev: 'tabulator-dp__nav-btn',
                dayClass,
            }"
            :input-attrs="{
                id,
                name: name ?? id,
                required: !!required,
                clearable: true,
                autocomplete: 'off',
            }"
            class="tabulator-dp-root"
        />
    </div>
</template>
