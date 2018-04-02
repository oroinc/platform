define(function(require) {
    'use strict';

    var BaseTreeManageView;
    var $ = require('jquery');
    var _ = require('underscore');
    var widgetManager = require('oroui/js/widget-manager');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var BaseTreeView = require('oroui/js/app/views/jstree/base-tree-view');

    BaseTreeManageView = BaseTreeView.extend({
        treeEvents: {
            'move_node.jstree': 'onMove',
            'select-subtree-item:change': 'onSelectSubtreeChange'
        },

        /**
         * @property {Boolean}
         */
        updateAllowed: false,

        /**
         * @property {Boolean}
         */
        moveTriggered: false,

        /**
         * @property {String}
         */
        reloadWidget: '',

        /**
         * @property {String}
         */
        onMoveRoute: '',

        /**
         * @property {Boolean}
         */
        checkboxEnabled: true,

        /**
         * @inheritDoc
         */
        constructor: function BaseTreeManageView() {
            BaseTreeManageView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            BaseTreeManageView.__super__.initialize.call(this, options);

            if (!this.$tree) {
                return;
            }

            this.updateAllowed = options.updateAllowed;
            this.reloadWidget = options.reloadWidget;
            this.onMoveRoute = options.onMoveRoute;
        },

        /**
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
            BaseTreeManageView.__super__.customizeTreeConfig.call(this, options, config);
            if (options.updateAllowed) {
                config.plugins.push('dnd');
                config.dnd = {
                    copy: false
                };
            }
            return config;
        },

        /**
         * Triggers after switching selectSubTree
         *
         * @param {Object} event
         * @param {Object} data
         */
        onSelectSubtreeChange: function(event, data) {
            this.jsTreeConfig.checkbox.cascade = data.selectSubTree ? 'up+down+undetermined' : 'undetermined';
            this.render();
        },

        /**
         * Triggers after node selection in tree
         *
         * @param {Event} e
         * @param {Object} selected
         */
        onSelect: function(e, selected) {
            BaseTreeManageView.__super__.onSelect.apply(this, arguments);

            if (this.initialization || !this.updateAllowed) {
                return;
            }
            var url;
            if (this.onRootSelectRoute && selected.node.parent === '#') {
                url = routing.generate(this.onRootSelectRoute, {id: selected.node.id});
            } else {
                url = routing.generate(this.onSelectRoute, {id: selected.node.id});
            }
            mediator.execute('redirectTo', {url: url});
        },

        /**
         * Triggers after node move
         *
         * @param {Object} e
         * @param {Object} data
         */
        onMove: function(e, data) {
            if (this.moveTriggered) {
                return;
            }

            var self = this;
            $.ajax({
                async: false,
                type: 'PUT',
                url: routing.generate(self.onMoveRoute),
                data: {
                    id: data.node.id,
                    parent: data.parent,
                    position: data.position
                },
                success: function(result) {
                    if (!result.status) {
                        self.rollback(data);
                        messenger.notificationFlashMessage(
                            'error',
                            _.__('oro.ui.jstree.move_node_error', {nodeText: data.node.text})
                        );
                    } else if (self.reloadWidget) {
                        widgetManager.getWidgetInstanceByAlias(self.reloadWidget, function(widget) {
                            widget.render();
                        });
                    }
                }
            });
        },

        /**
         * Rollback node move
         *
         * @param {Object} data
         */
        rollback: function(data) {
            if (data.old_position > data.position) {
                data.old_position++;
            }
            this.moveTriggered = true;
            this.$tree.jstree('move_node', data.node, data.old_parent, data.old_position);
            this.moveTriggered = false;
        },

        /**
         * Off events
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            BaseTreeManageView.__super__.dispose.call(this);
        }
    });

    return BaseTreeManageView;
});
