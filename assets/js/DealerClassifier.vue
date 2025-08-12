<template>
    <div class="DealerClassifier">
        <div class="unityBlock_body">
            <table class="table table__striped table__border">
                <thead>
                <tr>
                    <th style="width:20%">{{ t('Платформа') }}</th>
                    <th style="width:25%">{{ t('Client ID') }}</th>
                    <th style="width:25%">{{ t('Client Secret') }}</th>
                    <th style="width:15%">{{ t('Статус') }}</th>
                    <th style="width:15%">{{ t('Webhook токен') }}</th>
                    <th style="width:50px"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(classifier, index) in items" :key="classifier.id || index">
                    <input
                        v-if="init.formName"
                        type="hidden"
                        :name="`${init.formName}[classifiers][${index}][id]`"
                        :value="classifier.id || ''"
                    />

                    <td>
                        <span class="badge badge--info">{{ platformTypes[classifier.type] || 'Неизвестная' }}</span>
                    </td>
                    <td>
                        <code class="small">{{ classifier.client_id || t('Не указан') }}</code>
                    </td>
                    <td>
                        <code class="small">{{ classifier.client_secret || t('Не указан') }}</code>
                    </td>
                    <td>
                        <span
                            :class="['badge', classifier.is_active ? 'badge--success' : 'badge--secondary']"
                        >
                            {{ classifier.is_active ? t('Активен') : t('Неактивен') }}
                        </span>
                    </td>
                    <td>
                        <span v-if="classifier.webhook_token" class="badge badge--success">
                            {{ t('Установлен') }}
                        </span>
                        <span v-else class="badge badge--warning">
                            {{ t('Нет') }}
                        </span>
                        <div v-if="classifier.webhook_token" class="small text-muted mt-1">
                            <code class="small">{{ maskToken(classifier.webhook_token) }}</code>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn-icon" @click.prevent="edit(classifier)" v-if="canEdit">
                            <span class="svg--icon"><Icon name="bicolors-edit"/></span>
                        </button>
                        <button type="button" class="btn-icon" @click.prevent="remove(classifier)" v-if="canEdit">
                            <span class="svg--icon"><Icon name="bicolors-delete"/></span>
                        </button>
                    </td>
                </tr>
                </tbody>
                <tfoot v-if="canEdit">
                <tr>
                    <th>
                        <SelectBootstrap
                            class="form-control"
                            data-live-search="1"
                            :prompt="prompt"
                            :options="platformTypes"
                            :value="current.type"
                            @change="current.type = $event;"
                        ></SelectBootstrap>
                    </th>
                    <th>
                        <input
                            class="form-control"
                            type="text"
                            :placeholder="t('Client ID')"
                            :value="current.client_id"
                            @input="current.client_id = $event.target.value"
                        >
                    </th>
                    <th>
                        <input
                            class="form-control"
                            type="text"
                            :placeholder="t('Client Secret')"
                            :value="current.client_secret"
                            @input="current.client_secret = $event.target.value"
                        >
                    </th>
                    <th>
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                :id="'isActive_' + Math.random().toString(36).substr(2, 9)"
                                v-model="current.is_active"
                            />
                            <label class="form-check-label small">
                                {{ t('Активен') }}
                            </label>
                        </div>
                    </th>
                    <th>
                        <span class="text-muted small">{{ t('Автоматически') }}</span>
                    </th>
                    <th>
                        <button type="button" class="btn-icon" @click.prevent="add" v-if="canAdd && canEdit" data-toggle="tooltip" :title="t('Добавить')">
                            <span class="svg--icon"><Icon name="bicolors-plus"/></span>
                        </button>
                    </th>
                </tr>
                </tfoot>
            </table>

            <div v-if="items.length === 0 && canEdit" class="text-center py-4">
                <Icon name="bicolors-plug" class="svg--icon text-muted mb-3" style="font-size: 3rem;"/>
                <p class="text-muted mb-0">{{ t('Нет настроенных интеграций') }}</p>
                <small class="text-muted">{{ t('Добавьте интеграцию с рекламной платформой для получения лидов') }}</small>
            </div>

            <input v-if="!init.formName" type="hidden" name="classifiers" :value="JSON.stringify(result)">
            <input v-if="init.formName" type="hidden" :name="`${init.formName}[classifiers]`" :value="JSON.stringify(result)">
        </div>
    </div>
