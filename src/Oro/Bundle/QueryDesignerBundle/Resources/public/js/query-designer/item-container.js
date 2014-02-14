/*global define*/
define(['jquery', 'underscore', 'oro/translator', 'oro/delete-confirmation', 'jquery-outer-html', 'jquery-ui'],
function($, _, __, DeleteConfirmation) {
    'use strict';

    /**
     * Item container widget
     *
     * Emits events
     * edit, change, add, remove
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
                containment: this.element.closest('.grid'),
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
                if (uiId === model.id) {
                    return;
                }
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
            });

            if (collectionChanged) {
                collection.trigger('sort');
            }
        },

        _render: function() {
            this.element.empty();
            this.options.collection.each(_.bind(this._onModelAdded, this));
        },

        _onModelAdded: function (model) {
            this.element.append(this._renderModel(model));
        },

        _onModelChanged: function (model) {
            this.element.find('[data-id="' + model.id + '"]').outerHTML(this._renderModel(model));
        },

        _onModelDeleted: function (model) {
            this.element.find('[data-id="' + model.id + '"]').remove();
        },

        _onResetCollection: function () {
            this.element.empty();
            this.options.collection.each(_.bind(function (model, index) {
                this.initModel(model, index);
                this.element.append(this._renderModel(model));
            }, this));
        },

        _renderModel: function (model) {
            var data = {};
            _.each(model.toJSON(), function (value, name) {
                data[name] = model.getFieldLabel(name, value);
            });

            var item = $(this.itemTemplate(data));
            this._bindItemActions(item);

            return item;
        },

        _bindItemActions: function (item) {
            // bind edit button
            var onEdit = _.bind(function (e) {
                e.preventDefault();
                var id = $(e.currentTarget).closest('[data-id]').data('id');
                var model = this.options.collection.get(id);
                if (model) {
                    model.trigger('edit', model);
                }
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

        _handleDeleteModel: function (id) {
            var model = this.options.collection.get(id);
            if (model) {
                this.options.collection.remove(model);
            }
        }
    });

    return $;
});
