<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type EvalCase = {
    id: number;
    label: string;
    input: string;
    expect_contains: string;
};

type Trace = {
    id: number;
    output: string;
    passed: boolean;
    latency_ms: number;
    eval_case: EvalCase;
};

type EvalRun = {
    id: number;
    passed: number;
    failed: number;
    model_name: string;
    created_at: string;
    traces: Trace[];
};

type Suite = {
    id: number;
    name: string;
    system_prompt: string;
    cases_count: number;
};

const props = defineProps<{
    suites: Suite[];
    selectedSuiteId: number | null;
    latestRun: EvalRun | null;
}>();

const selectedSuiteId = ref(props.selectedSuiteId);
const latestRun = ref<EvalRun | null>(props.latestRun);
const isRunning = ref(false);
const errorMessage = ref('');

const selectedSuite = computed(
    () =>
        props.suites.find((suite) => suite.id === selectedSuiteId.value) ??
        null,
);
const totalCases = computed(
    () =>
        latestRun.value?.traces.length ?? selectedSuite.value?.cases_count ?? 0,
);
const score = computed(() => {
    if (!latestRun.value || totalCases.value === 0) return 0;
    return Math.round((latestRun.value.passed / totalCases.value) * 100);
});

function selectSuite(event: Event) {
    const id = Number((event.target as HTMLSelectElement).value);
    selectedSuiteId.value = id;
    latestRun.value = null;
    errorMessage.value = '';
    router.get(
        '/',
        { suite: id },
        { preserveScroll: true, preserveState: false },
    );
}

async function runEvaluation() {
    if (!selectedSuiteId.value || isRunning.value) return;

    isRunning.value = true;
    errorMessage.value = '';

    try {
        const token = document.querySelector<HTMLMetaElement>(
            'meta[name="csrf-token"]',
        )?.content;
        const response = await fetch('/eval-runs', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: JSON.stringify({ eval_suite_id: selectedSuiteId.value }),
        });

        if (!response.ok) {
            throw new Error(
                'The evaluation could not be completed. Please try again.',
            );
        }

        const payload = (await response.json()) as { run: EvalRun };
        latestRun.value = payload.run;
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Something went wrong. Please try again.';
    } finally {
        isRunning.value = false;
    }
}

