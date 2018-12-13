define(function(require) {
    'use strict';

    var BaseTreeView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var HighlightTextView = require('oroui/js/app/views/highlight-text-view');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var Chaplin = require('chaplin');
    var FuzzySearch = require('oroui/js/fuzzy-search');

    require('jquery.jstree');

    /**
     * Options:
     * - data - tree structure in jstree json format
     * - nodeId - identifier of selected node
     *
     * @export oroui/js/app/views/jstree/base-tree-view
     * @extends oroui.app.views.base.View
     * @class oroui.app.views.BaseTreeView
     */
    BaseTreeView = BaseView.extend({
        autoRender: true,

        optionNames: BaseView.prototype.optionNames.concat([
            'onSelectRoute', 'onSelectRouteParameters', 'onRootSelectRoute',
            'autoSelectFoundNode', 'viewGroup', 'autohideNeighbors'
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

        autohideNeighbors: false,

        /**
         * @inheritDoc
         */
        constructor: function BaseTreeView() {
            BaseTreeView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            BaseTreeView.__super__.initialize.apply(this, arguments);
            var nodeList = options.data;
            if (!nodeList) {
                return;
            }

            this.$tree = this.$('[data-role="jstree-container"]');
            this.$searchContainer = this.$('[data-name="jstree-search-component"]');
            this.$searchField = this.$('[data-name="search"]');
            this.$clearSearchButton = this.$('[data-name="clear-search"]');
            this.$tree.data('treeView', this);
            this.onSearchDelay = _.debounce(this.onSearch, this.searchTimeout);

            var config = {
                core: {
                    multiple: false,
                    data: nodeList,
                    check_callback: true,
                    force_text: true
                },
                state: {
                    key: this.viewGroup,
                    filter: _.bind(this.onFilter, this)
                },
                plugins: ['state', 'wholerow']
            };

            this.nodeId = options.nodeId;

            this.jsTreeConfig = this.customizeTreeConfig(options, config);

            this.subview('highlight', new HighlightTextView({
                el: this.el,
                viewGroup: this.viewGroup,
                highlightSelectors: ['.jstree-search']
            }));

            this._deferredRender();
        },

        render: function() {
            if (this.jsTreeInstance) {
                this.jsTreeInstance.destroy();
            }

            this.$tree.jstree(this.jsTreeConfig);
            this.jsTreeInstance = $.jstree.reference(this.$tree);

            var treeEvents = Chaplin.utils.getAllPropertyVersions(this, 'treeEvents');
            treeEvents = _.extend.apply({}, treeEvents);
            _.each(treeEvents, function(callback, event) {
                if (this[callback]) {
                    this.$tree.off(event + '.treeEvents');
                    this.$tree.on(event + '.treeEvents', _.bind(this[callback], this));
                }
            }, this);

            this.$tree.one('ready.jstree', _.bind(this.onReady, this));
        },

        onReady: function() {
            this.initialization = false;

            var state = tools.unpackFromQueryString(location.search)[this.viewGroup] || {};
            if (this.$searchField.length && state.search) {
                this.$searchField.val(state.search).change();
            }

            this.openSelectedNode();

            this._resolveDeferredRender();
        },

        openSelectedNode: function() {
            var nodes = this.jsTreeInstance.get_selected();
            var parents = [];
            _.each(nodes, function(node) {
                var parent = this.jsTreeInstance.get_parent(node);
                if (parent) {
                    parents.push(parent);
                }
            }, this);
            this.jsTreeInstance.open_node(parents.concat(nodes));
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
                    search_callback: _.bind(this.searchCallback, this)
                };
            }

            if (_.isUndefined(this.autohideNeighbors)) {
                config.autohideNeighbors = tools.isMobile();
            } else {
                config.autohideNeighbors = this.autohideNeighbors;
            }

            return config;
        },

        isNodeHasHandler: function(node) {
            return true;
        },

        isElementHasHandler: function($el) {
            var node = this.jsTreeInstance.get_node($el);
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
                var $results = this.$('a.jstree-search');
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
            var searchBy = node.original.search_by || [];
            searchBy.unshift(node.text);
            searchBy = _.uniq(searchBy);

            query = query.toLowerCase();

            var search;
            if (this._fuzzySearch) {
                search = function(str) {
                    return FuzzySearch.isMatched(str, query);
                };
            } else {
                search = function(str) {
                    return str.toLowerCase().indexOf(query) !== -1;
                };
            }

            for (var i = 0, length = searchBy.length; i < length; i++) {
                if (searchBy[i] && search(searchBy[i].toString())) {
                    this._foundNodes = true;
                    return true;
                }
            }

            return false;
        },

        onSearch: function(event) {
            var value = $(event.target).val();
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

            var additionalNodes = [];
            var nodesWithAdditional = [];

            _.each(this.$('li.jstree-node:visible'), function(item) {
                var $item = $(item);
                var node = this.jsTreeInstance.get_node(item.id);
                if (!node.children_d.length || this.isNodeHasHandler(node)) {
                    return;
                }

                var $child = $item.children('.jstree-children');
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
            var selectedNode = data.node;
            if (selectedNode) {
                selectedNode.parents.reverse().slice(1).forEach(_.bind(function(parentId) {
                    var node = this.jsTreeInstance.get_node(parentId);
                    this.hideNeighbors(node, 0);
                }, this));
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
            var $target = $(event.target);
            var action = $target.data('action-type');

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
                data.node.children.forEach(_.bind(function(nodeId) {
                    if (!this.jsTreeInstance.is_leaf(nodeId)) {
                        this.jsTreeInstance.close_node(nodeId);
                    }
                }, this));
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
            return node.children.map(_.bind(function(itemId) {
                return this.jsTreeInstance.get_node(itemId);
            }, this));
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

            var parent = this.jsTreeInstance.get_node(node.parent);

            return this.getChildren(parent)
                .filter(_.bind(function(item) {
                    return item.id !== node.id;
                }, this));
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

            this.getNeighbors(node).forEach(_.bind(function(item) {
                this.jsTreeInstance
                    .get_node(item.id, true)
                    .fadeIn(animationDuration);
            }, this));

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
            this.getNeighbors(node).forEach(_.bind(function(neighbor) {
                this.jsTreeInstance
                    .get_node(neighbor.id, true)
                    .fadeOut(animationDuration);
            }, this));

            return node;
        },

        _changeUrlParam: function(param, value) {
            param = this.viewGroup + '[' + param + ']';
            mediator.execute('changeUrlParam', param, value);
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
