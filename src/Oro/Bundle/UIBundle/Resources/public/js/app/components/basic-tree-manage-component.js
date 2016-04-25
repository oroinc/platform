define(function(require) {
    'use strict';

    var BasicTreeManageComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var widgetManager = require('oroui/js/widget-manager');
    var mediator = require('oroui/js/mediator');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var BasicTreeComponent = require('oroui/js/app/components/basic-tree-component');

    /**
     * @export oroui/js/app/components/basic-tree-manage-component
     * @extends oroui.app.components.BasicTreeComponent
     * @class oroui.app.components.BasicTreeManageComponent
     */
    BasicTreeManageComponent = BasicTreeComponent.extend({
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
        onSelectRoute: '',

        /**
         * @property {String}
         */
        onRootSelectRoute: '',

        /**
         * @property {String}
         */
        onMoveRoute: '',

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            BasicTreeManageComponent.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            this.updateAllowed = options.updateAllowed;
            this.reloadWidget = options.reloadWidget;
            this.onSelectRoute = options.onSelectRoute;
            this.onMoveRoute = options.onMoveRoute;
            this.onRootSelectRoute = options.onRootSelectRoute;

            this.$tree.on('select_node.jstree', _.bind(this.onSelect, this));
            this.$tree.on('move_node.jstree', _.bind(this.onMove, this));
        },

        /**
         * @param {Object} options
         * @param {Object} config
         * @returns {Object}
         */
        customizeTreeConfig: function(options, config) {
            if (options.updateAllowed) {
                config.plugins.push('dnd');
                config.dnd = {
                    'copy': false
                };
            }

            return config;
        },

        /**
         * Triggers after node selection in tree
         *
         * @param {Event} e
         * @param {Object} selected
         */
        onSelect: function(e, selected) {
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
                            __('oro.ui.jstree.move_node_error', {nodeText: data.node.text})
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
            this.$tree
                .off('select_node.jstree')
                .off('move_node.jstree');

            BasicTreeManageComponent.__super__.dispose.call(this);
        }
    });

    return BasicTreeManageComponent;
});
