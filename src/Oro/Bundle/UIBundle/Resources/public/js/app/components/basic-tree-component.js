define(function(require) {
    'use strict';

    var BasicTreeComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var tools = require('oroui/js/tools');
    var BaseComponent = require('oroui/js/app/components/base/component');

    require('jquery.jstree');

    /**
     * Options:
     * - data - tree structure in jstree json format
     * - nodeId - identifier of selected node
     *
     * @export oroui/js/app/components/basic-tree-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.BasicTreeComponent
     */
    BasicTreeComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $el: null,

        /**
         * @property {Object}
         */
        $tree: null,

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
        initialization: false,

        /**
         * @property {Boolean}
         */
        checkboxEnabled: false,

        /**
         * @property {Number}
         */
        searchTimeout: 0,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            var nodeList = options.data;
            if (!nodeList) {
                return;
            }

            this.$el = $(options._sourceElement);
            this.$tree = this.$el.find('[data-role="jstree-container"]');

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

            config = this.customizeTreeConfig(options, config);

            this.nodeId = options.nodeId;

            this._deferredInit();
            this.initialization = true;

            this.$tree.jstree(config);
            this.jsTreeInstance = $.jstree.reference(this.$tree);

            this.$tree.one('ready.jstree', _.bind(function() {
                this._resolveDeferredInit();
                this.initialization = false;
                this._fixContainerHeight();

                this.$tree.on('before_open.jstree', _.bind(this.onBeforeOpen, this));
                this.$tree.on('after_open.jstree', _.bind(this.onAfterOpen, this));
                this.$tree.on('after_close.jstree', _.bind(this.onAfterClose, this));
            }, this));

            this.$el.parent().on('keyup', '[data-name="search"]', _.bind(this.onSearch, this));

            if (this.checkboxEnabled) {
                var $checkAll = this.$tree.closest('[data-role="jstree-wrapper"]').find('[data-action-type]');
                $checkAll.on('click', _.bind(this.onCheckAllClick, this));
            }

            if (tools.isMobile()) {
                this.$tree.on('select_node.jstree', _.bind(function(event, data) {
                    var selectedNode = data.node;
                    if (selectedNode) {
                        selectedNode.parents.reverse().slice(1).forEach(_.bind(function(parentId) {
                            var node = this.jsTreeInstance.get_node(parentId);
                            this.hideNeighbors(node, 0);
                        }, this));
                    }
                    this.jsTreeInstance.close_all(selectedNode);
                }, this));
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
                    tie_selection: false
                };

                $('[data-role="jstree-checkall"]').show();
            }

            if (this.$el.find('[data-name="search"]').length) {
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

        onSearch: function(event) {
            var self = this;
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }
            this.searchTimeout = setTimeout(function() {
                var value = $(event.target).val();
                self.$tree.jstree(true).search(value);
            }, 250);
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
        },

        onAfterOpen: function(event, data) {
            if (this.jsTreeInstance.settings.autohideNeighbors) {
                this.hideNeighbors(data.node);
            }
        },

        onAfterClose: function(event, data) {
            if (this.jsTreeInstance.settings.autohideNeighbors) {
                this.showNeighbors(data.node);
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

        /**
         * Fix scrollable container height
         * TODO: This method should be removed during fixing of https://magecore.atlassian.net/browse/BB-336
         *
         */
        _fixContainerHeight: function() {
            var tree = this.$tree.parent();
            if (!tree.hasClass('tree-component')) {
                return;
            }

            var container = tree.parent();
            if (!container.hasClass('tree-component-container')) {
                return;
            }

            var fixHeight = function() {
                var anchor = $('#bottom-anchor').position().top;
                var containerTop = container.position().top;
                var debugBarHeight = $('.sf-toolbar:visible').height() || 0;
                var footerHeight = $('#footer:visible').height() || 0;
                var fixContent = 1;

                tree.height(anchor - containerTop - debugBarHeight - footerHeight + fixContent);
            };

            layout.onPageRendered(fixHeight);
            $(window).on('resize', _.debounce(fixHeight, 50));
            mediator.on('page:afterChange', fixHeight);
            mediator.on('layout:adjustReloaded', fixHeight);
            mediator.on('layout:adjustHeight', fixHeight);

            fixHeight();
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();
            this.$tree.off();
            this.$tree.parent().off();

            delete this.$el;
            delete this.$tree;
            delete this.jsTreeInstance;

            return BasicTreeComponent.__super__.dispose.call(this);
        }
    });

    return BasicTreeComponent;
});
