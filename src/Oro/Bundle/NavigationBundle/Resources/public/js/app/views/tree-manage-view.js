define(function(require) {
    'use strict';

    var TreeManageView;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var widgetManager = require('oroui/js/widget-manager');
    var messenger = require('oroui/js/messenger');
    var routing = require('routing');
    var BaseTreeManageView = require('oroui/js/app/views/jstree/base-tree-manage-view');

    TreeManageView = BaseTreeManageView.extend({
        /**
         * @property {String}
         */
        menu: '',

        /**
         * @property {Number}
         */
        scopeId: 0,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            TreeManageView.__super__.initialize.call(this, options);
            this.menu = options.menu;
            this.scopeId = options.scopeId;
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
            var route;
            var routeParams;
            if (this.onRootSelectRoute && selected.node.parent === '#') {
                route = this.onRootSelectRoute;
                routeParams = {menuName: this.menu, scopeId: this.scopeId};
            } else {
                route = this.onSelectRoute;
                routeParams = {
                    scopeId: this.scopeId,
                    menuName: this.menu,
                    key: selected.node.id
                };
            }
            mediator.execute('redirectTo', {url: routing.generate(route, routeParams)});
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
                url: routing.generate(self.onMoveRoute, {
                    scopeId: this.scopeId,
                    menuName: this.menu
                }),
                data: {
                    key: data.node.id,
                    parentKey: data.parent,
                    position: data.position
                },
                success: function() {
                    if (self.reloadWidget) {
                        widgetManager.getWidgetInstanceByAlias(self.reloadWidget, function(widget) {
                            widget.render();
                        });
                    }
                    var message = _.__('oro.navigation.menuupdate.moved_success_message', {nodeText: data.node.text});
                    messenger.notificationFlashMessage('success', message);
                },
                errorHandlerMessage: false,
                error: function(xhr) {
                    self.rollback(data);
                    var message = _.__('oro.ui.jstree.move_node_error', {nodeText: data.node.text});
                    if (xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    messenger.notificationFlashMessage('error', message);
                }
            });
        }
    });

    return TreeManageView;
});
