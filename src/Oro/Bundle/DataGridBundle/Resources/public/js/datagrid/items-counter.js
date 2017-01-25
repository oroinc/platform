define([
    'jquery',
    'underscore',
    'backbone',
    'tpl!../../templates/datagrid/items-counter.html'
], function($, _, Backbone, template) {
    'use strict';

    var ItemsCounter;

    /**
     * Datagrid simple pagination widget
     *
     * @class   orodatagrid.datagrid.ItemsCounter
     * @extends Backbone.View
     */
    ItemsCounter = Backbone.View.extend({
        /** @property */
        enabled: true,

        /** @property */
        hidden: false,

        /** @property */
        template: template,

        /** @property */
        className: 'grid__tool',

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

            ItemsCounter.__super__.initialize.call(this, options);
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
                state: state
            }));

            if (this.hidden) {
                this.$el.hide();
            }

            return this;
        }
    });

    return ItemsCounter;
});
