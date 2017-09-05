define(function(require) {
    'use strict';

    var ConfigurationTreeView;
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var BaseTreeView = require('oroui/js/app/views/jstree/base-tree-view');

    ConfigurationTreeView = BaseTreeView.extend({
        autoSelectFoundNode: true,

        isNodeHasHandler: function(node) {
            return node.children.length === 0;
        },

        redirect: function(node) {
            var parent = _.last(_.without(node.parents, '#'));
            var routeParams = _.extend({}, this.onSelectRouteParameters, {
                activeGroup: parent + '/' + node.id
            });

            var url = routing.generate(this.onSelectRoute, routeParams);
            //simulate click on real link to check page state
            var $link = $('<a>').attr('href', url);
            this.$tree.before($link);
            $link.click().remove();
        },

        onSelect: function(e, selected) {
            if (this.initialization) {
                return;
            } else if (!this.isNodeHasHandler(selected.node)) {
                return this._toggleParentNode(selected);
            }

            return this.redirect(selected.node);
        },

        _toggleParentNode: function(selected) {
            this.jsTreeInstance.toggle_node(selected.node);
            this.jsTreeInstance.deselect_node(selected.selected);
        }
    });

    return ConfigurationTreeView;
});
