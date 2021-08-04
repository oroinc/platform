define([
    'tpl-loader!orodatagrid/templates/datagrid/page-size.html',
    'jquery',
    'underscore',
    'backbone'
], function(template, $, _, Backbone) {
    'use strict';

    /**
     * Datagrid page size widget
     *
     * @export  orodatagrid/js/datagrid/page-size
     * @class   orodatagrid.datagrid.PageSize
     * @extends Backbone.View
     */
    const PageSize = Backbone.View.extend({
        /** @property */
        template: template,

        /** @property */
        events: {
            'click [data-grid-pagesize-trigger]': 'onChangePageSize',
            'change [data-grid-pagesize-selector]': 'onChangePageSize'
        },

        /** @property */
        items: [10, 25, 50, 100],

        /** @property */
        enabled: true,

        /** @property */
        hidden: false,

        /** @property */
        showLabels: false,

        /**
         * @inheritdoc
         */
        constructor: function PageSize(options) {
            PageSize.__super__.constructor.call(this, options);
        },

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Array} [options.items] page size values
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            if (options.items) {
                this.items = this.preparePageSizes(options.items);
            }

            if (typeof this.template !== 'function' || options.template) {
                this.template = _.template($(options.template || this.template).html());
            }

            this.collection = options.collection;
            this.listenTo(this.collection, 'add', this.render);
            this.listenTo(this.collection, 'remove', this.render);
            this.listenTo(this.collection, 'reset', this.render);

            this.enabled = options.enable !== false;
            this.hidden = options.hide === true;

            PageSize.__super__.initialize.call(this, options);
        },

        /**
         * Disable page size
         *
         * @return {*}
         */
        disable: function() {
            this.enabled = false;
            this.render();
            return this;
        },

        /**
         * Convert each page size value to integer value
         *
         * @param {array} items
         * @returns {array}
         */
        preparePageSizes(items) {
            return items.map(item => {
                if (_.isObject(item)) {
                    return {
                        ...item,
                        size: parseInt(item.size, 10)
                    };
                }

                return parseInt(item, 10);
            });
        },

        /**
         * Enable page size
         *
         * @return {*}
         */
        enable: function() {
            this.enabled = true;
            this.render();
            return this;
        },

        /**
         * jQuery event handler for the page handlers. Goes to the right page upon clicking.
         *
         * @param {Event} e
         */
        onChangePageSize: function(e) {
            e.preventDefault();
            const pageSize = parseInt($(e.target).data('size') || $(e.target).val(), 10);
            if (pageSize !== this.collection.state.pageSize) {
                this.changePageSize(pageSize);
            }
        },

        changePageSize: function(pageSize) {
            this.collection.setPageSize(pageSize);
            return this;
        },

        render: function() {
            this.$el.empty();
            const {pageSize: currentPageSize} = this.collection.state;
            const currentItem =
                this.items.find(item => currentPageSize === (typeof item.size !== 'undefined' ? item.size : item));

            this.$el.append($(this.template({
                disabled: !this.enabled || !this.collection.state.totalRecords,
                collectionState: this.collection.state,
                items: this.items,
                currentPageSize,
                currentSizeLabel: typeof currentItem.label !== 'undefined' ? currentItem.label : currentItem,
                showLabels: this.showLabels
            })));

            if (this.hidden) {
                this.$el.hide();
            }

            this.initControls();

            return this;
        }
    });

    return PageSize;
});
