/* global define */
define(['underscore', 'backbone', 'oro/translator', 'oro/query-designer/util', 'oro/form-validation', 'oro/delete-confirmation',
    'oro/query-designer/column-selector-view', 'jquery-outer-html'],
function(_, Backbone, __, util, FormValidation, DeleteConfirmation,
         ColumnSelectorView) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  oro/query-designer/abstract-view
     * @class   oro.queryDesigner.AbstractView
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        options: {
            collection: null,
            itemFormSelector: null,
            columnChainTemplateSelector: null,
            fieldsLabel: null,
            relatedLabel: null,
            findEntity: null
        },

        /** @property {Object} */
        selectors: {
            itemContainer:  '.item-container',
            cancelButton:   '.cancel-button',
            saveButton:     '.save-button',
            addButton:      '.add-button',
            columnSelector: '[data-purpose="column-selector"]'
        },

        /** @property {jQuery} */
        form: null,

        /** @property {Array} */
        fieldNames: null,

        /** @property {oro.queryDesigner.ColumnSelectorView} */
        columnSelector: null,

        /** @property {Array} */
        fieldLabelGetters: null,

        /** @property */
        itemTemplate: null,

        initialize: function() {
            this.options.collection = this.options.collection || new this.collectionClass();
            this.fieldNames = _.without(_.keys((this.createNewModel()).attributes), 'id');

            this.initForm();

            // prepare field label getters
            this.addFieldLabelGetter(this.getSelectFieldLabel);
            this.addFieldLabelGetter(this.getColumnFieldLabel);
        },

        initForm: function () {
            this.form = $(this.options.itemFormSelector);
            this.columnSelector = new ColumnSelectorView({
                el: this.form.find(this.selectors.columnSelector),
                columnChainTemplate: _.template($(this.options.columnChainTemplateSelector).html()),
                fieldsLabel: this.options.fieldsLabel,
                relatedLabel: this.options.relatedLabel,
                findEntity: this.options.findEntity
            });

            var onAdd = _.bind(function (e) {
                e.preventDefault();
                this.handleAddModel();
            }, this);
            this.$el.find(this.selectors.addButton).on('click', onAdd);

            var onSave = _.bind(function (e) {
                e.preventDefault();
                var id = $(e.currentTarget).data('id');
                this.handleSaveModel(id);
            }, this);
            this.$el.find(this.selectors.saveButton).on('click', onSave);

            var onCancel = _.bind(function (e) {
                e.preventDefault();
                this.handleCancelButton();
            }, this);
            this.$el.find(this.selectors.cancelButton).on('click', onCancel);
        },

        getCollection: function() {
            return this.options.collection;
        },

        changeEntity: function (entityName, columns) {
            this.getCollection().reset();
            this.columnSelector.changeEntity(entityName, columns);
        },

        handleAddModel: function() {
            this.beforeFormSubmit();
            var model = this.createNewModel();
            if (this.validateFormData()) {
                var data = this.getFormData();
                this.clearFormData();
                model.set(data);
                this.$itemContainer.itemContainer('addModel', model);
            }
        },

        handleSaveModel: function(modelId) {
            this.beforeFormSubmit();
            var model = this.getCollection().get(modelId);
            if (this.validateFormData()) {
                model.set(this.getFormData());
                this.resetForm();
            }
        },

        handleCancelButton: function() {
            this.resetForm();
        },

        toggleFormButtons: function (modelId) {
            if (_.isNull(modelId)) {
                modelId = '';
            }
            var addButton = this.$el.find(this.selectors.addButton);
            var saveButton = this.$el.find(this.selectors.saveButton);
            var cancelButton = this.$el.find(this.selectors.cancelButton);
            saveButton.data('id', modelId);
            if (modelId == '') {
                cancelButton.hide();
                saveButton.hide();
                addButton.show();
            } else {
                addButton.hide();
                cancelButton.show();
                saveButton.show();
            }
        },

        resetForm: function () {
            this.clearFormData();
            this.toggleFormButtons(null);
        },

        beforeFormSubmit: function () {
        },

        validateFormData: function () {
            var isValid = true;
            this.iterateFormData(_.bind(function (name, el) {
                FormValidation.removeFieldErrors(el);
                var msg = this.validateFormField(name, el);
                if (!_.isNull(msg)) {
                    FormValidation.addFieldErrors(el, __('This value should not be blank.'));
                    isValid = false;
                }
            }, this));

            return isValid;
        },

        validateFormField: function (name, el) {
            if (el.is('[required]')) {
                var value = el.val();
                if ('' === value) {
                    return __('This value should not be blank.');
                }
            }
            return null;
        },

        getFormData: function () {
            var data = {};
            this.iterateFormData(_.bind(function (name, field) {
                data[name] = this.getFormFieldValue(name, field);
            }, this));

            return data;
        },

        getFormFieldValue: function (name, field) {
            return field.val();
        },

        clearFormData: function () {
            this.iterateFormData(function (name, field) {
                field.val('').trigger('change');
            });
        },

        setFormData: function (data) {
            this.iterateFormData(_.bind(function (name, field) {
                this.setFormFieldValue(name, field, data[name]);
                field.trigger('change');
            }, this));
        },

        setFormFieldValue: function (name, field, value) {
            field.val(value);
        },

        iterateFormData: function (callback) {
            _.each(this.fieldNames, _.bind(function (name) {
                var field = this.findFormField(name);
                if (field.length === 1) {
                    callback(name, field);
                }
            }, this));
        },

        findFormField: function (name) {
            return this.form.find('[name$="\\[' + name + '\\]"]');
        },

        createNewModel: function () {
            var modelClass = this.options.collection.model;
            return new modelClass();
        },

        addFieldLabelGetter: function (callback) {
            if (_.isNull(this.fieldLabelGetters)) {
                this.fieldLabelGetters = [];
            }
            this.fieldLabelGetters.unshift(callback);
        },

        getFieldLabel: function (name, value) {
            var result = null;
            var field = this.findFormField(name);
            if (field.length == 1) {
                for (var i = 0; i < this.fieldLabelGetters.length; i++) {
                    var callback = this.fieldLabelGetters[i];
                    result = callback.call(this, field, name, value);
                    if (result !== null) {
                        break;
                    }
                }
            }
            return (result !== null ? result : value);
        },

        getSelectFieldLabel: function (field, name, value) {
            if (field.get(0).tagName.toLowerCase() == 'select') {
                var opt = util.findSelectOption(field, value);
                if (opt.length === 1) {
                    return opt.text();
                }
            }
            return null;
        },

        getColumnFieldLabel: function (field, name, value) {
            if (field.attr('name') == this.columnSelector.$el.attr('name')) {
                return this.columnSelector.getLabel(value);
            }
            return null;
        },

        onModelEdit: function (e, data) {
            this.setFormData(data.modelAttributes);
            this.toggleFormButtons(data.modelId);
        },

        onModelDelete: function (e, data) {
            if (this.$el.find(this.selectors.saveButton).data('id') == data.modelId) {
                this.resetForm();
            }
        },

        onCollectionReset: function () {
            this.resetForm();
        }
    });
});
