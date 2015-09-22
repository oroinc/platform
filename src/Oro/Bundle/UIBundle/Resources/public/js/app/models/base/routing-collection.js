/** @lends RoutingCollection */
define([
    'underscore',
    'orotranslation/js/translator',
    'chaplin',
    './model',
    '../route-model',
    './collection',
    'oroui/js/mediator'
], function(_, __, Chaplin, BaseModel, RouteModel, BaseCollection, mediator) {
    'use strict';

    /**
     * RoutingCollection is an abstraction of collection which uses Oro routing system.
     *
     * It keeps itself in actual state when route or state changes.
     *
     * Basic usage:
     * ```javascript
     * var CommentCollection = RoutingCollection.extend({
     *     routeDefaults: {
     *         routeName: 'oro_api_comment_get_items',
     *         routeQueryParameterNames: ['page', 'limit']
     *     },
     *
     *     stateDefaults: {
     *         page: 1,
     *         limit: 10
     *     },
     *
     *     // provide access to route
     *     setPage: function (pageNo) {
     *         this._route.set({page: pageNo});
     *     }
     * });
     *
     * var commentCollection = new CommentCollection([], {
     *     routeParameters: {
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
     * commentCollection.setPage(2)
     * ```
     *
     * @class
     * @augment BaseCollection
     * @exports RoutingCollection
     */
    var RoutingCollection;

    RoutingCollection = BaseCollection.extend(/** @exports RoutingCollection.prototype */{

        /**
         * Route object which used to generate urls. Collection will reload whenever route is changed.
         * Attributes will be available at the view as <%= route.page %>
         *
         * Access to route attributes should be realized in descendants. (e.g. `setPage()` or `setPerPage()`)
         *
         * @protected
         * @type {RouteModel}
         */
        _route: null,

        /**
         * State of the collection. Must contain both settings and server response parts such as
         * totalItemsQuantity of items
         * on server. Attributes will be available at the view as `<%= state.totalItemsQuantity %>`.
         *
         * The `stateChange` event is fired when state is changed.
         *
         * Override `parse()` function to add values from server response to the state
         *
         * @protected
         * @type {BaseModel}
         */
        _state: null,

        /**
         * Default route attributes
         *
         * @member {Object}
         */
        routeDefaults: function() {
            return /** @lends RouteCollection.routeDefaults */{
                /**
                 * Route name this collection belongs to
                 * @see RouteModel.prototype.routeName
                 * @type {string}
                 */
                routeName: '',

                /**
                 * List of query parameters which this route accepts.
                 *
                 * @see RouteModel.prototype.routeQueryParameterNames
                 * @type {Array.<string>}
                 */
                routeQueryParameterNames: []
            };
        },

        /**
         * Default state
         *
         * @type {Object}
         */
        stateDefaults: {},

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
        initialize: function(models, options) {
            if (!options) {
                options = {};
            }
            this.on('error', this._onErrorResponse, this);

            // initialize state
            this._state = this._createState(options.state);
            this._state.on('change', _.bind(this.trigger, this, 'stateChange'));

            // initialize route
            this._route = this._createRoute(options.routeParameters);
            this._route.on('change', _.bind(this.trigger, this, 'routeChange'));
            this._route.on('change', this.checkUrlChange, this);

            //listen base events
            this.on('add', this._onAdd);
            this.on('remove', this._onRemove);

            RoutingCollection.__super__.initialize.apply(this, arguments);
        },

        /**
         * Creates state object. Merges attributes from all stateDefaults objects/functions in class hierarchy.
         *
         * @param parameters {Object}
         * @protected
         */
        _createState: function(parameters) {
            return new BaseModel(_.extend(
                {},
                this._mergeAllPropertyVersions('stateDefaults'),
                parameters
            ));
        },

        /**
         * Creates route. Merges attributes from all routeDefaults objects/functions in class hierarchy.
         *
         * @param parameters {Object}
         * @protected
         */
        _createRoute: function(parameters) {
            return new RouteModel(_.extend(
                {},
                this._mergeAllPropertyVersions('routeDefaults'),
                parameters
            ));
        },

        /**
         * Utility function. Extends `Chaplin.utils.getAllPropertyVersions` with merge and `_.result()` like call,
         * if property is function
         *
         * @param attrName {string} attribute to merge
         * @returns {Object}
         * @protected
         */
        _mergeAllPropertyVersions: function(attrName) {
            var attrVersion;
            var result = {};
            var attrVersions = Chaplin.utils.getAllPropertyVersions(this, attrName);
            for (var i = 0; i < attrVersions.length; i++) {
                attrVersion = attrVersions[i];
                if (_.isFunction(attrVersion)) {
                    attrVersion = attrVersion.call(this);
                }
                _.extend(result, attrVersion);
            }
            return result;
        },

        /**
         * Returns current route parameters
         *
         * @returns {Object}
         */
        getRouteParameters: function() {
            return this._route.serialize();
        },

        /**
         * Returns collection state
         *
         * @returns {Object}
         */
        getState: function() {
            return this._state.serialize();
        },

        /**
         * @inheritDoc
         */
        url: function() {
            return this._route.getUrl();
        },

        /**
         * @inheritDoc
         */
        sync: function(type, self, options) {
            this.beginSync();
            this._lastUrl = options.url || this.url();
            this.once('sync error', this.finishSync, this);
            return RoutingCollection.__super__.sync.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        parse: function(response) {
            return response.data;
        },

        /**
         * Fetches collection if url is changed.
         * Callback for state and route changes.
         */
        checkUrlChange: function() {
            var newUrl = this.url();
            if (newUrl !== this._lastUrl) {
                this.fetch();
            }
        },

        /**
         * @inheritDoc
         */
        serializeExtraData: function() {
            return {
                route: this._route.serialize(),
                state: this._state.serialize(),
                syncState: this.syncState()
            };
        },

        /**
         * Default error response handler function
         * It will show error messages for all HTTP error codes except 400.
         * @protected
         */
        _onErrorResponse: function(collection, jqxhr) {
            this.finishSync();
            if (jqxhr.status === 403) {
                mediator.execute('showFlashMessage', 'error', __('oro.ui.forbidden_error'));
            } else if (jqxhr.status !== 400) {
                // handling of 400 response should be implemented
                mediator.execute('showFlashMessage', 'error', __('oro.ui.unexpected_error'));
            }
        },

        /**
         * General callback for 'add' event
         *
         * @protected
         */
        _onAdd: function() {

        },

        /**
         * General callback for 'remove' event
         *
         * @protected
         */
        _onRemove: function() {

        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            this._route.dispose();
            this._state.dispose();
            RoutingCollection.__super__.dispose.apply(this, arguments);
        }
    });

    _.extend(RoutingCollection.prototype, Chaplin.SyncMachine);

    return RoutingCollection;
});
