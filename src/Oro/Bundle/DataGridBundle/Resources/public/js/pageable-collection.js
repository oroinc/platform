define(function(require) {
    'use strict';

    var PageableCollection;
    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');
    var BackbonePageableCollection = require('backbone-pageable-collection');
    var tools = require('oroui/js/tools');

    /**
     * Object declares state keys that will be involved in URL-state saving with their shorthands
     *
     * @property {Object}
     */
    var stateShortKeys = {
        currentPage: 'i',
        pageSize: 'p',
        sorters: 's',
        filters: 'f',
        columns: 'c',
        gridView: 'v',
        urlParams: 'g'
    };

    /**
     * Quickly reset a collection by temporarily detaching the comparator of the
     * given collection, reset and then attach the comparator back to the
     * collection and sort.
     *
     * @param {Backbone.Collection} fullCollection
     * @param {Object} models
     * @param {Object} options
     * @returns {Backbone.Collection}
     */
    function resetQuickly () {

        var collection = arguments[0];
        var options = arguments[2];
        var resetArgs = _.toArray(arguments).slice(1);

        var comparator = collection.comparator;
        collection.comparator = null;

        try {
            collection.reset.apply(collection, resetArgs);
        }
        finally {
            collection.comparator = comparator;
            if (comparator && !options.reset) {
                collection.sort();
            }
        }

        return collection;
    }

    /**
     * Pageable collection
     *
     * Additional events:
     * beforeReset: Fired when collection is about to reset data (e.g. after success response)
     * updateState: Fired when collection state is updated using updateState method
     * beforeFetch: Fired when collection starts to fetch, before request is formed
     *
     * @export  orodatagrid/js/pageable-collection
     * @class   orodatagrid.PageableCollection
     * @extends Backbone.PageableCollection
     */
    PageableCollection = BackbonePageableCollection.extend({
        /**
         * Basic model to store row data
         *
         * @property {Function}
         */
        model: Backbone.Model,

        /**
         * Original set of options passed during initialization
         *
         * @property {Object}
         */
        options: {},

        /**
         * Initial state of collection
         *
         * @property
         */
        initialState: {
            currentPage: 1,
            pageSize: 25,
            totals: null,
            filters: {},
            sorters: {},
            columns: {}
        },

        /**
         * Declaration of URL parameters
         *
         * @property {Object}
         */
        queryParams: _.extend({}, BackbonePageableCollection.prototype.queryParams, {
            directions: {
                '-1': 'ASC',
                '1': 'DESC'
            },
            totalRecords: undefined,
            totalPages: undefined
        }),

        /**
         * @property {Object}
         */
        additionalParameters: {
            view: 'gridView'
        },

        /**
         * Whether multiple sorting is allowed
         *
         * @property {Boolean}
         */
        multipleSorting: true,

        /**
         * @property {Object}
         */
        urlParams: {},

        /**
         * Whether need to show all records at one page
         *
         * @property {Boolean}
         */
        onePagePagination: false,

        /**
         * Initialize basic parameters from source options
         *
         * @param models
         * @param options
         */
        initialize: function(models, options) {
            options = options || {};
            this.options = options;

            // copy initialState from the prototype to own property
            this.initialState = tools.deepClone(this.initialState);
            _.defaults(this.initialState, this.state);
            if (options.initialState) {
                if (options.initialState.sorters) {
                    _.each(options.initialState.sorters, function(direction, field) {
                        options.initialState.sorters[field] = this.getSortDirectionKey(direction);
                    }, this);
                }
                _.extend(this.initialState, options.initialState);
            }

            // copy state from the prototype to own property
            this.state = tools.deepClone(this.state);
            if (options.state) {
                if (options.state.sorters) {
                    _.each(options.state.sorters, function(direction, field) {
                        options.state.sorters[field] = this.getSortDirectionKey(direction);
                    }, this);
                }
                _.extend(this.state, options.state);
            }

            if (options.url) {
                this.url = options.url;
                this.urlParams = options.urlParams;
            }
            if (options.model) {
                this.model = options.model;
            }
            if (options.inputName) {
                this.inputName = options.inputName;
            }

            if (options.state) {
                if (options.state.currentPage) {
                    options.state.currentPage = parseInt(options.state.currentPage);
                }
                if (options.state.pageSize) {
                    options.state.pageSize = parseInt(options.state.pageSize);
                }
            }

            if (options.toolbarOptions && options.toolbarOptions.pagination) {
                if (options.toolbarOptions.pagination.onePage) {
                    this.onePagePagination = true;
                }
            }

            _.extend(this.queryParams, {
                currentPage: this.inputName + '[_pager][_page]',
                pageSize:    this.inputName + '[_pager][_per_page]',
                sortBy:      this.inputName + '[_sort_by][%field%]',
                parameters:  this.inputName + '[_parameters]'
            });

            this.on('remove', this.onRemove, this);

            PageableCollection.__super__.initialize.call(this, models, options);

            if (options.totals) {
                this.state.totals = options.totals;
            }
        },

        /**
         * Triggers when model is removed from collection.
         *
         * Ensure that state is changed after concrete model removed from collection.
         *
         * @protected
         */
        onRemove: function() {
            if (this.state.totalRecords > 0) {
                this.state.totalRecords--;
            }
        },

        /**
         * Adds filter parameters to data
         *
         * @param {Object} data
         * @param {Object=} state
         * @param {string=} prefix
         * @return {Object}
         */
        processFiltersParams: function(data, state, prefix) {
            if (!state) {
                state = this.state;
            }

            if (!prefix) {
                prefix = this.inputName + '[_filter]';
            }

            if (state.filters) {
                _.extend(
                    data,
                    this.generateParameterStrings(state.filters, prefix)
                );
            }
            return data;
        },

        /**
         * Adds columns parameters to data
         *
         * @param {Object} data
         * @param {Object=} state
         * @param {string=} prefix
         * @return {Object}
         */
        processColumnsParams: function(data, state, prefix) {
            if (!state) {
                state = this.state;
            }

            if (!prefix) {
                prefix = this.inputName + '[_columns]';
            }

            var columnsData = {};
            columnsData[prefix] = this._packColumnsStateData(state.columns);
            _.extend(data, columnsData);

            return data;
        },

        /**
         * Adds additional parameters to state
         *
         * @param {Object} state
         * @return {Object}
         */
        processAdditionalParams: function(state) {
            state = tools.deepClone(state);
            state.parameters = state.parameters || {};

            _.each(this.additionalParameters, _.bind(function(value, key) {
                if (!_.isUndefined(state[value])) {
                    state.parameters[key] = state[value];
                }
            }, this));

            return state;
        },

        /**
         * Get list of request parameters
         *
         * @param {Object} parameters
         * @param {String} prefix
         * @return {Object}
         */
        generateParameterStrings: function(parameters, prefix) {
            var localStrings = {};
            var localPrefix = prefix;
            _.each(parameters, function(filterParameters, filterKey) {
                filterKey = filterKey.toString();
                if (filterKey.substr(0, 2) !== '__') {
                    var filterKeyString = localPrefix + '[' + filterKey + ']';
                    if (_.isObject(filterParameters)) {
                        _.extend(
                            localStrings,
                            this.generateParameterStrings(filterParameters, filterKeyString)
                        );
                    } else {
                        localStrings[filterKeyString] = filterParameters;
                    }
                }
            }, this);

            return localStrings;
        },

        /**
         * Parse AJAX response
         *
         * @param resp
         * @param options
         * @return {Object}
         */
        parse: function(resp, options) {
            var responseModels = this._parseResponseModels(resp);
            var responseOptions = this._parseResponseOptions(resp);
            if (responseOptions) {
                _.extend(options, responseOptions);
            }
            this.state.totalRecords = options.totalRecords || 0;
            this.state.hideToolbar = options.hideToolbar;
            this.state = this._checkState(this.state);

            return responseModels;
        },

        /**
         * Reset collection object
         *
         * @param resp
         * @param options
         */
        reset: function(resp, options) {
            var responseModels = this._parseResponseModels(resp);
            var responseOptions = this._parseResponseOptions(resp);
            if (responseOptions) {
                _.extend(options, responseOptions);
            }
            this.trigger('beforeReset', this, responseModels, options);
            BackbonePageableCollection.prototype.reset.apply(this, arguments);
        },

        /**
         * @param {Object} resp
         * @returns {Object}
         * @protected
         */
        _parseResponseModels: function(resp) {
            if (_.has(resp, 'data')) {
                return resp.data;
            }
            return resp;
        },

        /**
         * @param {Object} resp
         * @returns {Object}
         * @protected
         */
        _parseResponseOptions: function(resp) {
            if (_.has(resp, 'options')) {
                return resp.options;
            }
            return {};
        },

        /**
         * Method is overridden to have in collection models with not unique id
         * @inheritDoc
         */
        // Update a collection by `set`-ing a new list of models, adding new ones,
        // removing models that are no longer present, and merging models that
        // already exist in the collection, as necessary. Similar to **Model#set**,
        // the core operation for updating the data contained by the collection.
        /*jshint maxstatements:false*/
        set: function(models, options) {
            options = _.defaults({}, options, {add: true, remove: true, merge: true});
            if (options.parse) {
                models = this.parse(models, options);
            }
            var singular = !_.isArray(models);
            models = singular ? (models ? [models] : []) : _.clone(models);
            var i;
            var l;
            var id;
            var model;
            var attrs;
            var existing;
            var sort;
            var at = options.at;
            var targetModel = this.model;
            var sortable = this.comparator && (at === null || at === undefined) && options.sort !== false;
            var sortAttr = _.isString(this.comparator) ? this.comparator : null;
            var toAdd = [];
            var toRemove = [];
            var modelMap = {};
            var add = options.add;
            var merge = options.merge;
            var remove = options.remove;
            var uniqueOnly = options.uniqueOnly;
            var order = !sortable && add && remove ? [] : false;

            // Turn bare objects into model references, and prevent invalid models
            // from being added.
            for (i = 0, l = models.length; i < l; i++) {
                attrs = models[i] || {};
                if (attrs instanceof Backbone.Model) {
                    id = model = attrs;
                } else {
                    id = attrs[targetModel.prototype.idAttribute || 'id'];
                }

                // If a duplicate is found, prevent it from being added and
                // optionally merge it into the existing model.
                if (uniqueOnly === true && (existing = this.get(id))) {
                    if (remove) {
                        modelMap[existing.cid] = true;
                    }
                    if (merge) {
                        attrs = attrs === model ? model.attributes : attrs;
                        if (options.parse) {
                            attrs = existing.parse(attrs, options);
                        }
                        existing.set(attrs, options);
                        if (sortable && !sort && existing.hasChanged(sortAttr)) {
                            sort = true;
                        }
                    }
                    models[i] = existing;

                // If this is a new, valid model, push it to the `toAdd` list.
                } else if (add) {
                    model = models[i] = this._prepareModel(attrs, options);
                    if (!model) {
                        continue;
                    }
                    toAdd.push(model);
                    this._addReference(model, options);
                }

                // Do not add multiple models with the same `id`.
                model = existing || model;
                if (order && (model.isNew() || !modelMap[model.id])) {
                    order.push(model);
                }
                modelMap[model.id] = true;
            }

            // Remove nonexistent models if appropriate.
            if (remove) {
                for (i = 0, l = this.length; i < l; ++i) {
                    if (!modelMap[(model = this.models[i]).cid]) {
                        toRemove.push(model);
                    }
                }
                if (toRemove.length) {
                    this.remove(toRemove, options);
                }
            }

            // See if sorting is needed, update `length` and splice in new models.
            if (toAdd.length || (order && order.length)) {
                if (sortable) {
                    sort = true;
                }
                this.length += toAdd.length;
                if (at !== null && at !== undefined) {
                    for (i = 0, l = toAdd.length; i < l; i++) {
                        this.models.splice(at + i, 0, toAdd[i]);
                    }
                } else {
                    if (order) {
                        this.models.length = 0;
                    }
                    var orderedModels = order || toAdd;
                    for (i = 0, l = orderedModels.length; i < l; i++) {
                        this.models.push(orderedModels[i]);
                    }
                }
            }

            // Silently sort the collection if appropriate.
            if (sort) {
                this.sort({silent: true});
            }

            // Unless silenced, it's time to fire all appropriate add/sort events.
            if (!options.silent) {
                for (i = 0, l = toAdd.length; i < l; i++) {
                    (model = toAdd[i]).trigger('add', model, this, options);
                }
                if (sort || (order && order.length)) {
                    this.trigger('sort', this, options);
                }
            }

            // Return the added (or merged) model (or models).
            return singular ? models[0] : models;
        },

        /**
         * Updates and checks state
         *
         * @param {Object} state
         */
        updateState: function(state) {
            var newState = _.extend({}, this.state, state);
            this.state = this._checkState(newState);
            this.trigger('updateState', this, this.state);
        },

        /**
         * @inheritDoc
         */
        _checkState: function(state) {
            var mode = this.mode;
            var links = this.links;
            var totalRecords = state.totalRecords;
            var pageSize = state.pageSize;
            var currentPage = state.currentPage;
            var firstPage = state.firstPage;
            var totalPages = state.totalPages;

            if (totalRecords !== null && totalRecords !== void 0 &&
                pageSize !== null && pageSize !== void 0 &&
                currentPage !== null && currentPage !== void 0 &&
                firstPage !== null && firstPage !== void 0 &&
                (mode === 'infinite' ? links : true)) {

                state.totalRecords = totalRecords = this.finiteInt(totalRecords, 'totalRecords');
                state.firstPage = firstPage = this.finiteInt(firstPage, 'firstPage');

                if (this.onePagePagination) {
                    state.pageSize = pageSize = state.totalRecords;
                    state.currentPage = currentPage = state.firstPage;
                } else {
                    state.pageSize = pageSize = this.finiteInt(pageSize, 'pageSize');
                    state.currentPage = currentPage = this.finiteInt(currentPage, 'currentPage');
                }

                if (pageSize < 0) {
                    throw new RangeError('"pageSize" must be >= 0');
                }

                state.totalPages = pageSize === 0 ?
                    1 : totalPages = state.totalPages = Math.ceil(totalRecords / pageSize);

                if (firstPage < 0 || firstPage > 1) {
                    throw new RangeError('"firstPage" must be 0 or 1');
                }

                state.lastPage = firstPage === 0 ? totalPages - 1 : totalPages;

                // page out of range
                if (currentPage > state.lastPage && state.pageSize > 0) {
                    state.currentPage = currentPage = state.lastPage;
                }

                if (state.pageSize === 0) {
                    state.currentPage = currentPage = 1;
                }

                // no results returned
                if (totalRecords === 0) {
                    state.currentPage = currentPage = firstPage;
                }

                if (mode === 'infinite') {
                    if (!links[currentPage + '']) {
                        throw new RangeError('No link found for page ' + currentPage);
                    }
                } else if (totalPages > 0) {
                    if (firstPage === 0 && (currentPage < firstPage || currentPage >= totalPages)) {
                        throw new RangeError('"currentPage" must be firstPage <= currentPage < totalPages ' +
                            'if 0-based. Got ' + currentPage + '.');
                    } else if (firstPage === 1 && (currentPage < firstPage || currentPage > totalPages)) {
                        throw new RangeError('"currentPage" must be firstPage <= currentPage <= totalPages ' +
                            'if 1-based. Got ' + currentPage + '.');
                    }
                } else if (currentPage !== firstPage) {
                    throw new RangeError('"currentPage" must be ' + firstPage + '. Got ' + currentPage + '.');
                }
            }

            return state;
        },

        /**
         * Asserts that val is finite integer.
         *
         * @param {*} val
         * @param {String} name
         * @return {Number}
         * @protected
         */
        finiteInt: function(val, name) {
            val *= 1;
            if (!_.isNumber(val) || _.isNaN(val) || !_.isFinite(val) || Math.floor(val) !== val) {
                throw new TypeError('"' + name + '" must be a finite integer');
            }
            return val;
        },

        /**
         * Returns an array contains the current state of a grid
         *
         * @returns {Array}
         */
        getFetchData: function() {
            var data = {};

            // extract params from a grid collection url
            var url = _.result(this, 'url') || '';
            var qsi = url.indexOf('?');
            if (qsi !== -1) {
                var nvp = url.slice(qsi + 1).split('&');
                for (var i = 0 ; i < nvp.length ; i++) {
                    var pair = nvp[i].split('=');
                    data[tools.decodeUriComponent(pair[0])] = tools.decodeUriComponent(pair[1]);
                }
            }

            var state = this._checkState(this.state);
            data = this.processQueryParams(data, state);
            data = this.processFiltersParams(data, state);
            data = this.processColumnsParams(data, state);

            return data;
        },

        /**
         * Fetch collection data
         */
        fetch: function(options) {
            options = options || {};
            options.waitForPromises = [];

            this.trigger('beforeFetch', this, options);

            if (options.waitForPromises.length) {
                var deferredFetch = $.Deferred();
                $.when.apply($, options.waitForPromises).done(_.bind(function() {
                    this._fetch(options)
                        .done(function() {
                            deferredFetch.resolveWith(this, arguments);
                        })
                        .fail(function() {
                            deferredFetch.rejectWith(this, arguments);
                        });
                }, this)).fail(function() {
                    deferredFetch.rejectWith(this, arguments);
                });

                return deferredFetch.promise();

            } else {
                return this._fetch(options);
            }
        },

        _fetch: function(options) {
            var BBColProto = Backbone.Collection.prototype;

            options = _.defaults(options || {}, {reset: true});

            var state = this._checkState(this.state);

            var mode = this.mode;

            if (mode === 'infinite' && !options.url) {
                options.url = this.links[state.currentPage];
            }

            var data = options.data || {};

            // set up query params
            var url = options.url || _.result(this, 'url') || '';
            var qsi = url.indexOf('?');
            if (qsi !== -1) {
                _.extend(data, tools.unpackFromQueryString(url.slice(qsi + 1)));
                url = url.slice(0, qsi);
            }

            options.url = url;
            options.data = data;

            data = this.processQueryParams(data, state);
            data = this.processFiltersParams(data, state);

            var fullCollection = this.fullCollection;
            var links = this.links;

            if (mode !== 'server') {

                var self = this;
                var success = options.success;
                options.success = function(col, resp, opts) {

                    // make sure the caller's intent is obeyed
                    opts = opts || {};
                    if (_.isUndefined(options.silent)) {
                        delete opts.silent;
                    } else {
                        opts.silent = options.silent;
                    }

                    var models = col.models;
                    var currentPage = state.currentPage;

                    if (mode === 'client') {
                        resetQuickly(fullCollection, models, opts);
                    } else if (links[currentPage]) { // refetching a page
                        var pageSize = state.pageSize;
                        var pageStart = (state.firstPage === 0 ?
                            currentPage :
                            currentPage - 1) * pageSize;
                        var fullModels = fullCollection.models;
                        var head = fullModels.slice(0, pageStart);
                        var tail = fullModels.slice(pageStart + pageSize);
                        fullModels = head.concat(models).concat(tail);
                        fullCollection.update(fullModels,
                            _.extend({silent: true, sort: false}, opts));
                        if (fullCollection.comparator) {
                            fullCollection.sort();
                        }
                        fullCollection.trigger('reset', fullCollection, opts);
                    } else { // fetching new page
                        fullCollection.add(models, _.extend({at: fullCollection.length,
                            silent: true}, opts));
                        fullCollection.trigger('reset', fullCollection, opts);
                    }

                    if (success) {
                        success(col, resp, opts);
                    }
                };

                // silent the first reset from backbone
                return BBColProto.fetch.call(self, _.extend({}, options, {silent: true}));
            }

            return BBColProto.fetch.call(this, options);
        },

        /**
         * Process parameters which are sending to server
         *
         * @param {Object} data
         * @param {Object} state
         * @return {Object}
         */
        processQueryParams: function(data, state) {
            state = this.processAdditionalParams(state);
            var pageablePrototype = PageableCollection.prototype;

            // map params except directions
            var queryParams = this.mode === 'client' ?
                _.pick(this.queryParams, 'sorters') :
                _.omit(_.pick(this.queryParams, _.keys(pageablePrototype.queryParams)), 'directions');

            var i;
            var kvp;
            var k;
            var v;
            var kvps = _.pairs(queryParams);
            var thisCopy = _.clone(this);
            for (i = 0; i < kvps.length; i++) {
                kvp = kvps[i];
                k = kvp[0];
                v = kvp[1];
                v = _.isFunction(v) ? v.call(thisCopy) : v;
                if (state[k] !== null && state[k] !== undefined && v !== null && v !== undefined) {
                    data[v] = state[k];
                }
            }

            // set sorting parameters
            if (state.sorters) {
                _.each(state.sorters, function(direction, field) {
                    var key = this.queryParams.sortBy.replace('%field%', field);
                    data[key] = this.queryParams.directions[direction];
                }, this);
            }

            // map extra query parameters
            var extraKvps = _.pairs(_.omit(this.queryParams,
                _.keys(pageablePrototype.queryParams)));
            for (i = 0; i < extraKvps.length; i++) {
                kvp = extraKvps[i];
                v = kvp[1];
                v = _.isFunction(v) ? v.call(thisCopy) : v;
                data[kvp[0]] = v;
            }

            // unused parameters
            delete data[queryParams.order];
            delete data[queryParams.sortKey];

            return data;
        },

        /**
         * Convert direction value to direction key
         *
         * @param {String} directionValue
         * @return {String}
         */
        getSortDirectionKey: function(directionValue) {
            var directionKey = null;
            _.each(this.queryParams.directions, function(value, key) {
                if (value === directionValue || key === directionValue) {
                    directionKey = key;
                }
            });
            return directionKey;
        },

        /**
         * Set sorting order
         *
         * @param {String} sortKey
         * @param {String} order
         * @param {Object} options
         * @return {*}
         */
        setSorting: function(sortKey, order, options) {
            var state = this.state;

            state.sorters = state.sorters || {};

            if (this.multipleSorting) {
                // there is always must be at least one sorted column
                if (_.keys(state.sorters).length <= 1 && !order) {
                    order = this.getSortDirectionKey('ASC');  // default order
                }

                // last sorting has the lowest priority
                delete state.sorters[sortKey];
            } else {
                state.sorters = {};
            }

            if (order) {
                state.sorters[sortKey] = order;
            }

            var fullCollection = this.fullCollection;
            var delComp = false;
            var delFullComp = false;

            if (!order) {
                delComp = delFullComp = true;
            }

            var mode = this.mode;
            options = _.extend({side: mode === 'client' ? mode : 'server', full: true},
                options
            );

            var comparator = this._makeComparator(sortKey, order);

            var full = options.full;
            var side = options.side;

            if (side === 'client') {
                if (full) {
                    if (fullCollection) {
                        fullCollection.comparator = comparator;
                    }
                    delComp = true;
                } else {
                    this.comparator = comparator;
                    delFullComp = true;
                }
            } else if (side === 'server' && !full) {
                this.comparator = comparator;
            }

            if (delComp) {
                delete this.comparator;
            }
            if (delFullComp && fullCollection) {
                delete fullCollection.comparator;
            }

            return this;
        },

        /**
         * Clone collection
         *
         * @return {PageableCollection}
         */
        clone: function() {
            var newCollection = new PageableCollection(this.toJSON(), tools.deepClone(this.options));
            newCollection.state = tools.deepClone(this.state);
            newCollection.initialState = tools.deepClone(this.initialState);
            return newCollection;
        },

        /**
         * Fetches value for a state hash
         *  - this value is used to preserve collection state in URL
         *
         * @param {boolean=} purge If true, clears value from initial state
         * @returns {string|null}
         */
        stateHashValue: function(purge) {
            var hash;

            hash = this._encodeStateData(this.state);
            if (purge && hash === this._encodeStateData(this.initialState)) {
                // if the state is the same as initial, remove URL param for grid state
                hash = null;
            }
            return hash;
        },

        /**
         *
         * @param {number} pageSize
         * @param {Object} options
         * @returns {Object}
         */
        setPageSize: function(pageSize, options) {
            var result;
            // make state clone
            var oldState = _.extend({}, this.state);

            this.state.pageSize = pageSize;
            if (this.mode === 'server') {
                options = _.extend(options || {}, {reset: true});
                result = this.getPage(this.state.currentPage, options);
            } else {
                result = PageableCollection.__super__.setPageSize.call(this, pageSize, options);
            }

            // getPage has inconsistent return value: collection or promise,
            // so we have to check it's a promise
            if (_.isFunction(result.fail)) {
                result.fail(_.bind(function() {
                    // revert state if page change fail
                    this.state = this._checkState(oldState);
                }, this));
            }

            return result;
        },

        /**
         * Encodes passed state taking in account url parameters of the collection
         *
         * @param {Object} state
         * @returns {string}
         * @protected
         */
        _encodeStateData: function(state) {
            var stateData = {urlParams: this.urlParams};
            stateData = _.extend(stateData, state);
            this._packStateData(stateData);
            return PageableCollection.encodeStateData(stateData);
        },

        /**
         * Fetches key for a state hash
         *
         * @returns {string}
         */
        stateHashKey: function() {
            return PageableCollection.stateHashKey(this.inputName);
        },

        /**
         * Packs state
         * (packs state value to minified representation)
         *
         * @param {Object} data
         * @protected
         */
        _packStateData: function(data) {
            data.columns = this._packColumnsStateData(data.columns);
        },

        /**
         * Converts column state object into a string
         *
         * @param {Object} state
         * @returns {string}
         * @protected
         */
        _packColumnsStateData: function(state) {
            // convert columns state to array
            var packedState = _.map(state, function(item, columnName) {
                return _.extend({name: columnName}, item);
            });

            // sort columns by their order
            packedState = _.sortBy(packedState, 'order');

            // stringify state parts
            packedState = _.map(packedState, function(item) {
                return item.name + String(Number(item.renderable));
            }).join('.');

            return packedState;
        },

        /**
         * Unpacks state
         * (extract state value from minified object)
         *
         * @param {Object} data
         * @protected
         */
        _unpackStateData: function(data) {
            data.columns = this._unpackColumnsStateData(data.columns);
        },

        /**
         * Extract column state from string
         *
         * @param {string} packedState
         * @return {Object}
         * @protected
         */
        _unpackColumnsStateData: function(packedState) {
            return _.object(_.map(packedState.split('.'), function(value, index) {
                var columnName = value.substr(0, value.length - 1);
                return [columnName, {
                    renderable: Boolean(Number(value.substr(-1))),
                    order: index
                }];
            }));
        },

        /**
         * Compare strings to perform sorting
         *
         * @param {String} sortKey
         * @param {Integer} order
         * @return {Function}
         * @protected
         */
        _makeComparator: function(sortKey, order) {
            var state = this.state;

            sortKey = sortKey || state.sortKey;
            order = order || state.order;

            if (!sortKey || !order) {
                return;
            }

            return function(left, right) {
                var l = left.get(sortKey);
                var r = right.get(sortKey);
                var t;

                if (order === 1) {
                    t = l;
                    l = r;
                    r = t;
                }

                if (isNaN(l)) {
                    if (!isNaN(r)) {
                        return 1;
                    }
                    l = String(l).toLowerCase();
                }
                if (isNaN(r)) {
                    if (!isNaN(l)) {
                        return -1;
                    }
                    r = String(r).toLowerCase();
                }

                if (l === r) {
                    return 0;
                } else if (l < r) {
                    return -1;
                } else {
                    return 1;
                }
            };
        }
    });

    /**
     * Generates name of URL parameter for collection state
     *
     * @static
     * @param {string} inputName
     * @returns {string}
     */
    PageableCollection.stateHashKey = function(inputName) {
        return 'grid[' + inputName + ']';
    };

    /**
     * Encode state object to string
     *
     * @static
     * @param {Object} stateObject
     * @return {string}
     */
    PageableCollection.encodeStateData = function(stateObject) {
        var data;
        data = _.pick(stateObject, _.keys(stateShortKeys));
        data = tools.invertKeys(data, stateShortKeys);
        return tools.packToQueryString(data);
    };

    /**
     * Decode state object from string, operation is invert for encodeStateData.
     *
     * @static
     * @param {string} stateString
     * @return {Object}
     */
    PageableCollection.decodeStateData = function(stateString) {
        var data = tools.unpackFromQueryString(stateString);
        data = tools.invertKeys(data, _.invert(stateShortKeys));
        return data;
    };

    return PageableCollection;
});
