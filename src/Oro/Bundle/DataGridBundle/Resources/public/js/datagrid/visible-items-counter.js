define([
    'jquery',
    'underscore',
    'backbone',
    'tpl!../../templates/datagrid/visible-items-counter.html'
], function($, _, Backbone, template) {
    'use strict';

    var VisibleItemsCounter;

    /**
     * Datagrid simple pagination widget
     *
     * @class   orodatagrid.datagrid.Pagination
     * @extends Backbone.View
     */
    VisibleItemsCounter = Backbone.View.extend({
        /** @property */
        enabled: true,

        /** @property */
        hidden: false,

        /** @property */
        template: template,

        /** @property */
        className: 'visible-items-counter pagination-centered',

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
            this.hidden = options.hidden !== false;
            if (!options.collection) {
                throw new TypeError('"collection" is required');
            }

            this.collection = options.collection;
            this.listenTo(this.collection, 'add', this.render);
            this.listenTo(this.collection, 'remove', this.render);
            this.listenTo(this.collection, 'reset', this.render);

            this.hidden = options.hide === true;

            VisibleItemsCounter.__super__.initialize.call(this, options);
        },

        /**
         * Disables view
         *
         * @return {*}
         */
        disable: function() {
            return this;
        },

        /**
         * Enable view
         *
         * @return {*}
         */
        enable: function() {
            return this;
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
            this.$el.html(this.template({
                disabled: !this.enabled || !state.totalRecords,
                state: _.extend({length: this.collection.length}, state)
            }));

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }
    });

    return VisibleItemsCounter;
});
