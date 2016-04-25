define([
    'jquery',
    'underscore',
    'backbone'
], function($, _, Backbone) {
    'use strict';

    var PageSize;

    /**
     * Datagrid page size widget
     *
     * @export  orodatagrid/js/datagrid/page-size
     * @class   orodatagrid.datagrid.PageSize
     * @extends Backbone.View
     */
    PageSize = Backbone.View.extend({
        /** @property */
        template: '#template-datagrid-toolbar-page-size',

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

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Array} [options.items]
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            if (options.items) {
                this.items = options.items;
            }

            this.template = _.template($(options.template || this.template).html());
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
            var pageSize = parseInt($(e.target).data('size') || $(e.target).val(), 10);
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

            var currentSizeLabel = _.filter(
                this.items,
                _.bind(
                    function(item) {
                        return item.size === undefined ?
                            this.collection.state.pageSize === item : this.collection.state.pageSize === item.size;
                    },
                    this
                )
            );

            if (currentSizeLabel.length > 0) {
                currentSizeLabel = _.isUndefined(currentSizeLabel[0].label) ?
                    currentSizeLabel[0] : currentSizeLabel[0].label;
            } else {
                currentSizeLabel = this.items[0];
            }

            this.$el.append($(this.template({
                disabled: !this.enabled || !this.collection.state.totalRecords,
                collectionState: this.collection.state,
                items: this.items,
                currentSizeLabel: currentSizeLabel
            })));

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }
    });

    return PageSize;
});
