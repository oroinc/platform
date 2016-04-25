define(function(require) {
    'use strict';

    var BasicTreeComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var BaseComponent = require('oroui/js/app/components/base/component');

    require('jstree/jquery.jstree.min');

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
        $tree: null,

        /**
         * @property {Number}
         */
        nodeId: null,

        /**
         * @property {Boolean}
         */
        initialization: false,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            var nodeList = options.data;
            if (!nodeList) {
                return;
            }

            this.$tree = $(options._sourceElement);

            var config = {
                'core': {
                    'multiple': false,
                    'data': nodeList,
                    'check_callback': true
                },
                'state': {
                    'key': options.key,
                    'filter': _.bind(this.onFilter, this)
                },
                'plugins': ['state']
            };
            config = this.customizeTreeConfig(options, config);

            this.nodeId = options.nodeId;

            this._deferredInit();
            this.initialization = true;

            this.$tree.jstree(config);

            var self = this;
            this.$tree.on('ready.jstree', function() {
                self._resolveDeferredInit();
                self.initialization = false;
            });

            self._fixContainerHeight();
        },

        /**
         * Customize jstree config to add plugins, callbacks etc.
         *
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
            return config;
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
        }
    });

    return BasicTreeComponent;
});
