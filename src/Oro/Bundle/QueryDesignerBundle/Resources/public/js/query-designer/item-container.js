/*global define*/
define(['jquery', 'underscore', 'oro/translator', 'oro/delete-confirmation', 'jquery-outer-html'],
function($, _, __, DeleteConfirmation) {
    'use strict';

    /**
     * Item container widget
     *
     * Emits events:
     * collection:change, collection:reset, model:edit, model:delete
     *
     * Listens to options.collection events:
     * add, remove, change, reset
     *
     * Uses options.itemTemplateSelector and options.getFieldLabel for item rendering
     */
    $.widget('oroquerydesigner.itemContainer', {
        options: {
            itemTemplateSelector: null,
            selectors: {
                editButton: '.edit-button',
                deleteButton: '.delete-button'
            },
            getFieldLabel: function (name, value) {
                return (typeof value === 'object') ? JSON.stringify(value) : value;
            }
        },

        _create: function () {
            this.itemTemplate = _.template($(this.options.itemTemplateSelector).html());

            var collection = this.options.collection;
            collection.on('add', this._onModelAdded, this);
            collection.on('remove', this._onModelDeleted, this);
            collection.on('change', this._onModelChanged, this);
            collection.on('reset', this._onResetCollection, this);

            this._initSorting();
            this._render();
        },

        _initSorting: function () {
            this.element.sortable({
                cursor: 'move',
                delay : 100,
                opacity: 0.7,
                revert: 10,
                axis: 'y',
                containment: '.query-designer-grid-container',
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
                this._trigger('collection:change');
            }
        },

        _render: function() {
            this.element.empty();
            this.options.collection.each(_.bind(this._onModelAdded, this));
        },

        _onModelAdded: function (model) {
            this.element.append(this._renderModel(model));
            this._trigger('collection:change');
        },

        _onModelChanged: function (model) {
            this.element.find('[data-id="' + model.id + '"]').outerHTML(this._renderModel(model));
            this._trigger('collection:change');
        },

        _onModelDeleted: function (model) {
            this.element.find('[data-id="' + model.id + '"]').remove();
            this._trigger('collection:change');
        },

        _onResetCollection: function () {
            this.element.empty();
            this._trigger('collection:reset');
            this.options.collection.each(_.bind(function (model, index) {
                this.initModel(model, index);
                this.element.append(this._renderModel(model));
            }, this));
            this._trigger('collection:change');
        },

        _renderModel: function (model) {
            var data = model.toJSON();
            _.each(data, function (value, name) {
                data[name] = this.options.getFieldLabel(name, value);
            }, this);

            var item = $(this.itemTemplate(data));
            this._bindItemActions(item);

            return item;
        },

        _bindItemActions: function (item) {
            // bind edit button
            var onEdit = _.bind(function (e) {
                e.preventDefault();
                var el = $(e.currentTarget);
                var id = el.closest('[data-id]').data('id');
                var model = this.options.collection.get(id);
                this._trigger('model:edit', {
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
            this._trigger('model:delete', {
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
        }
    });

    return $;
});