</template>

<script>
    import i18n from "./mixins/i18n";
    import Icon from "./Icon.vue";
    import SelectBootstrap from "./SelectBootstrap.vue";

    const newClassifier = () => ({
        id: null,
        type: null,
        client_id: '',
        client_secret: '',
        is_active: true,
        webhook_token: null,
    })

    export default {
        components: {
            Icon,
            SelectBootstrap,
        },
        mixins: [i18n],
        props: {
            init: {
                type: Object,
                default: {},
            },
        },
        data: () => ({
            items: [],
            canEdit: false,
            current: newClassifier(),
        }),
        computed: {
            canAdd() {
                if (
                    !this.current.type ||
                    !this.current.client_id ||
                    !this.current.client_secret
                ) {
                    return false;
                }

                return !this.items
                    .filter(i => i !== this.current)
                    .some(i => i.type === this.current.type);
            },
            editMode() {
                return this.items.some(i => i === this.current);
            },
            platformTypes() {
                return this.init.platformTypes || {};
            },
            result() {
                const list = [];

                this.items.forEach((row) => {
                    if (!list.some((r) => r.type === row.type)) {
                        list.push({
                            id: row.id || null,
                            type: row.type,
                            client_id: row.client_id,
                            client_secret: row.client_secret,
                            is_active: row.is_active,
                        });
                    }
                });

                return list;
            },
            prompt() {
                return this.init.prompt || this.t('Выбрать платформу');
            }
        },
        methods: {
            maskToken(token) {
                if (!token) return this.t('Нет');
                return token.length > 16
                    ? token.substring(0, 8) + '...' + token.substring(token.length - 8)
                    : token.substring(0, 8) + '...';
            },
            add() {
                if (!this.editMode) {
                    this.items.push(this.current);
                }

                this.current = newClassifier();
            },
            remove(classifier) {
                if (confirm(this.t('Удалить интеграцию с ') + (this.platformTypes[classifier.type] || classifier.type) + '?')) {
                    this.items = this.items.filter(i => i !== classifier);
                }
            },
            edit(classifier) {
                this.current = classifier;
            },
        },
        created() {
            this.items = (this.init.classifiers || []).map(c => ({
                id: c.id,
                type: c.type,
                client_id: c.client_id,
                client_secret: c.client_secret,
                is_active: c.is_active !== false,
                webhook_token: c.webhook_token || null,
            }));

            this.canEdit = this.init.canEdit !== false;
        }
    }
</script>

<style scoped>

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.badge--info {
    background-color: #17a2b8;
    color: white;
}

.badge--success {
    background-color: #28a745;
    color: white;
}

.badge--secondary {
    background-color: #6c757d;
    color: white;
}

.badge--warning {
    background-color: #ffc107;
    color: #212529;
}

code.small {
    font-size: 0.8rem;
    background-color: #f8f9fa;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
}

.form-check {
    margin-bottom: 0;
}

.form-check-input {
    margin-top: 0.125rem;
}

.form-check-label {
    padding-left: 0.25rem;
}

.btn-icon {
    background: none;
    border: none;
    padding: 0.25rem;
    margin: 0 0.125rem;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.btn-icon:hover {
    opacity: 1;
}

.text-center {
    text-align: center;
}

.py-4 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
}

.mb-3 {
    margin-bottom: 1rem;
}

.mb-0 {
    margin-bottom: 0;
}

.text-muted {
    color: #6c757d !important;
}

.mt-1 {
    margin-top: 0.25rem;
}

.small {
    font-size: 0.875rem;
}
</style>