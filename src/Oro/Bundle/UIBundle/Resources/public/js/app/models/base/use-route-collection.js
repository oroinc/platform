/*global define*/
/** @lends UseRouteCollection */
define([
    'underscore',
    'orotranslation/js/translator',
    './model',
    '../route-model',
    './collection',
    'oroui/js/mediator'
], function (_, __, BaseModel, RouteModel, BaseCollection, mediator) {
    'use strict';
    /**
     * UseRouteCollection is an abstraction of collection which uses Oro routing system.
     *
     * It keeps itself in actual state when route or state changes.
     *
     * Basic usage:
     * ```javascript
     * var CommentCollection = UseRouteCollection.extend({
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
     * @exports UseRouteCollection
     */
    var UseRouteCollection;

    UseRouteCollection = BaseCollection.extend(/** @exports UseRouteCollection.prototype */{
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
         * Route object which used to generate urls. Collection will reload whenever route is changed
         * @type {RouteModel}
         * @protected
         */
        _route: null,

        /**
         * State model. Collection will reload whenever state is changed
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
            this.on('error', this._onErrorResponse, this);
            // initialize state
            this.state = new BaseModel(_.extend({}, options.state, this.stateDefaults));
            this.state.on('change', _.bind(this.trigger, this, 'stateChange'));
            this.state.on('change', this.checkUrlChange, this);

            // initialize route
            this._route = new RouteModel(_.extend(
                {routeName: this.routeName, routeQueryParameters: this.routeQueryParameters},
                this.routeParams,
                options.routeParams,
                _.pick(options, ['routeName']) // route name cannot be overridden
            ));
            this._route.on('change', _.bind(this.trigger, this, 'routeChange'));
            this._route.on('change', this.checkUrlChange, this);

            UseRouteCollection.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        url: function () {
            return this._route.getUrl(this.state.toJSON());
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
        serializeExtraData: function () {
            return {
                state: this.state.toJSON(),
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

    return UseRouteCollection;
});
