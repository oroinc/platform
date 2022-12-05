define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const HighlightTextView = require('oroui/js/app/views/highlight-text-view');
    const mediator = require('oroui/js/mediator');
    const tools = require('oroui/js/tools');
    const Chaplin = require('chaplin');
    const FuzzySearch = require('oroui/js/fuzzy-search');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    const ApiAccessor = require('oroui/js/tools/api-accessor');

    require('jquery.jstree');

    /**
     * Options:
     * - data - tree structure in jstree json format
     * - nodeId - identifier of selected node
     * - disabled - disable the whole tree
     *
     * @export oroui/js/app/views/jstree/base-tree-view
     * @extends oroui.app.views.base.View
     * @class oroui.app.views.BaseTreeView
     */
    const BaseTreeView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'onSelectRoute', 'onSelectRouteParameters', 'onRootSelectRoute',
            'autoSelectFoundNode', 'viewGroup', 'updateApiAccessor', 'autohideNeighbors'
        ]),

        /**
         * @property {String}
         */
        viewGroup: 'jstree',

        /**
         * @property {String}
         */
        onSelectRoute: '',

        /**
         * @property {Object}
         */
        onSelectRouteParameters: {},

        /**
         * @property {String}
         */
        onRootSelectRoute: '',

        autoSelectFoundNode: false,

        events: {
            'keypress [data-name="search"]': 'onSearchKeypress',
            'input [data-name="search"]': 'onSearchDelay',
            'change [data-name="search"]': 'onSearchDelay',
            'change [data-action-type="checkAll"]': 'onCheckAllClick',
            'click [data-name="clear-search"]': 'clearSearch'
        },

        treeEvents: {
            'after_open.jstree': 'onAfterOpen',
            'before_open.jstree': 'onBeforeOpen',
            'after_close.jstree': 'onAfterClose',
            'select_node.jstree': 'onSelect',
            'search.jstree': 'searchResultsFilter',
            'open_node.jstree': 'onOpen'
        },

        /**
         * @property {Object}
         */
        $tree: null,

        /**
         * @property {Object}
         */
        $searchContainer: null,

        /**
         * @property {Object}
         */
        $searchField: null,

        /**
         * @property {String}
         */
        searchValue: null,

        /**
         * @property {Object}
         */
        $clearSearchButton: null,

        /**
         * @property {Object}
         */
        jsTreeConfig: null,

        /**
         * @property {Object}
         */
        jsTreeInstance: null,

        /**
         * @property {Number}
         */
        nodeId: null,

        /**
         * @property {Boolean}
         */
        initialization: true,

        /**
         * @property {Boolean}
         */
        checkboxEnabled: false,

        /**
         * @property {Number}
         */
        searchTimeout: 250,

        /**
         * @property {Boolean}
         */
        _fuzzySearch: false,

        /**
         * @property {Boolean}
         */
        _foundNodes: false,

        /**
         * @property {Object}
         */
        updateApiAccessor: false,

        /**
         * @property {Object}
         */
        apiAccessor: null,

        /**
         * @property {String}
         */
        isEmptyTreeMessage: _.__('oro.ui.jstree.is_empty'),

        autohideNeighbors: false,

        /**
         * @property {Boolean}
         */
        disabled: false,

        /**
         * @inheritdoc
         */
        constructor: function BaseTreeView(options) {
            this.onBeforeOpen = _.debounce(this.onBeforeOpen, this.searchTimeout);
            this.onSearchDelay = _.debounce(this.onSearch, this.searchTimeout);
            BaseTreeView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            BaseTreeView.__super__.initialize.call(this, options);
            const nodeList = options.data;
            if (!nodeList) {
                return;
            }

            this.$tree = this.$('[data-role="jstree-container"]');
            this.$searchContainer = this.$('[data-name="jstree-search-component"]');
            this.$searchField = this.$('[data-name="search"]');
            this.$clearSearchButton = this.$('[data-name="clear-search"]');
            this.$tree.data('treeView', this);

            const config = {
                core: {
                    multiple: false,
                    data: nodeList,
                    check_callback: true,
                    force_text: true
                },
                state: {
                    key: this.viewGroup,
                    filter: this.onFilter.bind(this)
                },
                plugins: ['state', 'wholerow']
            };

            this.nodeId = options.nodeId;
            this.disabled = options.disabled || false;

            this.jsTreeConfig = this.customizeTreeConfig(options, config);

            this.subview('highlight', new HighlightTextView({
                el: this.el,
                viewGroup: this.viewGroup,
                highlightSelectors: ['.jstree-search']
            }));

            this._deferredRender();

            if (this.updateApiAccessor) {
                this._registerUpdateData();
            }
        },

        render: function() {
            if (this.jsTreeInstance) {
                this.jsTreeInstance.destroy();
            }

            this.$tree.jstree(this.jsTreeConfig);
            this.jsTreeInstance = $.jstree.reference(this.$tree);

            let treeEvents = Chaplin.utils.getAllPropertyVersions(this, 'treeEvents');
            treeEvents = _.extend.apply({}, treeEvents);
            _.each(treeEvents, function(callback, event) {
                if (this[callback]) {
                    this.$tree.off(event + '.treeEvents');
                    this.$tree.on(event + '.treeEvents', this[callback].bind(this));
                }
            }, this);

            this.$tree.one('ready.jstree', this.onReady.bind(this));
        },

        onReady: function() {
            this.initialization = false;

            const state = tools.unpackFromQueryString(location.search)[this.viewGroup] || {};
            if (this.$searchField.length && state.search) {
                this.$searchField.val(state.search).change();
            }

            this.openSelectedNode(!this.disabled);

            if (this.disabled) {
                this.toggleDisable(true);
            }

            this._resolveDeferredRender();
        },

        /**
         * @param {jQuery.Event} event
         * @param {Object} data
         *  {
         *      instance: jQuery.jsTree,
         *      node: Object
         *  }
         */
        onOpen: function(event, data) {
            const $nodeElement = $(this.jsTreeInstance.get_node(data.node, true));
            if (this.disabled) {
                $nodeElement.find('.jstree-wholerow').addClass('jstree-wholerow-disabled');
            }
        },

        /**
         * @param {Boolean} includeSelf
         */
        openSelectedNode: function(includeSelf) {
            const nodes = this.jsTreeInstance.get_selected();
            const parents = [];
            _.each(nodes, function(node) {
                const parent = this.jsTreeInstance.get_parent(node);
                if (parent) {
                    parents.push(parent);
                }
            }, this);

            this.jsTreeInstance.open_node(includeSelf ? parents.concat(nodes) : parents);
        },

        /**
         * @param {Boolean} state
         */
        toggleDisable: function(state) {
            this.disabled = state;
            this.disableSearchField(state);

            const nodes = this.jsTreeInstance.get_json('#', {flat: true, no_data: true, no_state: true});
            if (state === true) {
                _.each(nodes, node => {
                    this.jsTreeInstance.disable_node(node);
                    $(this.jsTreeInstance.get_node(node, true))
                        .find('.jstree-wholerow')
                        .addClass('jstree-wholerow-disabled');
                });
            } else {
                _.each(nodes, node => {
                    this.jsTreeInstance.enable_node(node);
                    $(this.jsTreeInstance.get_node(node, true))
                        .find('.jstree-wholerow')
                        .removeClass('jstree-wholerow-disabled');
                });
            }
        },

        /**
         * Customize jstree config to add plugins, callbacks etc.
         *
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
            if (this.checkboxEnabled) {
                config.plugins.push('checkbox');
                config.checkbox = {
                    whole_node: false,
                    tie_selection: false,
                    three_state: false
                };

                this.$('[data-role="jstree-checkall"]').show();
            }

            if (this.$searchField.length) {
                config.plugins.push('search');
                config.search = {
                    close_opened_onclear: true,
                    show_only_matches: true,
                    show_only_matches_children: false,
                    case_sensitive: false,
                    search_callback: this.searchCallback.bind(this)
                };
            }

            if (_.isUndefined(this.autohideNeighbors)) {
                config.autohideNeighbors = tools.isMobile();
            } else {
                config.autohideNeighbors = this.autohideNeighbors;
            }

            return config;
        },

        /**
         * Update core config for the tree
         *
         * @param {Object} config
         */
        updateTreeConfig: function(key, config) {
            if (_.isString(key)) {
                this.jsTreeConfig[key] = _.extend(this.jsTreeConfig[key], config);
                return;
            }

            if (_.isObject(key)) {
                this.jsTreeConfig = _.extend(this.jsTreeConfig, key);
                return;
            }
        },

        isNodeHasHandler: function(node) {
            return true;
        },

        isElementHasHandler: function($el) {
            const node = this.jsTreeInstance.get_node($el);
            return node ? this.isNodeHasHandler(node) : false;
        },

        onSearchKeypress: function(e) {
            if (e.keyCode === 13) {
                // enter in search field
                return this.onSearchEnter(e);
            }
        },

        onSearchEnter: function(e) {
            if (this.autoSelectFoundNode) {
                this.onSearch(e);
                const $results = this.$('a.jstree-search');
                if ($results.length === 1 && this.isElementHasHandler($results)) {
                    $results.click();
                }
            }

            e.preventDefault();
            this.$searchField.focus();
            return false;
        },

        onSearchDelay: function(e) {
            return this.onSearch(e);
        },

        searchCallback: function(query, node) {
            let searchBy = node.original.search_by || [];
            searchBy.unshift(node.text);
            searchBy = _.uniq(searchBy);

            query = query.toLowerCase();

            let search;
            if (this._fuzzySearch) {
                search = function(str) {
                    return FuzzySearch.isMatched(str, query);
                };
            } else {
                search = function(str) {
                    return str.toLowerCase().indexOf(query) !== -1;
                };
            }

            for (let i = 0, length = searchBy.length; i < length; i++) {
                if (searchBy[i] && search(searchBy[i].toString())) {
                    this._foundNodes = true;
                    return true;
                }
            }

            return false;
        },

        onSearch: function(event) {
            let value = $(event.target).val();
            value = _.trim(value).replace(/\s+/g, ' ');
            if (this.searchValue === value) {
                return;
            }
            this.searchValue = value;

            this.jsTreeInstance.searchValue = value;
            this.jsTreeInstance.settings.autohideNeighbors = tools.isMobile() && _.isEmpty(value);

            this._fuzzySearch = false;
            this._foundNodes = false;

            this.jsTreeInstance.show_all();
            this.jsTreeInstance.search(value);
            if (!this._foundNodes) {
                this._fuzzySearch = true;

                this.jsTreeInstance.show_all();
                this.jsTreeInstance.search(value);
            }

            this._toggleSearchState(value);

            this._changeUrlParam('search', value.length ? value : null);
            mediator.trigger(this.viewGroup + ':highlight-text:update', value, this._fuzzySearch);
        },

        /**
         * Toggle active-search class for search component
         */
        _toggleSearchState: function(str) {
            this.$searchContainer.toggleClass('active-search', str !== '');
        },

        /**
         * Clear search field value
         */
        clearSearch: function() {
            this.$searchField.val('');
            this.$searchField.change();
        },

        /**
         * Search results filter
         *
         * @param {Object} event
         * @param {Object} data
         */
        searchResultsFilter: function(event, data) {
            if (!data || !data.instance) {
                return;
            }

            if (this._foundNodes) {
                this.addChildToSearchResults(data.res);
            } else {
                this.showSearchResultMessage(_.__('oro.ui.jstree.search.search_no_found'));
            }
        },

        /**
         * Show child of found nodes without handler
         *
         * @param {Array} nodes
         */
        addChildToSearchResults: function(nodes) {
            if (this.jsTreeInstance.settings.search.show_only_matches_children) {
                return;
            }

            let additionalNodes = [];
            const nodesWithAdditional = [];

            _.each(this.$('li.jstree-node:visible'), function(item) {
                const $item = $(item);
                const node = this.jsTreeInstance.get_node(item.id);
                if (!node.children_d.length || this.isNodeHasHandler(node)) {
                    return;
                }

                const $child = $item.children('.jstree-children');
                if ($child.is(':visible')) {
                    return;
                }

                additionalNodes = additionalNodes.concat(node.children_d);
                nodesWithAdditional.push(node.id);
            }, this);

            if (!additionalNodes.length) {
                return;
            }

            nodes = _.uniq(nodes);
            additionalNodes = _.uniq(additionalNodes);
            additionalNodes = nodes.slice().concat(additionalNodes);
            this.jsTreeInstance.show_node(additionalNodes);

            if (nodes.length > 1) {
                this.jsTreeInstance.close_node(nodesWithAdditional, 0);
            } else {
                this.jsTreeInstance.open_node(nodesWithAdditional, 0);
            }
        },

        /**
         * Show search result message
         *
         * @param {string} message
         */
        showSearchResultMessage: function(message) {
            if (_.isUndefined(message)) {
                message = '';
            }
            this.$tree.find('>ul').hide();
            this.$tree.append(
                $('<div />', {
                    'class': 'no-data',
                    'text': message
                })
            );
        },

        onSelect: function(event, data) {
            if (!tools.isMobile() || !this.jsTreeInstance.settings.autohideNeighbors) {
                return;
            }
            const selectedNode = data.node;
            if (selectedNode) {
                selectedNode.parents.reverse().slice(1).forEach(parentId => {
                    const node = this.jsTreeInstance.get_node(parentId);
                    this.hideNeighbors(node, 0);
                });
            }
            this.jsTreeInstance.close_all(selectedNode);
        },

        /**
         * Filters tree state
         *
         * @param {Object} state
         * @returns {Object}
         */
        onFilter: function(state) {
            if (this.nodeId) {
                state.core.selected = [this.nodeId];
            } else {
                state.core.selected = [];
            }
            this.$tree.jstree().select_node(this.nodeId);
            return state;
        },

        onCheckAllClick: function(event) {
            const $target = $(event.target);
            const action = $target.data('action-type');

            if (action === 'checkAll') {
                this.$tree.jstree('check_all');
                $target.data('action-type', 'unCheckAll');
            } else {
                this.$tree.jstree('uncheck_all');
                $target.data('action-type', 'checkAll');
            }
        },

        onBeforeOpen: function(event, data) {
            if (this.jsTreeInstance.settings.autohideNeighbors) {
                data.node.children.forEach(nodeId => {
                    if (!this.jsTreeInstance.is_leaf(nodeId)) {
                        this.jsTreeInstance.close_node(nodeId);
                    }
                });
            }
            mediator.trigger(this.viewGroup + ':highlight-text:update', this.searchValue ? this.searchValue : '');
        },

        onAfterOpen: function(event, data) {
            if (this.jsTreeInstance.settings.autohideNeighbors) {
                this.hideNeighbors(data.node, null);
            }
        },

        onAfterClose: function(event, data) {
            if (this.jsTreeInstance.settings.autohideNeighbors) {
                this.showNeighbors(data.node, null);
            }
        },

        /**
         * Return children of the node
         *
         * @param {Object} node
         * @returns {Array} children of the node;
         */
        getChildren: function(node) {
            return node.children.map(itemId => this.jsTreeInstance.get_node(itemId));
        },

        /**
         * Return neighbors of the node
         *
         * @param {Object} node
         * @returns {Array} neighbors of the node;
         */
        getNeighbors: function(node) {
            if (!node.parent) {
                return [];
            }

            const parent = this.jsTreeInstance.get_node(node.parent);

            return this.getChildren(parent)
                .filter(item => {
                    return item.id !== node.id;
                });
        },

        /**
         * shows neighbors
         *
         * @param {Object} node
         * @param {String|Number} animationDuration
         * @returns {Object} node;
         */
        showNeighbors: function(node, animationDuration) {
            animationDuration = animationDuration || this.jsTreeInstance.settings.core.animation;

            this.getNeighbors(node).forEach(item => {
                this.jsTreeInstance
                    .get_node(item.id, true)
                    .fadeIn(animationDuration);
            });

            return node;
        },

        /**
         * hides neighbors
         *
         * @param {Object} node
         * @param {String|Number} animationDuration
         * @returns {Object} node;
         */
        hideNeighbors: function(node, animationDuration) {
            animationDuration = animationDuration || this.jsTreeInstance.settings.core.animation;
            this.getNeighbors(node).forEach(neighbor => {
                this.jsTreeInstance
                    .get_node(neighbor.id, true)
                    .fadeOut(animationDuration);
            });

            return node;
        },

        /**
         * Disable/enable search field in the tree
         *
         * @param {Boolean} state
         */
        disableSearchField: function(state) {
            $(this.$searchField).prop('disabled', state);
        },

        /**
         *
         */
        updateTree: function(params) {
            if (!this.apiAccessor) {
                return;
            }

            this.loadingMask.show();
            this.apiAccessor.send(params).then(this._updateTreeFromData.bind(this));
        },

        /**
         *
         * @param param
         * @param value
         * @private
         */
        _changeUrlParam: function(param, value) {
            param = this.viewGroup + '[' + param + ']';
            mediator.execute('changeUrlParam', param, value);
        },

        /**
         *
         * @private
         */
        _registerUpdateData: function() {
            this.apiAccessor = new ApiAccessor(
                this.updateApiAccessor
            );

            this.loadingMask = new LoadingMaskView({
                container: this.$el
            });
        },

        /**
         * Re-render tree with new data
         *
         * @param {*} data
         * @private
         */
        _updateTreeFromData: function(data) {
            data = data.tree;
            this.updateTreeConfig('core', {
                data: _.isString(data) ? JSON.parse(data) : data
            });

            if (!_.isEmpty(data)) {
                this.disableSearchField(false);
                this.render();
            } else {
                this.showSearchResultMessage(this.isEmptyTreeMessage);
                this.disableSearchField(true);
                this.onDeselect();
            }

            this.loadingMask.hide();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$tree.off();
            this.$tree.parent().off();

            delete this.$tree;
            delete this.$searchField;
            delete this.$searchContainer;
            delete this.$clearSearchButton;
            delete this.jsTreeInstance;
            delete this.jsTreeConfig;

            return BaseTreeView.__super__.dispose.call(this);
        }
    });

    return BaseTreeView;
});
