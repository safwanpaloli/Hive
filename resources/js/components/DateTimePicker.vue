<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [Date, null], default: null },
    id: { type: String, default: undefined },
    invalid: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const rootRef = ref(null);
const viewDate = ref(new Date());
const hour = ref(9);
const minute = ref(0);
const meridiem = ref('AM');

const WEEKDAYS = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];

const formatted = computed(() => {
    if (!props.modelValue) return '';
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(props.modelValue));
});

const monthLabel = computed(() =>
    viewDate.value.toLocaleString(undefined, { month: 'long', year: 'numeric' }),
);

const calendarGrid = computed(() => {
    const year = viewDate.value.getFullYear();
    const month = viewDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lead = (firstDay.getDay() + 6) % 7;
    const totalDays = new Date(year, month + 1, 0).getDate();
    const cells = [];

    for (let i = 0; i < lead; i += 1) cells.push(null);
    for (let d = 1; d <= totalDays; d += 1) cells.push(d);
    while (cells.length % 7 !== 0) cells.push(null);

    return cells;
});

watch(
    () => props.modelValue,
    (value) => {
        if (!value) return;
        const d = new Date(value);
        hour.value = d.getHours() % 12 || 12;
        minute.value = d.getMinutes();
        meridiem.value = d.getHours() >= 12 ? 'PM' : 'AM';
        viewDate.value = new Date(d.getFullYear(), d.getMonth(), 1);
    },
    { immediate: true },
);

function hour24() {
    return meridiem.value === 'PM' ? (hour.value % 12) + 12 : hour.value % 12;
}

function selectDay(day) {
    const d = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth(), day, hour24(), minute.value, 0, 0);
    emit('update:modelValue', d);
}

function onTimeChange() {
    if (!props.modelValue) return;
    const d = new Date(props.modelValue);
    d.setHours(hour24(), minute.value, 0, 0);
    emit('update:modelValue', d);
}

function shiftMonth(delta) {
    viewDate.value = new Date(viewDate.value.getFullYear(), viewDate.value.getMonth() + delta, 1);
}

function applyPreset(daysFromToday) {
    const now = new Date();
    const source = props.modelValue ? new Date(props.modelValue) : now;
    const d = new Date(source.getFullYear(), source.getMonth(), source.getDate() + daysFromToday);
    d.setHours(source.getHours(), source.getMinutes(), 0, 0);
    emit('update:modelValue', d);
}

function clearValue() {
    emit('update:modelValue', null);
    open.value = false;
}

function isToday(day) {
    const d = new Date();
    return (
        viewDate.value.getFullYear() === d.getFullYear() &&
        viewDate.value.getMonth() === d.getMonth() &&
        day === d.getDate()
    );
}

function isSelected(day) {
    if (!props.modelValue) return false;
    const d = new Date(props.modelValue);
    return (
        viewDate.value.getFullYear() === d.getFullYear() &&
        viewDate.value.getMonth() === d.getMonth() &&
        day === d.getDate()
    );
}

function dayClass(day) {
    if (day === null) return 'invisible';
    if (isSelected(day)) return 'bg-brand-600 font-semibold text-white';
    if (isToday(day)) return 'font-semibold text-brand-600 ring-1 ring-inset ring-brand-200';
    return 'text-slate-600 hover:bg-slate-100';
}

function toggle() {
    if (!open.value && props.modelValue) {
        const d = new Date(props.modelValue);
        viewDate.value = new Date(d.getFullYear(), d.getMonth(), 1);
    }
    open.value = !open.value;
}

function onDocClick(e) {
    if (rootRef.value && !rootRef.value.contains(e.target)) {
        open.value = false;
    }
}

function onKeydown(e) {
    if (e.key === 'Escape') open.value = false;
}

onMounted(() => {
    document.addEventListener('click', onDocClick);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick);
    document.removeEventListener('keydown', onKeydown);
});

const hourOptions = Array.from({ length: 12 }, (_, i) => i + 1);
const minuteOptions = Array.from({ length: 12 }, (_, i) => String(i * 5).padStart(2, '0'));
</script>

