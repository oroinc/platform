define(function(require) {
    'use strict';

    var _ = require('underscore');
    var BaseCollection = require('oroui/js/app/models/base/collection');

    var collectionTools = {
        /**
         * Creates collection
         *
         * @param {Backbone.Collection} collection - collection to use as a base
         * @param {Object} options - options
         * @param {Function|Object|Array} options.criteria - criteria to filter children, see [_.iteratee](https://lodash.com/docs#iteratee)
         *                                                   for a list of available values
         * @param {Function} options.collection - constructor for filtered collection
         */
        createFilteredCollection: function(collection, options) {
            var criteria = _.iteratee(options.criteria);
            var Collection = options.collection || BaseCollection;

            var filteredCollection = new Collection(collection.filter(criteria), _.omit(options, ['criteria']));

            filteredCollection.listenTo(collection, 'change add remove reset sort', function() {
                filteredCollection.reset(collection.filter(criteria));
            });

            return filteredCollection;
        }
    };

    return collectionTools;
});
