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
     * ```
     * CommentCollection = LoadMoreCollection.extend({
     *     routeName: 'oro_api_comment_get_items',
     *     routeAccepts: ['page', 'limit'],
     *     stateDefaults: {
     *         page: 1,
     *         limit: 10
     *     }
     * });
     *
     * var commentCollection = new CommentCollection([], {
     *     routeParams: {
     *         // specify required parameters
     *         relationId: 1,
     *         relationClass: 'Some/Class'
     *     }
     * });
     *
     * // load first page
     * commentCollection.fetch()
     *
     * // load second page
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
         * There is no need to specify here arguments which is required to build route path.
         *
         * @see RouteModel.routeAccepts
         * @type {Array.<string>}
         */
        routeAccepts: [],

        /**
         * Route object which used to generate urls. Collection will reload whenever route is changed
         * @type {RouteModel}
         */
        route: null,

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
