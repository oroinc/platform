/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    './model',
    '../route-model',
    './collection',
    'oroui/js/mediator'
], function (_, __, BaseModel, RouteModel, BaseCollection, mediator) {
    'use strict';

    var UseRouteCollection;
    /**
     *
     * @type {*|Object|void}
     */
    UseRouteCollection = BaseCollection.extend({
        /**
         * Route name this collection belongs to
         * @type {string}
         */
        routeName: '',

        /**
         * Arguments which route accepts
         * @type {Array.<string>}
         */
        routeAccepts: [],

        /**
         * Route object which used to generate urls
         * @type {RouteModel}
         */
        route: null,

        /**
         * State model
         * @type {BaseModel}
         */
        state: null,

        /**
         * Default state
         * @type {object}
         */
        stateDefaults: {},

        /**
         * Default route parameters
         * @type {object}
         */
        routeParams: {},

        /**
         * Last url from which data was fetched
         *
         * @type {string}
         * @private
         */
        _lastUrl: null,

        /**
         * @inheritDoc
         */
        initialize: function (models, options) {
            if (!options) {
                options = {};
            }
            this.on('error', this.onErrorResponse, this);
            // initialize state
            this.state = new BaseModel(_.extend({}, options.state, this.stateDefaults));
            this.state.on('change', _.bind(this.trigger, this, 'stateChange'));
            this.state.on('change', this.checkUrlChange, this);

            // initialize route
            this.route = new RouteModel(_.extend(
                {routeName: this.routeName, routeAccepts: this.routeAccepts},
                _.pick(options, ['routeName']),
                options.routeParams,
                this.routeParams
            ));
            this.route.on('change', _.bind(this.trigger, this, 'routeChange'));
            this.route.on('change', this.checkUrlChange, this);
            UseRouteCollection.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        url: function () {
            return this.route.getUrl(this.state.toJSON());
        },

        /**
         * @inheritDoc
         */
        sync: function (type, self, options) {
            this.beginSync();
            this._lastUrl = options.url || this.url();
            return UseRouteCollection.__super__.sync.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        parse: function (response) {
            this.finishSync();
            this.state.set(_.omit(response, ['data']), {silent: true});
            return response.data;
        },

        /**
         * Fetches collection if url is changed.
         * Callback for state and route changes.
         */
        checkUrlChange: function () {
            var newUrl = this.url();
            if (newUrl !== this._lastUrl) {
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

        /**
         * Default error response handle function
         * It will show errors for all http error codes except 400.
         */
        onErrorResponse: function (collection, jqxhr) {
            this.finishSync();
            if (jqxhr.status === 403) {
                mediator.execute('showFlashMessage', 'error', __('oro.ui.forbidden_error'));
            } else if (jqxhr.status !== 400) {
                // handling of 400 response should be implemented
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
            }
        }
    });

    return UseRouteCollection;
});
