/*global define*/
define([
    './model',
    '../route',
    './collection',
    'oroui/js/mediator'
], function (BaseModel, Route, BaseCollection, mediator) {
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
            this.on('error', this.onErrorResponse, this);
            // initialize state
            this.state = new BaseModel(_.extend({}, options.state, this.stateDefaults));
            this.state.on('change', _.bind(this.trigger, this, 'stateChange'));
            this.state.on('change', this.checkRouteChange, this);

            // initialize route
            this.route = new Route(_.extend(
                {routeName: this.routeName},
                _.pick(options, ['routeName']),
                options.routeParams,
                this.routeParams
            ));
            this.route.on('change', _.bind(this.trigger, this, 'routeChange'));
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
            this.state.set(_.omit(response, ['data']), {silent: true});
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
            data.state = this.state.toJSON();
            data.syncState = this.syncState();
            return data;
        },

        onErrorResponse: function (collection, jqxhr) {
            this.finishSync();
            if (jqxhr.status === 403) {
                mediator.execute('showFlashMessage', 'error', __('oro.ui.forbidden_error'));
            } else if (jqxhr.status !== 400) {
                // handling of 400 response should be implemented
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
            }
        },
    });

    return UseRouteCollection;
});