<template>
    <div ref="rootRef" class="relative">
        <button
            type="button"
            :id="id"
            :aria-expanded="open"
            class="flex w-full items-center justify-between gap-2 rounded-xl border-0 bg-white px-3.5 py-2.5 text-left text-sm shadow-sm ring-1 ring-inset transition focus:outline-none focus:ring-2"
            :class="invalid ? 'ring-rose-300 focus:ring-rose-500' : 'ring-slate-300 focus:ring-brand-500'"
            @click="toggle"
        >
            <span
                class="flex min-w-0 items-center gap-2"
                :class="modelValue ? 'font-medium text-slate-800' : 'text-slate-400'"
            >
                <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path
                        stroke-linecap="round" stroke-linejoin="round"
                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008z"
                    />
                </svg>
                <span class="truncate">{{ modelValue ? formatted : 'Pick a date & time' }}</span>
            </span>
            <svg
                class="size-4 shrink-0 text-slate-400 transition-transform"
                :class="open ? 'rotate-180' : ''"
                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <button
            v-if="modelValue"
            type="button"
            class="absolute right-9 top-1/2 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-rose-600"
            title="Clear schedule"
            @click.stop="clearValue"
        >
            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <Transition name="picker">
            <div
                v-if="open"
                class="absolute left-0 right-0 z-50 mt-2 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-slate-200"
            >
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="preset in [
                            { label: 'Today', days: 0 },
                            { label: 'Tomorrow', days: 1 },
                            { label: '+1 week', days: 7 },
                        ]"
                        :key="preset.label"
                        type="button"
                        class="rounded-lg px-2.5 py-1 text-xs font-medium text-brand-600 transition hover:bg-brand-50"
                        @click="applyPreset(preset.days)"
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-800">{{ monthLabel }}</span>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                            aria-label="Previous month"
                            @click="shiftMonth(-1)"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                            aria-label="Next month"
                            @click="shiftMonth(1)"
                        >
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-2 grid grid-cols-7 gap-1 text-center">
                    <span
                        v-for="(wd, i) in WEEKDAYS"
                        :key="i"
                        class="text-[11px] font-medium text-slate-400"
                    >
                        {{ wd }}
                    </span>
                </div>

                <div class="mt-1 grid grid-cols-7 gap-1 text-center">
                    <button
                        v-for="(day, i) in calendarGrid"
                        :key="i"
                        type="button"
                        :disabled="day === null"
                        class="h-8 rounded-lg text-sm transition"
                        :class="dayClass(day)"
                        @click="selectDay(day)"
                    >
                        {{ day }}
                    </button>
                </div>

                <div class="mt-3 border-t border-slate-100 pt-3">
                    <span class="text-xs font-medium text-slate-500">Time</span>
                    <div class="mt-1.5 flex items-center gap-2">
                        <select
                            v-model.number="hour"
                            class="rounded-lg border-0 bg-slate-50 px-2 py-1.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500"
                            @change="onTimeChange"
                        >
                            <option v-for="h in hourOptions" :key="h" :value="h">{{ h }}</option>
                        </select>
                        <span class="font-mono text-sm text-slate-400">:</span>
                        <select
                            v-model="minute"
                            class="rounded-lg border-0 bg-slate-50 px-2 py-1.5 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-brand-500"
                            @change="onTimeChange"
                        >
                            <option v-for="m in minuteOptions" :key="m" :value="Number(m)">{{ m }}</option>
                        </select>
                        <div class="ml-auto flex overflow-hidden rounded-lg ring-1 ring-inset ring-slate-200">
                            <button
                                type="button"
                                class="px-3 py-1.5 text-sm font-medium transition"
                                :class="meridiem === 'AM' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'"
                                @click="meridiem = 'AM'; onTimeChange()"
                            >
                                AM
                            </button>
                            <button
                                type="button"
                                class="px-3 py-1.5 text-sm font-medium transition"
                                :class="meridiem === 'PM' ? 'bg-brand-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'"
                                @click="meridiem = 'PM'; onTimeChange()"
                            >
                                PM
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex justify-end border-t border-slate-100 pt-3">
                    <button
                        type="button"
                        class="rounded-lg px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                        @click="clearValue"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.picker-enter-active,
.picker-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.picker-enter-from,
.picker-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
