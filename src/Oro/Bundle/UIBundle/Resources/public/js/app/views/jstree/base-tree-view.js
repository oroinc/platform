define(function(require) {
    'use strict';

    var BaseTreeView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var tools = require('oroui/js/tools');
    var Chaplin = require('chaplin');

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
            'autoSelectFoundNode'
        ]),

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
            'keypress [data-name="search"]': 'disableSubmit',
            'input [data-name="search"]': 'onSearch',
            'change [data-action-type="checkAll"]': 'onCheckAllClick',
            'click [data-name="clear-search"]': 'clearSearch'
        },

        treeEvents: {
            'after_open.jstree':  'onAfterOpen',
            'before_open.jstree':  'onBeforeOpen',
            'after_close.jstree':  'onAfterClose',
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
        $searchField: null,

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
         * @param {Object} options
         */
        initialize: function(options) {
            BaseTreeView.__super__.initialize.apply(this, arguments);
            var nodeList = options.data;
            if (!nodeList) {
                return;
            }

            this.$tree = this.$el.find('[data-role="jstree-container"]');
            this.$searchField = this.$el.find('[data-name="search"]');
            this.$clearSearchButton = this.$el.find('[data-name="clear-search"]');
            this.$tree.data('treeView', this);
            this.onSearch = _.debounce(this.onSearch, this.searchTimeout);

            var config = {
                'core': {
                    'multiple': false,
                    'data': nodeList,
                    'check_callback': true,
                    'force_text': true
                },
                'state': {
                    'key': options.key,
                    'filter': _.bind(this.onFilter, this)
                },
                'plugins': ['state', 'wholerow']
            };

            this.nodeId = options.nodeId;
            this.jsTreeConfig = this.customizeTreeConfig(options, config);

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

            this.$tree.one('ready.jstree', _.bind(function() {
                this.initialization = false;
                this._resolveDeferredRender();
            }, this));
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

                this.$el.find('[data-role="jstree-checkall"]').show();
            }

            if (this.$searchField.length) {
                config.plugins.push('search');
                config.search = {
                    close_opened_onclear: true,
                    show_only_matches: true,
                    show_only_matches_children: false,
                    case_sensitive: false
                };
            }

            if (_.isUndefined(options.autohideNeighbors)) {
                config.autohideNeighbors = tools.isMobile();
            } else {
                config.autohideNeighbors = options.autohideNeighbors;
            }

            return config;
        },

        disableSubmit: function(e) {
            if (e.keyCode === 13) {
                //enter in search field
                e.preventDefault();
                return false;
            }
        },

        onSearch: function(event) {
            var value = $(event.target).val();
            this.toggleClearSearchButton();
            value = _.trim(value).replace(/\s+/g, ' ');
            if (this.jsTreeInstance.allNodesHidden) {
                this.jsTreeInstance.show_all();
                this.jsTreeInstance.allNodesHidden = false;
            }
            this.jsTreeInstance.searchValue = value;
            this.jsTreeInstance.settings.autohideNeighbors = tools.isMobile() && _.isEmpty(value);
            this.jsTreeInstance.search(value);
        },

        onOpen: function(event, data) {
            this.underlineFilter(data);
        },

        /**
         * Show/Hide clear search field button
         */
        toggleClearSearchButton: function() {
            this.$clearSearchButton.toggleClass('hide', this.$searchField.val() === '');
        },

        /**
         * Clear search field value
         */
        clearSearch: function() {
            this.$searchField.val('');
            this.$searchField.trigger('input');
        },

        /**
         * Search results filter
         *
         * @param {Object} event
         * @param {Object} data
         */
        searchResultsFilter: function(event, data) {
            if (data.res.length) {
                this.underlineFilter(data);
                if (this.autoSelectFoundNode && data.res.length === 1) {
                    this.$el.find('a.jstree-search').click();
                    this.$searchField.focus();
                }
            } else {
                this.showSearchResultMessage(_.__('oro.ui.jstree.search.search_no_found'));
            }
        },

        /**
         * Underline matches substrings
         *
         * @param {Object} data
         */
        underlineFilter: _.debounce(function(data) {
            if (!data || !data.instance) {
                return;
            }
            var pattern = new RegExp(data.instance.searchValue, 'gi');
            $('.jstree-search', this.$el).each(function(index, item) {
                var $item = $(item);
                var sourceText = $item.text().replace(pattern, '<span class="matched-keyword">$&</span>');
                $item.contents().filter(function(index, node) {
                    return node.nodeName === '#text' || node.className === 'matched-keyword';
                }).remove();

                $item.append(sourceText);
            });
        }),

        /**
         * Show search result message
         *
         * @param {string} message
         */
        showSearchResultMessage: function(message) {
            if (_.isUndefined(message)) {
                message = '';
            }
            this.jsTreeInstance.hide_all();
            this.jsTreeInstance.allNodesHidden = true;
            this.$tree.append(
                $('<div />', {
                    'class': 'search-no-results',
                    'text': message
                })
            );
        },

        onSelect: function(event, data) {
            if (!tools.isMobile()) {
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
            this.underlineFilter.apply(data);
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

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$tree.off();
            this.$tree.parent().off();

            delete this.$tree;
            delete this.$searchField;
            delete this.$clearSearchButton;
            delete this.jsTreeInstance;
            delete this.jsTreeConfig;

            return BaseTreeView.__super__.dispose.call(this);
        }
    });

    return BaseTreeView;
});
