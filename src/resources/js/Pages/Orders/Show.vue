<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    order: Object,
});

const groupedItems = computed(() => {
    const groups = {};
    for (const item of props.order.order_items) {
        const category = item.item.category.nombre;
        if (!groups[category]) {
            groups[category] = { category_name: category, items: [] };
        }
        groups[category].items.push(item);
    }
    return Object.values(groups);
});

function formatPrice(value) {
    return Number(value).toLocaleString('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });
}

function formatDate(value) {
    if (!value) return '';
    // Permite valores como "2026-07-16" o "2026-07-16T00:00:00.000000Z"
    const datePart = String(value).split('T')[0];
    const [year, month, day] = datePart.split('-');
    if (!year || !month || !day) return value;
    return `${day}/${month}/${year}`;
}
</script>

<template>
    <Head :title="`Pedido ${order.sede}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Pedido {{ order.sede }}</h2>
                    <p class="mt-1 text-sm text-gray-500">Generado el {{ formatDate(order.fecha) }}</p>
                </div>
                <div class="flex gap-2 no-print">
                    <a :href="route('orders.export-xlsx', order.id)" class="btn-success btn-sm">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Descargar XLSX
                    </a>
                    <a :href="route('orders.export-pdf', order.id)" class="btn-danger btn-sm">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Descargar PDF
                    </a>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <!-- Order info -->
            <div class="card p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Sede</p>
                        <p class="mt-1 text-base font-medium text-gray-900">{{ order.sede }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Remisión</p>
                        <p class="mt-1 text-base font-medium text-gray-900">#{{ order.remision }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fecha</p>
                        <p class="mt-1 text-base font-medium text-gray-900">{{ formatDate(order.fecha) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Realizado por</p>
                        <p class="mt-1 text-base font-medium text-gray-900">{{ order.user?.name ?? 'Sin registrar' }}</p>
                    </div>
                </div>
            </div>

            <!-- Category groups -->
            <div v-for="(group, index) in groupedItems" :key="index" class="card">
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
                            <tr v-for="item in group.items" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-900">{{ item.item.codigo_item }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 max-w-xs truncate">{{ item.item.descripcion }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600">{{ formatPrice(item.precio_unitario) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-600">{{ item.presentacion ?? item.item.presentacion }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600">{{ formatPrice(item.precio_presentacion) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">{{ item.cantidad }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">{{ formatPrice(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-3 bg-gray-50/30 border-t border-gray-100 text-right">
                    <span class="text-sm font-semibold text-gray-700">Subtotal {{ group.category_name }}: {{ formatPrice(group.items.reduce((sum, i) => sum + Number(i.total), 0)) }}</span>
                </div>
            </div>

            <!-- Totals -->
            <div class="card p-5">
                <dl class="space-y-2 max-w-xs ml-auto">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">Subtotal</dt>
                        <dd class="font-medium text-gray-900">{{ formatPrice(order.subtotal) }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">IVA (19%)</dt>
                        <dd class="font-medium text-gray-900">{{ formatPrice(order.iva) }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-semibold border-t border-gray-200 pt-2">
                        <dt class="text-gray-900">Total</dt>
                        <dd class="text-gray-900">{{ formatPrice(order.total) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="no-print">
                <Link :href="route('orders.index')" class="btn-ghost">&larr; Volver a pedidos</Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
