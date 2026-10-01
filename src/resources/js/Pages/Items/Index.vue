<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    items: { type: Object, required: true },
    categories: { type: Array, required: true },
    presentaciones: { type: Array, required: true },
    filters: { type: Object, default: () => ({ search: '', category: '' }) },
});

function isPresentationPending(item) {
    return !props.presentaciones.includes(String(item.presentacion ?? '').trim().toUpperCase());
}

const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');

let timeout = null;
watch([search, categoryFilter], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('items.index'), { search: search.value, category: categoryFilter.value }, { preserveState: true, replace: true });
    }, 300);
});

const confirmDialog = ref(null);

function confirmDelete(item) {
    confirmDialog.value.show({
        title: 'Eliminar producto',
        message: `¿Eliminar "${item.descripcion}" (código ${item.codigo_item})?`,
        confirmText: 'Eliminar',
        method: 'delete',
        url: route('items.destroy', item.id),
    });
}
</script>

<template>
    <Head title="Productos" />
    <ConfirmDialog ref="confirmDialog" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Productos</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ items.total }} productos en el catálogo</p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('items.import.form')" class="btn-ghost btn-sm">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Importar Excel
                    </Link>
                    <Link :href="route('items.create')" class="btn-primary btn-sm">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Producto
                    </Link>
                </div>
            </div>
        </template>

        <div class="card">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input v-model="search" type="text" placeholder="Buscar por código o descripción..." class="input-field pl-9" />
                    </div>
                    <select v-model="categoryFilter" class="select-field sm:w-56">
                        <option value="">Todas las categorías</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                    </select>
                </div>
            </div>

            <div v-if="items.data && items.data.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Código</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Descripción</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Categoría</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Presentación</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-mono font-medium text-gray-900">{{ item.codigo_item }}</td>
                            <td class="px-5 py-4 text-sm text-gray-700 max-w-xs truncate">{{ item.descripcion }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                    {{ item.category?.nombre }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm">
                                <span v-if="isPresentationPending(item)" class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                    Pendiente
                                </span>
                                <span v-else class="font-medium text-gray-700">{{ item.presentacion }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <Link :href="route('items.edit', item.id)" class="btn-ghost btn-sm mr-1">
                                    <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </Link>
                                <button @click="confirmDelete(item)" class="btn-danger btn-sm">
                                    <svg class="h-3.5 w-3.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="px-5 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <p class="mt-4 text-sm text-gray-500">No se encontraron productos.</p>
                <Link :href="route('items.create')" class="mt-3 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    Crear el primer producto →
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="items.links && items.links.length > 3" class="flex items-center justify-between px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                <p class="text-sm text-gray-600">
                    Mostrando {{ items.from }}-{{ items.to }} de {{ items.total }} resultados
                </p>
                <nav class="flex gap-1">
                    <template v-for="(link, index) in items.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-200'"
                        />
                        <span v-else v-html="link.label" class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-sm text-gray-400" />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
