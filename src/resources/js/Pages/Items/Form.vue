<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    item: { type: Object, default: null },
    categories: { type: Array, required: true },
    presentaciones: { type: Array, required: true },
});

const currentPresentation = String(props.item?.presentacion ?? '').trim().toUpperCase();
const presentationNeedsSetup = Boolean(props.item)
    && !props.presentaciones.includes(currentPresentation);
const legacyPresentation = presentationNeedsSetup && currentPresentation
    ? props.item.presentacion
    : null;

const form = useForm({
    codigo_item: props.item?.codigo_item ?? '',
    descripcion: props.item?.descripcion ?? '',
    precio_unidad: props.item?.precio_unidad ?? '',
    presentacion: presentationNeedsSetup ? '' : currentPresentation,
    precio_presentacion: props.item?.precio_presentacion ?? '',
    categoria_id: props.item?.categoria_id ?? '',
});

function submit() {
    if (props.item) {
        form.put(route('items.update', props.item.id));
    } else {
        form.post(route('items.store'));
    }
}
</script>

<template>
    <Head :title="item ? 'Editar Producto' : 'Nuevo Producto'" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ item ? 'Editar Producto' : 'Nuevo Producto' }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ item ? 'Modifica los datos del producto' : 'Registra un nuevo producto en el catálogo' }}</p>
            </div>
        </template>

        <div class="max-w-2xl mx-auto">
            <form @submit.prevent="submit" class="card p-6 space-y-5">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label-field" for="codigo_item">Código</label>
                        <input id="codigo_item" v-model="form.codigo_item" type="text" class="input-field" placeholder="Ej: 1001" required />
                        <InputError :message="form.errors.codigo_item" />
                    </div>
                    <div>
                        <label class="label-field" for="categoria_id">Categoría</label>
                        <select id="categoria_id" v-model="form.categoria_id" class="select-field" required>
                            <option value="">Seleccione una categoría</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                        </select>
                        <InputError :message="form.errors.categoria_id" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label-field" for="descripcion">Descripción</label>
                        <input id="descripcion" v-model="form.descripcion" type="text" class="input-field" placeholder="Nombre completo del producto" required />
                        <InputError :message="form.errors.descripcion" />
                    </div>
                    <div>
                        <label class="label-field" for="precio_unidad">Precio por Unidad</label>
                        <input id="precio_unidad" v-model="form.precio_unidad" type="number" step="0.01" min="0" class="input-field" placeholder="0.00" />
                        <InputError :message="form.errors.precio_unidad" />
                    </div>
                    <div>
                        <label class="label-field" for="precio_presentacion">Precio por Presentación</label>
                        <input id="precio_presentacion" v-model="form.precio_presentacion" type="number" step="0.01" min="0" class="input-field" placeholder="0.00" />
                        <InputError :message="form.errors.precio_presentacion" />
                    </div>
                    <div>
                        <label class="label-field" for="presentacion">Presentación</label>
                        <select id="presentacion" v-model="form.presentacion" class="select-field" required>
                            <option value="" disabled>Seleccione una presentación</option>
                            <option v-for="presentacion in presentaciones" :key="presentacion" :value="presentacion">
                                {{ presentacion }}
                            </option>
                        </select>
                        <InputError :message="form.errors.presentacion" />
                        <p v-if="presentationNeedsSetup" class="mt-1 text-xs text-amber-700">
                            Presentación pendiente de parametrizar. Selecciona una opción nueva{{ legacyPresentation ? `; el valor anterior era ${legacyPresentation}.` : '.' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                    <button type="submit" :disabled="form.processing" class="btn-primary flex items-center gap-2">
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        {{ item ? 'Actualizar' : 'Guardar' }}
                    </button>
                    <Link :href="route('items.index')" class="btn-ghost">Cancelar</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
