define(function(require) {
    'use strict';

    var BaseCollection;
    var _ = require('underscore');
    var Chaplin = require('chaplin');
    var BaseModel = require('./model');

    /**
     * @class BaseCollection
     * @extends Chaplin.Collection
     */
    BaseCollection = Chaplin.Collection.extend(/** @lends BaseCollection.prototype */{
        cidPrefix: 'cl',

        model: BaseModel,

        constructor: function BaseCollection(data, options) {
            this.cid = _.uniqueId(this.cidPrefix);
            BaseCollection.__super__.constructor.call(this, data, options);
        },

        /**
         * Returns additional parameters to be merged into serialized object
         */
        serializeExtraData: function() {
            return {};
        }
    });

    return BaseCollection;
});
