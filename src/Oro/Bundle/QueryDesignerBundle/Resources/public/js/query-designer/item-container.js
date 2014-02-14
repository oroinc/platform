/*global define*/
define(['jquery', 'underscore', 'oro/translator', 'oro/delete-confirmation', 'jquery-outer-html', 'jquery-ui'],
function($, _, __, DeleteConfirmation) {
    'use strict';

    /**
     * Item container widget
     *
     * Emits events
     * edit, sort, add, remove
     *
     * Listens to options.collection events:
     * add, remove, change, reset
     *
     * Uses options.itemTemplateSelector for item rendering
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

            this.element.empty();
            this.options.collection.each(this._onModelAdded, this);
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
                    this._sortCollection();
                }, this)
            }).disableSelection();
        },

        _sortCollection: function () {
            var collectionChanged = false;
            var collection = this.options.collection;

            _.each(this.element.find('tr'), function (el, index) {
                var cid = $(el).data('cid');
                var model = collection.at(index);
                if (cid === model.cid) {
                    return;
                }
                var anotherModel = collection.get(cid);
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

        _onModelAdded: function (model) {
            this.element.append(this._renderModel(model));
        },

        _onModelChanged: function (model) {
            this.element.find('[data-cid="' + model.cid + '"]').outerHTML(this._renderModel(model));
        },

        _onModelDeleted: function (model) {
            this.element.find('[data-cid="' + model.cid + '"]').remove();
        },

        _onResetCollection: function () {
            this.element.empty();
            this.options.collection.each(this._onModelAdded, this);
        },

        _renderModel: function (model) {
            var data = {};
            $.each(model.toJSON(), function (name) {
                data[name] = model.getFieldLabel(name);
            });
            data.cid = model.cid;

            var item = $(this.itemTemplate(data));
            this._bindItemActions(item);

            return item;
        },

        _bindItemActions: function (item) {
            // bind edit button
            var onEdit = _.bind(function (e) {
                e.preventDefault();
                var cid = $(e.currentTarget).closest('[data-cid]').data('cid');
                var model = this.options.collection.get(cid);
                if (model) {
                    model.trigger('edit', model);
                }
            }, this);
            item.find(this.options.selectors.editButton).on('click', onEdit);

            // bind delete button
            var onDelete = _.bind(function (e) {
                e.preventDefault();
                var el = $(e.currentTarget);
                var cid = el.closest('[data-cid]').data('cid');
                var confirm = new DeleteConfirmation({
                    content: el.data('message')
                });
                confirm.on('ok', _.bind(this._handleDeleteModel, this, cid));
                confirm.open();
            }, this);
            item.find(this.options.selectors.deleteButton).on('click', onDelete);
        },

        _handleDeleteModel: function (cid) {
            var model = this.options.collection.get(cid);
            if (model) {
                this.options.collection.remove(model);
            }
        }
    });

    return $;
});
