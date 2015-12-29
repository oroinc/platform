define(function(require) {
    'use strict';

    var GridViewsCollection;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var BaseCollection = require('oroui/js/app/models/base/collection');
    var GridViewsModel = require('./model');

    GridViewsCollection = BaseCollection.extend({
        /** @property */
        model: GridViewsModel,

        /** @type {string} */
        gridName: '',

        /**
         * @inheritDoc
         */
        initialize: function(models, options) {
            _.extend(this, _.pick(options, ['gridName']));
            GridViewsCollection.__super__.initialize.call(this, models, options);
        },

        /**
         * @inheritDoc
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
         * @inheritDoc
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
