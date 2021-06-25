define([
    'backbone',
    'underscore',
    './collection',
    'oroui/js/items-manager/table',
    'jquery.select2'
], function(Backbone, _, ItemCollection) {
    'use strict';

    /**
     * @export  orodashboard/js/items/view
     * @class   orodashboard.items.Model
     * @extends Backbone.Model
     */
    const DashboardItemsView = Backbone.View.extend({
        events: {
            'change .item-select': '_toggleButtons',
            'click .add-button:not(.disabled)': '_onAddClick',
            'click .add-all-button:not(.disabled)': '_onAddAllClick'
        },

        selectTplSelector: '#widget-items-item-select-template',
        itemTplSelector: '#widget-items-item-template',

        requiredOptions: [
            'itemsData',
            'baseName'
        ],

        items: null,
        filteredItems: null,
        itemSelect: null,

        /**
         * @inheritdoc
         */
        constructor: function DashboardItemsView(options) {
            DashboardItemsView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            _.each(this.requiredOptions, function(optionName) {
                if (!_.has(options, optionName)) {
                    throw new Error('Required option "' + optionName + '" not found.');
                }
            });

            this.items = this._initializeItems(options.itemsData, options.baseName);

            this._initializeFilter(this.items, options);
            this._initializeItemGrid(this.items, options);
            this._toggleButtons();

            this.$dialog = this.$el.closest('.ui-dialog');
            this.$dialog.css('top', 0);
        },

        _initializeItems: function(itemsData, baseName) {
            const items = new ItemCollection(itemsData);
            items.each(function(item, index) {
                item.set('namePrefix', baseName + '[' + index + ']');
            });

            return items;
        },

        _initializeFilter: function(items, options) {
            const selectTpl = _.template(Backbone.$(this.selectTplSelector).html());
            const select = selectTpl({
                items: items
            });

            const $filterContainer = this.$('.controls:first');
            $filterContainer.prepend(select);
            this.itemSelect = $filterContainer.find('select');
            this.itemSelect.inputWidget('create', 'select2', {
                initializeOptions: {
                    allowClear: true,
                    placeholder: options.placeholder || null
                }
            });

            items.on('change:show', function(model) {
                const $option = this.itemSelect.find('option[value=' + model.id + ']');
                if (model.get('show')) {
                    $option.addClass('hide');
                } else {
                    $option.removeClass('hide');
                }
            }, this);

            const showedItems = items.where({show: true});
            _.each(showedItems, function(item) {
                const $option = this.itemSelect.find('option[value=' + item.id + ']');
                $option.addClass('hide');
            }, this);
        },

        _initializeItemGrid: function(items) {
            const $itemContainer = this.$('.item-container');
            const showedItems = items.where({show: true});
            const filteredItems = this.filteredItems = new ItemCollection(showedItems, {comparator: 'order'});

            $itemContainer.itemsManagerTable({
                itemTemplate: Backbone.$(this.itemTplSelector).html(),
                collection: filteredItems,
                moveUpHandler: this.moveUpHandler.bind(this),
                moveDownHandler: this.moveDownHandler.bind(this)
            });

            filteredItems.on('sort add', function() {
                this.each(function(model, index) {
                    model.set('order', index, {silent: true});
                    $itemContainer.find('[data-cid="' + model.cid + '"] input.order')
                        .val(index)
                        .trigger('change');
                });
            });

            filteredItems.on('action:delete', function(model) {
                model.set('show', false);
            });

            items.on('change:show', function(model) {
                if (model.get('show')) {
                    filteredItems.add(model);
                } else {
                    filteredItems.remove(model);
                }
            });

            $itemContainer.on('change', function(e) {
                let value;
                const $target = Backbone.$(e.target);
                const item = items.get($target.closest('tr').data('cid'));
                if (item) {
                    value = $target.is(':checkbox') ? $target.is(':checked') : $target.val();
                    item.set($target.data('name'), value);
                }
            });
        },

        _onAddClick: function() {
            const item = this.itemSelect.inputWidget('val');
            const model = this.items.get(item);

            model.set('show', true);

            this.itemSelect.inputWidget('val', '');
            this.itemSelect.change();
        },

        _onAddAllClick: function() {
            this.items.each(function(item) {
                item.set('show', true);
            });

            this.itemSelect.inputWidget('val', '');
            this.itemSelect.change();
        },

        _toggleButtons: function() {
            if (this.itemSelect.inputWidget('val')) {
                this.$('.add-button').removeClass('disabled');
            } else {
                this.$('.add-button').addClass('disabled');
            }
        },

        moveUpHandler: function(model) {
            this._moveModel(model, -1);
        },

        moveDownHandler: function(model) {
            this._moveModel(model, +1);
        },

        _moveModel: function(model, shift) {
            let order;
            let targetModel;
            const collection = this.filteredItems;
            const targetIndex = collection.indexOf(model) + shift;
            if (targetIndex >= 0 && targetIndex < collection.length) {
                targetModel = collection.at(targetIndex);
                order = model.get('order');
                model.set('order', targetModel.get('order'));
                targetModel.set('order', order);
                collection.sort();
            }
        }
    });

    return DashboardItemsView;
});
