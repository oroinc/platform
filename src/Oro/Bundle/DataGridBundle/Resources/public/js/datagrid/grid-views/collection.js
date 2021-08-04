define(function(require) {
    'use strict';

    const _ = require('underscore');
    const Backbone = require('backbone');
    const BaseCollection = require('oroui/js/app/models/base/collection');
    const GridViewsModel = require('./model');

    const GridViewsCollection = BaseCollection.extend({
        /** @property */
        model: GridViewsModel,

        /** @type {string} */
        gridName: '',

        /**
         * @inheritdoc
         */
        constructor: function GridViewsCollection(...args) {
            GridViewsCollection.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(models, options) {
            _.extend(this, _.pick(options, ['gridName']));
            GridViewsCollection.__super__.initialize.call(this, models, options);
        },

        /**
         * @inheritdoc
         */
        _prepareModel: function(attrs, options) {
            if (attrs instanceof Backbone.Model) {
                attrs.set('grid_name', this.gridName, {silent: true});
            } else {
                attrs.grid_name = this.gridName;
            }
            return GridViewsCollection.__super__._prepareModel.call(this, attrs, options);
        },

        /**
         * @inheritdoc
         */
        clone: function() {
            return new this.constructor(this.toJSON(), {gridName: this.gridName});
        },

        /**
         * Fetches key for a state hash of GridViewsCollection
         *
         * @returns {string}
         */
        stateHashKey: function() {
            return GridViewsCollection.stateHashKey(this.gridName);
        },

        /**
         * Fetches value for a state hash of GridViewsCollection
         *
         * @param {boolean=} purge If true, clears value from initial state
         * @returns {string|null}
         */
        stateHashValue: function(purge) {
            // selected grid view is already preserved in URL, no need extra value
            return null;
        }
    });

    /**
     * Generates name of URL parameter for collection state
     *
     * @static
     * @param {string} gridName
     * @returns {string}
     */
    GridViewsCollection.stateHashKey = function(gridName) {
        return 'gridViews[' + gridName + ']';
    };

    return GridViewsCollection;
});
