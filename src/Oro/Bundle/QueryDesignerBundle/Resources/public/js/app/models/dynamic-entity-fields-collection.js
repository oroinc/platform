import {isEqual} from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseCollection from 'oroui/js/app/models/base/collection';
import DynamicEntityModel from './dynamic-entity-model';

const DynamicEntityFieldsCollection = BaseCollection.extend({
    model: DynamicEntityModel,

    constructor: function DynamicEntityFieldsCollection(...args) {
        DynamicEntityFieldsCollection.__super__.constructor.apply(this, args);
    },

    setColumnsSource(collection) {
        collection.each(model => this.onAddColumn(model));

        this.listenTo(collection, 'add', this.onAddColumn.bind(this));
        this.listenTo(collection, 'remove', this.onRemoveColumn.bind(this));
        this.listenTo(collection, 'change', this.onChangeColumn.bind(this));
    },

    isApplicable(model) {
        return model.get('func') !== '' && model.get('func').group_type === 'converters';
    },

    onAddColumn(model) {
        if (!this.isApplicable(model)) {
            return;
        }

        this.add({
            id: model.cid,
            name: model.get('name'),
            label: model.get('label'),
            func: model.get('func')
        }, {
            merge: true
        });
    },

    onChangeColumn(model, ...args) {
        this.onAddColumn(model, args);
    },

    onRemoveColumn(model) {
        if (!this.isApplicable(model)) {
            return;
        }

        this.remove([this.get(model.cid)]);
    },

    getSelectData() {
        return this.models.map(model => model.getSelect2Data());
    },

    /**
     * Generate function notation
     *
     * @param {object} attrs
     * @param {string} key
     */
    resolveFunction(attrs, key) {
        const found = this.find(model => model.getSelect2Id() === attrs[key]);

        if (found) {
            attrs[key] = found.get(key);
            attrs.func = found.get('func');
        }
    },

    /**
     * Generate id from data {name, func}
     *
     * @param {object} data
     * @returns {string}
     */
    generateBindId(data) {
        const found = this.find(
            model =>
                model.get('name') === data.name && this.isApplicable(model) && isEqual(model.get('func'), data.func)
        );

        if (found) {
            return found.getSelect2Id();
        }

        return data.name;
    },

    resolveFunctionNameByData({label, funcName}) {
        return __('oro.querydesigner.label_func', {
            label,
            funcName
        });
    },

    extractName(name = '') {
        for (const model of this.models) {
            name = name.replace(`_${model.get('id')}`, '');
        }

        return name;
    }
});

export default DynamicEntityFieldsCollection;
