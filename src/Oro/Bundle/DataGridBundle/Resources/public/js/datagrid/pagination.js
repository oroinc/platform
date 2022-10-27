define([
    'tpl-loader!orodatagrid/templates/datagrid/pagination.html',
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function(template, $, _, BaseView) {
    'use strict';

    /**
     * Datagrid pagination widget
     *
     * @export  orodatagrid/js/datagrid/pagination
     * @class   orodatagrid.datagrid.Pagination
     * @extends BaseView
     */
    const Pagination = BaseView.extend({
        /** @property */
        windowSize: 10,

        /** @property */
        enabled: true,

        /** @property */
        hidden: false,

        /** @property */
        template: template,

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
                wrapClass: 'fa-chevron-left hide-text'
            },
            next: {
                label: 'Next',
                direction: 'next',
                arrow: 'right',
                wrapClass: 'fa-chevron-right hide-text'
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function Pagination(options) {
            Pagination.__super__.constructor.call(this, options);
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
            this.scrollToPosition = $(options.el).closest('.toolbar').prevAll('.toolbar').position();

            if (options.template) {
                this.template = options.template;
            }
            this.template = this.getTemplateFunction();

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

            let direction = $(e.target).closest('[data-grid-pagination-trigger]')
                .data('grid-pagination-direction');

            if (typeof direction === 'string') {
                direction = direction.trim();
            }

            const ffConfig = this.fastForwardHandleConfig;

            const collection = this.collection;
            const state = collection.state;

            if (this.scrollToPosition) {
                $('body,html').stop().animate({scrollTop: this.scrollToPosition.top}, '500', 'swing');
            }

            if (ffConfig) {
                const prevDirection = _.has(ffConfig.prev, 'direction') ? ffConfig.prev.direction : undefined;
                const nextDirection = _.has(ffConfig.next, 'direction') ? ffConfig.next.direction : undefined;
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

            const pageIndex = $(e.target).text() * 1 - state.firstPage;
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

            const collection = this.collection;
            const state = collection.state;

            // convert all indices to 0-based here
            let lastPage = state.lastPage ? state.lastPage : state.firstPage;
            lastPage = state.firstPage === 0 ? lastPage : lastPage - 1;
            const currentPage = state.firstPage === 0 ? state.currentPage : state.currentPage - 1;
            const windowStart = Math.floor(currentPage / this.windowSize) * this.windowSize;
            let windowEnd = windowStart + this.windowSize;
            windowEnd = windowEnd <= lastPage ? windowEnd : lastPage + 1;

            if (collection.mode !== 'infinite') {
                for (let i = windowStart; i < windowEnd; i++) {
                    handles.push({
                        label: i + 1,
                        title: 'No. ' + (i + 1),
                        className: currentPage === i ? 'active' : undefined
                    });
                }
            }

            const ffConfig = this.fastForwardHandleConfig;

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
            const state = this.collection.state;

            // prevent render if data is not loaded yet
            if (state.totalRecords === null) {
                return this;
            }

            this.$el.empty();
            this.$el.append(this.template({
                disabled: !this.enabled || !state.totalRecords,
                handles: this.makeHandles(),
                state: state
            }));

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }
    });

    return Pagination;
});