function formattedDate(value: string) {
    return new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
</script>

<template>
    <Head title="Prompt Eval Lab" />

    <div class="min-h-screen bg-[#f6f7f9] text-[#20252d]">
        <header class="border-b border-[#e7e9ed] bg-white">
            <div
                class="mx-auto flex h-[68px] max-w-[1440px] items-center justify-between px-5 sm:px-8"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="Prompt Eval Lab home"
                >
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#302dca] text-white shadow-sm shadow-indigo-200"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="h-5 w-5"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 5.5h16M4 12h10M4 18.5h6"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                            />
                            <circle cx="18" cy="12" r="2" fill="currentColor" />
                            <circle
                                cx="14"
                                cy="18.5"
                                r="2"
                                fill="currentColor"
                            />
                        </svg>
                    </span>
                    <span class="text-[15px] font-semibold tracking-[-0.02em]"
                        >Prompt Eval Lab</span
                    >
                    <span
                        class="hidden rounded-md bg-[#f1f0ff] px-2 py-1 text-[10px] font-semibold tracking-[0.12em] text-[#5149c8] uppercase sm:inline-flex"
                        >Workspace</span
                    >
                </a>

                <div
                    class="flex items-center gap-3 text-xs font-medium text-[#737b87]"
                >
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-[#e5e8ec] bg-white px-3 py-1.5"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full bg-[#32a879]"
                        ></span>
                        Runner ready
                    </span>
                    <span
                        class="hidden h-8 w-8 items-center justify-center rounded-full bg-[#e9e8ff] text-[11px] font-bold text-[#5048bb] sm:flex"
                        >PL</span
                    >
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-5 pt-9 pb-16 sm:px-8 sm:pt-12">
            <div
                class="mb-8 flex flex-col justify-between gap-5 lg:flex-row lg:items-end"
            >
                <div>
                    <div
                        class="mb-3 flex items-center gap-2 text-[11px] font-semibold tracking-[0.15em] text-[#777f8b] uppercase"
                    >
                        <span class="text-[#5149c8]">Evaluation</span>
                        <span class="text-[#b5bac2]">/</span>
                        <span>Prompt quality</span>
                    </div>
                    <h1
                        class="text-[30px] font-semibold tracking-[-0.045em] text-[#20252d] sm:text-[36px]"
                    >
                        Evaluation workspace
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[#737b87]">
                        Run a prompt against real-world fixtures. Inspect every
                        response, assertion, and trace in one place.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <label for="suite-picker" class="sr-only"
                        >Evaluation suite</label
                    >
                    <div class="relative">
                        <select
                            id="suite-picker"
                            :value="selectedSuiteId ?? ''"
                            class="h-11 min-w-[190px] appearance-none rounded-lg border border-[#dfe2e7] bg-white py-2 pr-10 pl-3.5 text-sm font-medium text-[#303641] shadow-sm transition outline-none focus:border-[#7168e8] focus:ring-3 focus:ring-[#7168e8]/10"
                            @change="selectSuite"
                        >
                            <option
                                v-for="suite in suites"
                                :key="suite.id"
                                :value="suite.id"
                            >
                                {{ suite.name }}
                            </option>
                        </select>
                        <svg
                            class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-[#777f8b]"
                            viewBox="0 0 20 20"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="m5 7.5 5 5 5-5"
                                stroke="currentColor"
                                stroke-width="1.6"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#3833ca] px-4 text-sm font-semibold text-white shadow-sm shadow-indigo-200 transition hover:bg-[#2d28b1] focus:ring-3 focus:ring-[#7168e8]/30 focus:outline-none disabled:cursor-wait disabled:opacity-70"
                        :disabled="isRunning || !selectedSuite"
                        @click="runEvaluation"
                    >
                        <svg
                            v-if="isRunning"
                            class="h-4 w-4 animate-spin"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-opacity=".25"
                                stroke-width="3"
                            />
                            <path
                                d="M21 12a9 9 0 0 0-9-9"
                                stroke="currentColor"
                                stroke-width="3"
                                stroke-linecap="round"
                            />
                        </svg>
                        <svg
                            v-else
                            viewBox="0 0 20 20"
                            fill="none"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <path
                                d="m7 4.75 8 5.25-8 5.25V4.75Z"
                                fill="currentColor"
                            />
                        </svg>
                        {{
                            isRunning ? 'Running evaluation…' : 'Run evaluation'
                        }}
                    </button>
                </div>
            </div>

            <div
                v-if="errorMessage"
                role="alert"
                class="mb-5 rounded-lg border border-[#f0caca] bg-[#fff7f6] px-4 py-3 text-sm text-[#a33d39]"
            >
                {{ errorMessage }}
            </div>

            <section
                v-if="selectedSuite"
                class="mb-6 flex flex-col gap-3 rounded-xl border border-[#e5e7eb] bg-white px-5 py-4 shadow-[0_1px_2px_rgba(24,32,56,0.03)] sm:flex-row sm:items-start sm:gap-4"
                aria-label="Active system prompt"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#f1f0ff] text-[10px] font-bold tracking-wide text-[#5149c8]"
                    >SYS</span
                >
                <div class="min-w-0 flex-1">
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <h2 class="text-xs font-semibold text-[#454c57]">
                            System prompt
                        </h2>
                        <span
                            class="rounded bg-[#f5f6f8] px-1.5 py-0.5 text-[9px] font-medium tracking-[0.08em] text-[#8a929d] uppercase"
                            >{{ selectedSuite.name }}</span
                        >
                    </div>
                    <p class="text-xs leading-5 text-[#737b87]">
                        {{ selectedSuite.system_prompt }}
                    </p>
                </div>
                <span
                    class="shrink-0 text-[10px] font-medium text-[#9299a3] sm:pt-1"
                    >{{ selectedSuite.cases_count }} fixtures</span
                >
            </section>

            <section
                class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                aria-label="Evaluation summary"
            >
                <div
                    class="rounded-xl border border-[#e5e7eb] bg-white p-5 shadow-[0_1px_2px_rgba(24,32,56,0.03)]"
                >
                    <div class="flex items-start justify-between">
                        <p class="text-xs font-medium text-[#737b87]">
                            Latest score
                        </p>
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#f1f0ff] text-[#5149c8]"
                        >
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M3.5 15.5V11m6.5 4.5V5m6.5 10.5V8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span
                            class="text-[30px] font-semibold tracking-[-0.05em]"
                            >{{ latestRun ? `${score}%` : '—' }}</span
                        >
                        <span v-if="latestRun" class="text-xs text-[#9299a3]"
                            >{{ latestRun.passed }} of
                            {{ totalCases }} passed</span
                        >
                    </div>
                    <div
                        class="mt-3 h-1.5 overflow-hidden rounded-full bg-[#eef0f3]"
                    >
                        <div
                            class="h-full rounded-full bg-[#4e49d7] transition-all"
                            :style="{ width: `${score}%` }"
                        ></div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-[#e5e7eb] bg-white p-5 shadow-[0_1px_2px_rgba(24,32,56,0.03)]"
                >
                    <div class="flex items-start justify-between">
                        <p class="text-xs font-medium text-[#737b87]">Passed</p>
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#eaf7f1] text-[#25845d]"
                            ><svg
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="m4.5 10.5 3.5 3.5 7.5-8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                /></svg
                        ></span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span
                            class="text-[30px] font-semibold tracking-[-0.05em]"
                            >{{ latestRun?.passed ?? '—' }}</span
                        ><span class="text-xs text-[#9299a3]">assertions</span>
                    </div>
                    <p class="mt-3 text-xs text-[#9299a3]">
                        {{
                            latestRun
                                ? 'Matched expected response content'
                                : 'Run the suite to see results'
                        }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-[#e5e7eb] bg-white p-5 shadow-[0_1px_2px_rgba(24,32,56,0.03)]"
                >
                    <div class="flex items-start justify-between">
                        <p class="text-xs font-medium text-[#737b87]">Failed</p>
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#fff0ef] text-[#ca544d]"
                            ><svg
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="m6 6 8 8m0-8-8 8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                /></svg
                        ></span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span
                            class="text-[30px] font-semibold tracking-[-0.05em]"
                            >{{ latestRun?.failed ?? '—' }}</span
                        ><span class="text-xs text-[#9299a3]">assertions</span>
                    </div>
                    <p class="mt-3 text-xs text-[#9299a3]">
                        {{
                            latestRun?.failed
                                ? 'Review failed traces below'
                                : 'No failures in the latest run'
                        }}
                    </p>
                </div>

                <div
                    class="rounded-xl border border-[#e5e7eb] bg-white p-5 shadow-[0_1px_2px_rgba(24,32,56,0.03)]"
                >
                    <div class="flex items-start justify-between">
                        <p class="text-xs font-medium text-[#737b87]">Model</p>
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#eff5ff] text-[#4776bf]"
                            ><svg
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 2.75v2m0 10.5v2m7.25-7.25h-2m-10.5 0h-2m12.37-5.12-1.42 1.42m-7.4 7.4-1.42 1.42m10.24 0-1.42-1.42m-7.4-7.4L5.48 4.9M13.25 10a3.25 3.25 0 1 1-6.5 0 3.25 3.25 0 0 1 6.5 0Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                /></svg
                        ></span>
                    </div>
                    <div
                        class="mt-4 truncate text-[17px] font-semibold tracking-[-0.03em]"
                        :title="latestRun?.model_name"
                    >
                        {{ latestRun?.model_name ?? 'Not run yet' }}
                    </div>
                    <p class="mt-3 truncate text-xs text-[#9299a3]">
                        {{
                            latestRun
                                ? formattedDate(latestRun.created_at)
                                : 'Choose a suite and start a run'
                        }}
                    </p>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-xl border border-[#e5e7eb] bg-white shadow-[0_1px_2px_rgba(24,32,56,0.03)]"
            >
                <div
                    class="flex flex-col justify-between gap-3 border-b border-[#eceef1] px-5 py-4 sm:flex-row sm:items-center sm:px-6"
                >
                    <div>
                        <h2 class="text-sm font-semibold tracking-[-0.01em]">
                            Evaluation traces
                        </h2>
                        <p class="mt-1 text-xs text-[#858d98]">
                            {{ selectedSuite?.name ?? 'No suite selected' }}
                            <span class="mx-1.5 text-[#c1c5cc]">·</span>
                            {{
                                latestRun
                                    ? `Run #${latestRun.id}`
                                    : `${selectedSuite?.cases_count ?? 0} fixtures`
                            }}
                        </p>
                    </div>
                    <div
                        class="inline-flex w-fit items-center gap-2 rounded-md bg-[#f5f6f8] px-2.5 py-1.5 text-[11px] font-medium text-[#737b87]"
                    >
                        <span
                            class="h-1.5 w-1.5 rounded-full"
                            :class="latestRun ? 'bg-[#8b92a0]' : 'bg-[#d5a640]'"
                        ></span>
                        {{ latestRun ? 'Completed' : 'Waiting for run' }}
                    </div>
                </div>

                <div
                    v-if="latestRun && latestRun.traces.length"
                    class="overflow-x-auto"
                >
                    <table
                        class="w-full min-w-[860px] border-collapse text-left"
                    >
                        <thead class="border-b border-[#eceef1] bg-[#fafbfc]">
                            <tr>
                                <th
                                    class="px-6 py-3 text-[10px] font-semibold tracking-[0.1em] text-[#9097a2] uppercase"
                                >
                                    Fixture
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-semibold tracking-[0.1em] text-[#9097a2] uppercase"
                                >
                                    Input
                                </th>
                                <th
                                    class="px-5 py-3 text-[10px] font-semibold tracking-[0.1em] text-[#9097a2] uppercase"
                                >
                                    Model output
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-[10px] font-semibold tracking-[0.1em] text-[#9097a2] uppercase"
                                >
                                    Result
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#eceef1]">
                            <tr
                                v-for="(trace, index) in latestRun.traces"
                                :key="trace.id"
                                class="align-top"
                            >
                                <td class="w-[23%] px-6 py-5">
                                    <div class="mb-2 flex items-center gap-2">
                                        <span
                                            class="flex h-5 w-5 items-center justify-center rounded-md bg-[#f2f3f6] text-[10px] font-semibold text-[#727a86]"
                                            >{{
                                                String(index + 1).padStart(
                                                    2,
                                                    '0',
                                                )
                                            }}</span
                                        >
                                        <span
                                            class="text-[13px] font-semibold text-[#343a44]"
                                            >{{ trace.eval_case.label }}</span
                                        >
                                    </div>
                                    <span
                                        class="ml-7 inline-flex rounded border border-[#e5e7eb] px-1.5 py-0.5 text-[10px] font-medium text-[#777f8b]"
                                        >Contains: “{{
                                            trace.eval_case.expect_contains
                                        }}”</span
                                    >
                                </td>
                                <td
                                    class="w-[27%] px-5 py-5 text-xs leading-5 text-[#5e6672]"
                                >
                                    {{ trace.eval_case.input }}
                                </td>
                                <td
                                    class="w-[34%] px-5 py-5 text-xs leading-5 text-[#434b56]"
                                >
                                    {{ trace.output }}
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-semibold"
                                        :class="
                                            trace.passed
                                                ? 'bg-[#eaf7f1] text-[#25845d]'
                                                : 'bg-[#fff0ef] text-[#bd4b45]'
                                        "
                                    >
                                        <svg
                                            v-if="trace.passed"
                                            class="h-3 w-3"
                                            viewBox="0 0 16 16"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="m3 8.5 3 3 7-7"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-3 w-3"
                                            viewBox="0 0 16 16"
                                            fill="none"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="m4.5 4.5 7 7m0-7-7 7"
                                                stroke="currentColor"
                                                stroke-width="1.7"
                                                stroke-linecap="round"
                                            />
                                        </svg>
                                        {{ trace.passed ? 'Passed' : 'Failed' }}
                                    </span>
                                    <div
                                        class="mt-2 text-[10px] text-[#969da7] tabular-nums"
                                    >
                                        {{ trace.latency_ms }} ms
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-else-if="selectedSuite"
                    class="flex flex-col items-center px-6 py-16 text-center"
                >
                    <span
                        class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f2f1ff] text-[#5b54cb]"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 20 20"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M5.25 3.5h9.5A1.75 1.75 0 0 1 16.5 5.25v9.5a1.75 1.75 0 0 1-1.75 1.75h-9.5A1.75 1.75 0 0 1 3.5 14.75v-9.5A1.75 1.75 0 0 1 5.25 3.5Z"
                                stroke="currentColor"
                                stroke-width="1.5"
                            />
                            <path
                                d="M6.5 7h7m-7 3h7m-7 3h4"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                            />
                        </svg>
                    </span>
                    <h3 class="text-sm font-semibold">
                        Your traces will appear here
                    </h3>
                    <p class="mt-1.5 max-w-sm text-xs leading-5 text-[#858d98]">
                        Run the {{ selectedSuite.name }} suite to see how each
                        fixture performs against the expected response.
                    </p>
                    <button
                        type="button"
                        class="mt-5 text-xs font-semibold text-[#4d47c3] hover:text-[#2e299f] disabled:opacity-50"
                        :disabled="isRunning"
                        @click="runEvaluation"
                    >
                        {{ isRunning ? 'Running…' : 'Run first evaluation →' }}
                    </button>
                </div>

                <div
                    v-else
                    class="px-6 py-16 text-center text-sm text-[#858d98]"
                >
                    No evaluation suites yet. Run the database seeder to add the
                    demo suite.
                </div>
            </section>

            <footer
                class="mt-6 flex flex-col justify-between gap-2 text-[11px] text-[#969da7] sm:flex-row sm:items-center"
            >
                <span
                    >Fixture-based checks · Case-insensitive substring
                    matching</span
                >
                <span
                    >Prompt Eval Lab <span class="mx-1">·</span> Laravel +
                    Vue</span
                >
            </footer>
        </main>
    </div>
</template>
