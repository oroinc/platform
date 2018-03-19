define(function(require) {
    'use strict';

    var WidgetsCollection;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var WidgetContainerModel = require('./model');

    WidgetsCollection = Backbone.Collection.extend({
        model: WidgetContainerModel,

        comparator: 'position',

        /**
         * @inheritDoc
         */
        constructor: function WidgetsCollection() {
            WidgetsCollection.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(data, options) {
            _.extend(this, _.pick(options, ['url']));
            WidgetsCollection.__super__.initialize.apply(this, arguments);
        }
    });

    return WidgetsCollection;
});
