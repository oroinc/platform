/*global define*/
define(['jquery', 'underscore', 'oro/translator', 'oro/delete-confirmation', 'jquery-outer-html'],
function($, _, __, DeleteConfirmation) {
    'use strict';

    /**
     * Item container widget
     */
    $.widget('oroquerydesigner.itemContainerWidget', {
        options: {
            itemTemplateSelector: null,
            selectors: {
                editButton:     '.edit-button',
                deleteButton:   '.delete-button',
            }
        },

        _create: function () {
            this.itemTemplate = _.template($(this.options.itemTemplateSelector).html());

            this._initColumnSorting();

            this.options.collection.on('add', _.bind(this.onModelAdded, this));
            this.options.collection.on('remove', _.bind(this.onModelDeleted, this));
            this.options.collection.on('change', _.bind(this.onModelChanged, this));
            this.options.collection.on('reset', _.bind(this.onResetCollection, this));
        },

        _initColumnSorting: function () {
            this.element.sortable({
                cursor: 'move',
                delay : 100,
                opacity: 0.7,
                revert: 10,
                axis: 'y',
                containment: ".query-designer-grid-container",
                items: 'tr',
                helper: function (e, ui) {
                    ui.children().each(function () {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                stop: _.bind(function(e, ui) {
                    this._syncCollectionWithUi();
                }, this)
            }).disableSelection();
        },

        _syncCollectionWithUi: function () {
            var collectionChanged = false;
            var collection = this.options.collection;
            _.each(this.element.find('tr'), function (el, index) {
                var uiId = $(el).data('id');
                var model = collection.at(index);
                if (uiId !== model.id) {
                    var anotherModel = collection.get(uiId);
                    var anotherIndex = collection.indexOf(anotherModel);
                    collection.remove(model, {silent: true});
                    collection.remove(anotherModel, {silent: true});
                    if (index < anotherIndex) {
                        collection.add(anotherModel, {silent: true, at: index});
                        collection.add(model, {silent: true, at: anotherIndex});
                    } else {
                        collection.add(model, {silent: true, at: anotherIndex});
                        collection.add(anotherModel, {silent: true, at: index});
                    }
                    collectionChanged = true;
                }
            }, this);
            if (collectionChanged) {
                this.element.trigger('collection:change');
            }
        },

        render: function() {
            this.element.empty();
            this.options.collection.each(_.bind(function (model) {
                this.onModelAdded(model);
            }, this));
        },

        onModelAdded: function (model) {
            this.element.append(this._renderModel(model));
            this.element.trigger('collection:change');
        },

        onModelChanged: function (model) {
            this.element.find('[data-id="' + model.id + '"]').outerHTML(this._renderModel(model));
            this.element.trigger('collection:change');
        },

        onModelDeleted: function (model) {
            this.element.find('[data-id="' + model.id + '"]').remove();
            this.element.trigger('collection:change');
        },

        onResetCollection: function () {
            this.element.empty();
            //this.resetForm();
            this.options.collection.each(_.bind(function (model, index) {
                this.initModel(model, index);
                this.element.append(this._renderModel(model));
            }, this));
            this.element.trigger('collection:change');
        },

        _renderModel: function (model) {
            var item = $(this.itemTemplate(this._prepareItemTemplateData(model)));
            this._bindItemActions(item);
            return item;
        },

        _prepareItemTemplateData: function (model) {
            var data = model.toJSON();
            _.each(data, _.bind(function (value, name) {
                data[name] = this.options.getFieldLabel(name, value);
            }, this));
            return data;
        },

        _bindItemActions: function (item) {
            // bind edit button
            var onEdit = _.bind(function (e) {
                e.preventDefault();
                var el = $(e.currentTarget);
                var id = el.closest('[data-id]').data('id');
                var model = this.options.collection.get(id);
                this.element.trigger('model:edit', {
                    modelId: id,
                    modelAttributes: model.attributes
                });
            }, this);
            item.find(this.options.selectors.editButton).on('click', onEdit);

            // bind delete button
            var onDelete = _.bind(function (e) {
                e.preventDefault();
                var el = $(e.currentTarget);
                var id = el.closest('[data-id]').data('id');
                var confirm = new DeleteConfirmation({
                    content: el.data('message')
                });
                confirm.on('ok', _.bind(this._handleDeleteModel, this, id));
                confirm.open();
            }, this);
            item.find(this.options.selectors.deleteButton).on('click', onDelete);
        },

        _handleDeleteModel: function (modelId) {
            var model = this.options.collection.get(modelId);
            this.element.trigger('model:delete', {
                modelId: modelId
            });
            this.deleteModel(model);
        },

        initModel: function (model, index) {
            model.set('id', _.uniqueId('designer'));
        },

        addModel: function (model) {
            this.initModel(model, this.options.collection.size());
            this.options.collection.add(model);
        },

        deleteModel: function (model) {
            this.options.collection.remove(model);
        },

    });

    return $;
});
