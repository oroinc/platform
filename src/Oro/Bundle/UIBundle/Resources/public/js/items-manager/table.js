/*global define*/
/*jslint nomen: true*/
define(['jquery', 'underscore', 'oro/translator', 'oro/delete-confirmation', 'jquery-outer-html', 'jquery-ui'
    ], function ($, _, __, DeleteConfirmation) {
    'use strict';

    /**
     * Item container widget
     *
     * Listens to options.collection events:
     * add, remove, change, reset
     *
     * Emits events
     * action:*, sorts
     *
     * Uses options.itemTemplate & options.itemRender for item rendering
     */
    $.widget('oroui.itemsManagerTable', {
        options: {
            itemTemplate: null
        },

        _create: function () {
            var options = this.options;

            options.deleteHandler = options.deleteHandler || _.bind(this._deleteHandler, this);

            switch (typeof options.itemTemplate) {
                case 'function':
                    this.itemTemplate = options.itemTemplate;
                    break;
                case 'string':
                    this.itemTemplate = _.template(options.itemTemplate);
                    break;
                default:
                    throw new Error('itemTemplate option required');
            }

            if (typeof options.itemRender === 'function') {
                this._itemRender = options.itemRender;
            }

            if (!options.collection) {
                throw new Error('collection option required');
            }

            options.collection.on('add', this._onModelAdded, this);
            options.collection.on('remove', this._onModelDeleted, this);
            options.collection.on('change', this._onModelChanged, this);
            options.collection.on('reset', this._onResetCollection, this);

            this._initSorting();
            this._onResetCollection();

            this.element.on('click', '[data-collection-action]',  _.bind(this._onAction, this));
        },

        reset: function () {
            this.options.collection.reset();
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
                stop: _.bind(function (e, ui) {
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
            var data = _.extend({ cid: model.cid }, model.toJSON());
            return this._itemRender(this.itemTemplate, data);
        },

        _itemRender: function (tmpl, data) {
            return tmpl(data);
        },

        _onAction: function (ev) {
            ev.preventDefault();

            var $el = $(ev.currentTarget);
            var cid = $el.closest('[data-cid]').data('cid');
            var model = this.options.collection.get(cid);
            if (!model) {
                return;
            }

            var data = $el.data();
            var action = data.collectionAction;
            var handler = this.options[action + 'Handler'];
            if (typeof handler === 'function') {
                handler(model, data);
            } else {
                model.trigger('action:' + action, model, data);
            }
        },

        _deleteHandler: function (model, data) {
            var collection = this.options.collection;
            var confirm = new DeleteConfirmation({
                content: data.message
            });
            confirm.on('ok', function () {
                collection.remove(model);
            });
            confirm.open();
        }
    });

    return $;
});
