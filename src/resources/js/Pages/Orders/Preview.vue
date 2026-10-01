<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    orderData: Object,
    remision: String,
    sede: String,
    fecha: String,
    operationCenter: Object,
    items: Array,
});

const form = useForm({
    archivo: null,
    remision: props.remision,
    sede: props.sede,
    fecha: props.fecha,
    manual_items: [],
});

const enableManual = ref(false);
const searchText = ref('');
const selectedItemCodigo = ref('');
const manualCantidad = ref(1);
const manualItems = ref([]);

const filteredItems = computed(() => {
    if (!searchText.value) return [];
    const search = searchText.value.toLowerCase();
    const added = new Set(manualItems.value.map(m => m.codigo_item));
    return props.items.filter(item =>
        !added.has(item.codigo_item) && (
            item.codigo_item.toLowerCase().includes(search) ||
            item.descripcion.toLowerCase().includes(search)
        )
    ).slice(0, 20);
});

const selectedItem = computed(() => {
    if (!selectedItemCodigo.value) return null;
    return props.items.find(i => i.codigo_item === selectedItemCodigo.value);
});

const manualSubtotal = computed(() =>
    manualItems.value.reduce((sum, mi) => sum + mi.total, 0)
);

const manualIva = computed(() =>
    manualItems.value.reduce((sum, mi) =>
        sum + (mi.aplica_iva ? mi.total * 0.19 : 0), 0
    )
);

const hasPresentationMismatches = computed(() =>
    (props.orderData.presentation_mismatches ?? []).length > 0
);

const hasPendingPresentations = computed(() =>
    (props.orderData.presentation_pending ?? []).length > 0
);

const allSubtotal = computed(() => props.orderData.subtotal + manualSubtotal.value);
const allIva = computed(() => props.orderData.iva + manualIva.value);
const allTotal = computed(() => allSubtotal.value + allIva.value);

