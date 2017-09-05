define(function(require) {
    'use strict';

    var ConfigurationTreeView;
    var _ = require('underscore');
    var $ = require('jquery');
    var routing = require('routing');
    var BaseTreeView = require('oroui/js/app/views/jstree/base-tree-view');

    ConfigurationTreeView = BaseTreeView.extend({
        /**
         * Triggers after node selection in tree
         *
         * @param {Event} e
         * @param {Object} selected
         */
        onSelect: function(e, selected) {
            if (this.initialization) {
                return;
            }
            if (selected.node.children.length) {
                return this._toggleParentNode(selected);
            }

            var parent = _.last(_.without(selected.node.parents, '#'));
            var routeParams = _.extend({}, this.onSelectRouteParameters, {
                activeGroup: parent + '/' + selected.node.id
            });

            var url = routing.generate(this.onSelectRoute, routeParams);
            //simulate click on real link to check page state
            var $link = $('<a>').attr('href', url);
            this.$tree.before($link);
            $link.click().remove();
        },

        _toggleParentNode: function(selected) {
            this.jsTreeInstance.toggle_node(selected.node);
            this.jsTreeInstance.deselect_node(selected.selected);
        }
    });

    return ConfigurationTreeView;
});
