define([
    'jquery',
    'underscore',
    'backbone'
], function($, _, Backbone) {
    'use strict';

    var Pagination;

    /**
     * Datagrid pagination widget
     *
     * @export  orodatagrid/js/datagrid/pagination
     * @class   orodatagrid.datagrid.Pagination
     * @extends Backbone.View
     */
    Pagination = Backbone.View.extend({
        /** @property */
        windowSize: 10,

        /** @property */
        enabled: true,

        /** @property */
        hidden: false,

        /** @property */
        template: '#template-datagrid-toolbar-pagination',

        /** @property */
        events: {
            'click [data-grid-pagination-trigger]': 'onChangePage'
        },

        /** @property */
        fastForwardHandleConfig: {
            prev: {
                label: 'Prev',
                direction: 'prev',
                arrow: 'left',
                wrapClass: 'icon-chevron-left hide-text'
            },
            next: {
                label: 'Next',
                direction: 'next',
                arrow: 'right',
                wrapClass: 'icon-chevron-right hide-text'
            }
        },

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Object} options.fastForwardHandleConfig
         * @param {Number} options.windowSize
         */
        initialize: function(options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            this.collection = options.collection;
            this.listenTo(this.collection, 'add', this.render);
            this.listenTo(this.collection, 'remove', this.render);
            this.listenTo(this.collection, 'reset', this.render);

            this.hidden = options.hide === true;

            this.template = _.template($(options.template || this.template).html());

            Pagination.__super__.initialize.call(this, options);
        },

        /**
         * Disable pagination
         *
         * @return {*}
         */
        disable: function() {
            this.enabled = false;
            this.render();
            return this;
        },

        /**
         * Enable pagination
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
         * @protected
         */
        onChangePage: function(e) {
            e.preventDefault();

            if (!this.enabled) {
                return;
            }

            var direction = $.trim($(e.target).closest('[data-grid-pagination-trigger]')
                .data('grid-pagination-direction'));
            var ffConfig = this.fastForwardHandleConfig;

            var collection = this.collection;
            var state = collection.state;

            if (ffConfig) {
                var prevDirection = _.has(ffConfig.prev, 'direction') ? ffConfig.prev.direction : undefined;
                var nextDirection = _.has(ffConfig.next, 'direction') ? ffConfig.next.direction : undefined;
                switch (direction) {
                    case prevDirection:
                        if (collection.hasPrevious()) {
                            collection.getPreviousPage();
                        }
                        return;
                    case nextDirection:
                        if (collection.hasNext()) {
                            collection.getNextPage();
                        }
                        return;
                }
            }

            var pageIndex = $(e.target).text() * 1 - state.firstPage;
            collection.getPage(state.firstPage === 0 ? pageIndex : pageIndex + 1);
        },

        /**
         * Internal method to create a list of page handle objects for the template
         * to render them.
         *
         * @return {Array.<Object>} an array of page handle objects hashes
         */
        makeHandles: function(handles) {
            handles = handles || [];

            var collection = this.collection;
            var state = collection.state;

            // convert all indices to 0-based here
            var lastPage = state.lastPage ? state.lastPage : state.firstPage;
            lastPage = state.firstPage === 0 ? lastPage : lastPage - 1;
            var currentPage = state.firstPage === 0 ? state.currentPage : state.currentPage - 1;
            var windowStart = Math.floor(currentPage / this.windowSize) * this.windowSize;
            var windowEnd = windowStart + this.windowSize;
            windowEnd = windowEnd <= lastPage ? windowEnd : lastPage + 1;

            if (collection.mode !== 'infinite') {
                for (var i = windowStart; i < windowEnd; i++) {
                    handles.push({
                        label: i + 1,
                        title: 'No. ' + (i + 1),
                        className: currentPage === i ? 'active' : undefined
                    });
                }
            }

            var ffConfig = this.fastForwardHandleConfig;

            if (ffConfig.prev) {
                handles.unshift({
                    label: _.has(ffConfig.prev, 'label') ? ffConfig.prev.label : undefined,
                    wrapClass: _.has(ffConfig.prev, 'wrapClass') ? ffConfig.prev.wrapClass : undefined,
                    direction: _.has(ffConfig.prev, 'direction') ? ffConfig.prev.direction : undefined,
                    arrow: _.has(ffConfig.prev, 'arrow') ? ffConfig.prev.arrow : undefined,
                    className: collection.hasPrevious() ? undefined : 'disabled'
                });
            }

            if (ffConfig.next) {
                handles.push({
                    label: _.has(ffConfig.next, 'label') ? ffConfig.next.label : undefined,
                    wrapClass: _.has(ffConfig.next, 'wrapClass') ? ffConfig.next.wrapClass : undefined,
                    direction: _.has(ffConfig.next, 'direction') ? ffConfig.next.direction : undefined,
                    arrow: _.has(ffConfig.next, 'arrow') ? ffConfig.next.arrow : undefined,
                    className: collection.hasNext() ? void 0 : 'disabled'
                });
            }

            return handles;
        },

        /**
         * Render pagination
         *
         * @return {*}
         */
        render: function() {
            var state = this.collection.state;

            // prevent render if data is not loaded yet
            if (state.totalRecords === null) {
                return this;
            }

            this.$el.empty();
            this.$el.append($(this.template({
                disabled: !this.enabled || !state.totalRecords,
                handles: this.makeHandles(),
                state: state
            })));

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }
    });

    return Pagination;
});
