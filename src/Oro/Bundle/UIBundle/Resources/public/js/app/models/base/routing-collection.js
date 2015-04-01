/*global define*/
/** @lends RoutingCollection */
define([
    'underscore',
    'orotranslation/js/translator',
    'chaplin',
    './model',
    '../route-model',
    './collection',
    'oroui/js/mediator'
], function (_, __, Chaplin, BaseModel, RouteModel, BaseCollection, mediator) {
    'use strict';
    /**
     * RoutingCollection is an abstraction of collection which uses Oro routing system.
     *
     * It keeps itself in actual state when route or state changes.
     *
     * Basic usage:
     * ```javascript
     * var CommentCollection = RoutingCollection.extend({
     *     routeName: 'oro_api_comment_get_items',
     *     routeQueryParameters: ['page', 'limit'],
     *     stateDefaults: {
     *         page: 1,
     *         limit: 10
     *     }
     * });
     *
     * var commentCollection = new CommentCollection([], {
     *     routeParams: {
     *         // specify required parameters
     *         relationId: 123,
     *         relationClass: 'Some_Class'
     *     }
     * });
     *
     * // load first page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=1)
     * commentCollection.fetch();
     *
     * // load second page (api/rest/latest/relation/Some_Class/123/comment?limit=10&page=2)
     * commentCollection.state.set({page: 2})
     * ```
     *
     * @class
     * @exports RoutingCollection
     */
    var RoutingCollection;

    RoutingCollection = BaseCollection.extend(/** @exports RoutingCollection.prototype */{
        /**
         * Route name this collection belongs to
         * @see RouteModel.routeName
         * @type {string}
         */
        routeName: '',

        /**
         * List of query parameters which this route accepts.
         *
         * @see RouteModel.routeQueryParameters
         * @type {Array.<string>}
         */
        routeQueryParameters: [],

        /**
         * Route object which used to generate urls. Collection will reload whenever route is changed.
         * Attributes will be available at the view as <%= route.page %>
         *
         * @protected
         * @type {RouteModel}
         */
        _route: null,

        /**
         * State model. Contains unparsed part of server response. Attributes will be available at the
         * view as `<%= state.count %>`
         *
         * @type {BaseModel}
         */
        state: null,

        /**
         * Default state
         *
         * @type {object}
         */
        stateDefaults: {},

        /**
         * Default route parameters
         *
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
            this.on('error', this._onErrorResponse, this);
            // initialize state
            this.state = new BaseModel(_.extend({}, options.state, this.stateDefaults));
            this.state.on('change', _.bind(this.trigger, this, 'stateChange'));

            // initialize route
            this.route = new RouteModel(_.extend(
                {routeName: this.routeName, routeQueryParameters: this.routeQueryParameters},
                this.routeParams,
                options.routeParams,
                _.pick(options, ['routeName']) // route name cannot be overridden
            ));
            this.route.on('change', _.bind(this.trigger, this, 'routeChange'));
            this.route.on('change', this.checkUrlChange, this);

            RoutingCollection.__super__.initialize.apply(this, arguments);
        },

        /**
         * Clean way to pass new parameters to route
         *
         * @param newParameters
         * @param options
         */
        updateRoute: function (newParameters, options) {
            this.route.set(newParameters, options);
            if (options && options.silent) {
                this._lastUrl = this.url();
            }
        },

        /**
         * @inheritDoc
         */
        url: function () {
            return this.route.getUrl();
        },

        /**
         * @inheritDoc
         */
        sync: function (type, self, options) {
            this.beginSync();
            this._lastUrl = options.url || this.url();
            return RoutingCollection.__super__.sync.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        parse: function (response) {
            this.finishSync();
            this.state.set(_.omit(response, ['data']));
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
        serializeExtraData: function () {
            return {
                route: this.route.serialize(),
                state: this.state.serialize(),
                syncState: this.syncState()
            };
        },

        /**
         * Default error response handler function
         * It will show error messages for all HTTP error codes except 400.
         * @protected
         */
        _onErrorResponse: function (collection, jqxhr) {
            this.finishSync();
            if (jqxhr.status === 403) {
                mediator.execute('showFlashMessage', 'error', __('oro.ui.forbidden_error'));
            } else if (jqxhr.status !== 400) {
                // handling of 400 response should be implemented
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
            }
        }
    });

    _.extend(RoutingCollection.prototype, Chaplin.SyncMachine);

    return RoutingCollection;
});
