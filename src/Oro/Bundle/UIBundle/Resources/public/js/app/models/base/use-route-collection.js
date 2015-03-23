/*global define*/
define([
    './model',
    '../route',
    './collection'
], function (BaseModel, Route, BaseCollection) {
    'use strict';

    var UseRouteCollection;

    UseRouteCollection = BaseCollection.extend({
        routeName: '',
        route: null,
        state: null,
        stateDefaults: {},
        routeParams: {},
        lastUrl: null,
        initialize: function (models, options) {
            if (!options) {
                options = {};
            }
            // initialize state
            this.state = new BaseModel(options.state);
            this.state.on('change', this.checkRouteChange, this);

            // initialize route
            this.route = new Route(_.extend(
                {routeName: this.routeName},
                _.pick(options, ['routeName']),
                options.routeParams,
                this.routeParams
            ));
            this.route.on('change', this.checkRouteChange, this);
            UseRouteCollection.__super__.initialize.apply(this, arguments);
        },

        url: function () {
            return this.route.getUrl(this.state.toJSON());
        },

        sync: function (type, self, options) {
            this.beginSync();
            this.lastUrl = options.url || this.url();
            return UseRouteCollection.__super__.sync.apply(this, arguments);
        },

        parse: function (response) {
            this.finishSync();
            this.state.set(_.omit(response, ['data']));
            return response.data;
        },

        /**
         * Callback for state and route changes
         */
        checkRouteChange: function () {
            var newUrl = this.url();
            if (newUrl !== this.lastUrl) {
                this.fetch();
            }
        },

        /**
         * @inheritDoc
         */
        serialize: function () {
            var data = UseRouteCollection.__super__.serialize.apply(this, arguments);
            data.state = this.state.serialize();
            return data;
        }
    });

    return UseRouteCollection;
});
