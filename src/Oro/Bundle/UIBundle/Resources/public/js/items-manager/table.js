define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    require('jquery-ui/widget');
    require('jquery-ui/widgets/sortable');

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
            itemTemplate: null,
            sorting: true
        },

        _create: function() {
            const options = this.options;

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
            options.collection.on('sort', this._renderCollection, this);

            this._initSorting();
            this._onResetCollection();

            this._on({
                'click [data-collection-action]': '_onAction'
            });
        },

        reset: function() {
            this.options.collection.reset();
        },

        _initSorting: function() {
            if (!this.options.sorting) {
                return;
            }

            this.element.sortable({
                cursor: 'move',
                delay: 25,
                opacity: 0.7,
                revert: 10,
                axis: 'y',
                containment: this.element.closest('.grid tbody'),
                items: 'tr',
                tolerance: 'pointer',
                handle: '.handle',
                helper: function(e, ui) {
                    ui.children().each(function() {
                        $(this).width($(this).width());
                    });
                    return ui;
                },
                stop: (e, ui) => {
                    this._sortCollection();
                }
            }).disableSelection();
        },

        _sortCollection: function() {
            const collection = this.options.collection;
            const positions = {};
            this.element.find('tr').each(function(index) {
                positions[$(this).data('cid')] = index;
            });
            collection.models.sort(function(left, right) {
                const diff = positions[left.cid] - positions[right.cid];
                return diff > 0 ? 1 : (diff < 0 ? -1 : 0);
            });
            collection.trigger('sort');
        },

        _getItemElementByModel: function(model) {
            return this.element.find('[data-cid="' + model.cid + '"]');
        },

        _onModelAdded: function(model, collection, options = {}) {
            const newRow = this._renderModel(model);

            if (_.isUndefined(options.at)) {
                this.element.append(newRow);
            } else if (options.at === 0) {
                this.element.prepend(newRow);
            } else {
                this.element.children().eq(options.at).insertBefore(newRow);
            }

            if (this.options.sorting) {
                this.element.sortable('refresh');
            }

            this._getItemElementByModel(model).trigger('content:changed');

            mediator.trigger(
                'items-manager:table:add:' + this._getIdentifier(),
                this.options.collection,
                model,
                this.element
            );
        },

        _onModelChanged: function(model) {
            const $oldItemElement = this._getItemElementByModel(model);

            $oldItemElement.trigger('content:remove');
            $oldItemElement.replaceWith(this._renderModel(model));
            this._getItemElementByModel(model).trigger('content:changed');

            mediator.trigger(
                'items-manager:table:change:' + this._getIdentifier(),
                this.options.collection,
                model,
                this.element
            );
        },

        _onModelDeleted: function(model) {
            this._getItemElementByModel(model).trigger('content:remove').remove();

            mediator.trigger(
                'items-manager:table:remove:' + this._getIdentifier(),
                this.options.collection,
                model,
                this.element
            );
        },

        _onResetCollection: function() {
            this._renderCollection();

            mediator.trigger(
                'items-manager:table:reset:' + this._getIdentifier(),
                this.options.collection,
                this.element
            );
        },

        _renderCollection: function() {
            this.element.empty();
            const {collection} = this.options;
            collection.each(model => this._onModelAdded(model, collection));
        },

        _renderModel: function(model) {
            const collection = this.options.collection;
            const index = collection.indexOf(model);
            const data = _.extend({
                cid: model.cid,
                isFirst: index === 0,
                isLast: index === collection.length - 1
            }, model.toJSON());
            return this._itemRender(this.itemTemplate, data);
        },

        _itemRender: function(tmpl, data) {
            return tmpl(data);
        },

        _onAction: function(ev) {
            ev.preventDefault();

            const $el = $(ev.currentTarget);
            const cid = $el.closest('[data-cid]').data('cid');
            const model = this.options.collection.get(cid);
            if (!model) {
                return;
            }

            const data = $el.data();
            const action = data.collectionAction;
            const handler = this.options[action + 'Handler'];
            if (typeof handler === 'function') {
                handler(model, data);
            } else {
                model.trigger('action:' + action, model, data);
            }
        },

        _getIdentifier: function() {
            return _.first(_.first(this.element).className.split(' '));
        }
    });

    return $;
});