function formatPrice(value) {
    return Number(value).toLocaleString('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });
}

function selectItem(item) {
    selectedItemCodigo.value = item.codigo_item;
    searchText.value = '';
}

function addManualItem() {
    if (!selectedItem.value || manualCantidad.value < 1) return;
    const item = selectedItem.value;
    manualItems.value.push({
        codigo_item: item.codigo_item,
        descripcion: item.descripcion,
        cantidad: manualCantidad.value,
        precio_unitario: Number(item.precio_unidad),
        presentacion: item.presentacion,
        precio_presentacion: Number(item.precio_presentacion),
        total: manualCantidad.value * Number(item.precio_presentacion),
        category_name: item.category.nombre,
        category_id: item.categoria_id,
        aplica_iva: item.category.aplica_iva,
    });
    selectedItemCodigo.value = '';
    manualCantidad.value = 1;
}

function removeManualItem(index) {
    manualItems.value.splice(index, 1);
}

const confirmOrder = () => {
    form.manual_items = manualItems.value.map(mi => ({
        codigo_item: mi.codigo_item,
        cantidad: mi.cantidad,
    }));
    form.post(route('orders.store'));
};
</script>

<template>
    <Head title="Vista Previa del Pedido" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Vista Previa del Pedido</h2>
        </template>

        <div class="space-y-5">
            <!-- Order info -->
            <div class="card p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Remisión</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ remision }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Sede</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ sede }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fecha</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ fecha }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">C.O.</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">{{ operationCenter?.code ?? 'No aplica' }}</p>
                    </div>
                </div>
                <p v-if="operationCenter?.applied" class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    La sede se ajustó automáticamente a {{ operationCenter.sede }} según el C.O. {{ operationCenter.code }} del archivo.
                </p>
                <p v-else-if="operationCenter?.filtered" class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                    El archivo contiene varios C.O.; se usarán {{ operationCenter.filtered_items_count }} de {{ operationCenter.original_items_count }} ítems del C.O. {{ operationCenter.code }} para la sede {{ operationCenter.sede }}.
                </p>
            </div>

            <!-- Not found warning -->
            <div v-if="orderData.not_found && orderData.not_found.length" class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-yellow-800">Códigos no encontrados en el catálogo</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span v-for="code in orderData.not_found" :key="code" class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">
                                {{ code }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products that need presentation setup -->
            <div v-if="hasPendingPresentations" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-amber-900">Hay productos sin Presentación parametrizada</p>
                        <p class="mt-1 text-sm text-amber-800">Se puede continuar con el pedido, pero no se validará la U.M. del Excel para estos productos hasta configurar su Presentación.</p>
                        <div class="mt-3 overflow-x-auto rounded-lg border border-amber-200 bg-white">
                            <table class="min-w-full divide-y divide-amber-100 text-left">
                                <thead class="bg-amber-50 text-xs font-semibold uppercase tracking-wider text-amber-900">
                                    <tr>
                                        <th class="px-3 py-2">Fila</th>
                                        <th class="px-3 py-2">Producto</th>
                                        <th class="px-3 py-2">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100 text-sm text-gray-700">
                                    <tr v-for="(pending, index) in orderData.presentation_pending" :key="`${pending.codigo_item}-${pending.fila}-${index}`">
                                        <td class="whitespace-nowrap px-3 py-2">{{ pending.fila ?? '—' }}</td>
                                        <td class="px-3 py-2"><span class="font-mono">{{ pending.codigo_item }}</span> — {{ pending.descripcion }}</td>
                                        <td class="whitespace-nowrap px-3 py-2">
                                            <Link :href="route('items.edit', pending.item_id)" class="font-semibold text-amber-800 underline decoration-amber-400 underline-offset-2 hover:text-amber-950">Configurar</Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- U.M validation errors -->
            <div v-if="hasPresentationMismatches" class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-red-800">Hay diferencias en U.M. Corrige el Excel o la Presentación del producto antes de confirmar.</p>
                        <div class="mt-3 overflow-x-auto rounded-lg border border-red-200 bg-white">
                            <table class="min-w-full divide-y divide-red-100 text-left">
                                <thead class="bg-red-50 text-xs font-semibold uppercase tracking-wider text-red-800">
                                    <tr>
                                        <th class="px-3 py-2">Fila</th>
                                        <th class="px-3 py-2">Producto</th>
                                        <th class="px-3 py-2">U.M Excel</th>
                                        <th class="px-3 py-2">Presentación configurada</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-red-100 text-sm text-gray-700">
                                    <tr v-for="(mismatch, index) in orderData.presentation_mismatches" :key="`${mismatch.codigo_item}-${mismatch.fila}-${index}`">
                                        <td class="whitespace-nowrap px-3 py-2">{{ mismatch.fila ?? '—' }}</td>
                                        <td class="px-3 py-2"><span class="font-mono">{{ mismatch.codigo_item }}</span> — {{ mismatch.descripcion }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 font-semibold text-red-700">{{ mismatch.unidad_medida_excel ?? '(vacía)' }}</td>
                                        <td class="whitespace-nowrap px-3 py-2 font-semibold">{{ mismatch.presentacion_producto }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual items toggle -->
            <div class="card p-5">
                <label class="inline-flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" v-model="enableManual" class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    <span class="text-sm font-medium text-gray-700">Deseo agregar productos adicionales manualmente</span>
                </label>
            </div>

            <!-- Manual items section -->
            <div v-if="enableManual" class="card p-5 space-y-4 overflow-visible">
                <h3 class="text-base font-semibold text-gray-800">Productos manuales</h3>

                <div class="relative">
                    <input
                        v-model="searchText"
                        type="text"
                        class="input-field w-full"
                        placeholder="Buscar producto por código o descripción..."
                    />
                    <div
                        v-if="searchText && filteredItems.length"
                        class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg max-h-60 overflow-y-auto"
                    >
                        <button
                            v-for="item in filteredItems"
                            :key="item.codigo_item"
                            type="button"
                            @click="selectItem(item)"
                            class="w-full px-4 py-2 text-left text-sm hover:bg-indigo-50 transition-colors border-b border-gray-100 last:border-0"
                        >
                            <span class="font-medium">{{ item.codigo_item }}</span>
                            <span class="text-gray-500 ml-2">{{ item.descripcion }}</span>
                        </button>
                    </div>
                    <p v-if="searchText && !filteredItems.length && !selectedItemCodigo" class="mt-1 text-xs text-gray-400">Sin resultados</p>
                </div>

                <div v-if="selectedItem" class="flex items-center gap-3 p-3 rounded-lg bg-indigo-50">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ selectedItem.codigo_item }} - {{ selectedItem.descripcion }}</p>
                        <p class="text-xs text-gray-500">{{ selectedItem.category.nombre }} — {{ formatPrice(selectedItem.precio_presentacion) }} c/u</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <input
                            v-model.number="manualCantidad"
                            type="number"
                            min="1"
                            class="input-field w-20 text-center"
                        />
                        <button type="button" @click="addManualItem" class="btn-primary btn-sm whitespace-nowrap">Agregar</button>
                    </div>
                </div>

                <div v-if="manualItems.length" class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Agregados ({{ manualItems.length }})</p>
                    <div v-for="(mi, i) in manualItems" :key="i" class="flex items-center justify-between p-3 rounded-lg border border-gray-200">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ mi.codigo_item }} - {{ mi.descripcion }}</p>
                            <p class="text-xs text-gray-500">{{ mi.category_name }} — {{ mi.cantidad }} und × {{ formatPrice(mi.precio_presentacion) }} = {{ formatPrice(mi.total) }}</p>
                        </div>
                        <button type="button" @click="removeManualItem(i)" class="text-red-500 hover:text-red-700 text-sm font-medium shrink-0 ml-3">Eliminar</button>
                    </div>
                </div>
            </div>

            <!-- Category groups -->
            <div v-for="(group, index) in orderData.grouped" :key="index" class="card">
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80">
                    <h3 class="text-base font-semibold text-gray-800">{{ group.category_name }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Producto</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Precio Und</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Presentación</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Precio Pres</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Cantidad</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(item, i) in group.items" :key="i" class="hover:bg-gray-50/50 transition-colors">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-900">{{ item.codigo_item }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">{{ item.descripcion }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600">{{ formatPrice(item.precio_unitario) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-600">{{ item.presentacion }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600">{{ formatPrice(item.precio_presentacion) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">{{ item.cantidad }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">{{ formatPrice(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 bg-gray-50/30 border-t border-gray-100 text-right">
                    <span class="text-sm font-semibold text-gray-700">Subtotal {{ group.category_name }}: {{ formatPrice(group.subtotal) }}</span>
                </div>
            </div>

            <!-- Totals -->
            <div class="card p-5">
                <dl class="space-y-2 max-w-xs ml-auto">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-900">{{ formatPrice(allSubtotal) }}</dd>
                    </div>
                    <div v-if="manualItems.length" class="flex justify-between text-xs text-gray-400">
                        <dt class="text-gray-400">(archivo: {{ formatPrice(orderData.subtotal) }})</dt>
                        <dd class="text-gray-400">+ manual: {{ formatPrice(manualSubtotal) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">IVA (19%)</dt>
                        <dd class="font-medium text-gray-900">{{ formatPrice(allIva) }}</dd>
                    </div>
                    <div v-if="manualItems.length" class="flex justify-between text-xs text-gray-400">
                        <dt class="text-gray-400">(archivo: {{ formatPrice(orderData.iva) }})</dt>
                        <dd class="text-gray-400">+ manual: {{ formatPrice(manualIva) }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-semibold border-t border-gray-200 pt-2">
                        <dt class="text-gray-900">Total</dt>
                        <dd class="text-gray-900">{{ formatPrice(allTotal) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Confirm actions -->
            <form @submit.prevent="confirmOrder" class="card p-5 space-y-4">
                <div v-if="form.errors.remision || form.errors.sede" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                    <p v-if="form.errors.remision">{{ form.errors.remision }}</p>
                    <p v-if="form.errors.sede">{{ form.errors.sede }}</p>
                </div>
                <div>
                    <label class="label-field">Selecciona nuevamente el archivo para confirmar</label>
                    <input
                        type="file"
                        accept=".xlsx,.xls"
                        @input="form.archivo = $event.target.files[0]"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                    />
                    <p v-if="form.errors.archivo" class="mt-1 text-sm text-red-600">{{ form.errors.archivo }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="form.processing || !form.archivo || hasPresentationMismatches" class="btn-success flex items-center gap-2">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        Confirmar Pedido
                    </button>
                    <Link :href="route('orders.create')" class="btn-ghost">Volver</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
