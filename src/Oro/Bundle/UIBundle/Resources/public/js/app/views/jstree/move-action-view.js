define(function(require) {
    'use strict';

    var MoveActionView;
    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    var _ = require('underscore');
    var DialogWidget = require('oro/dialog-widget');
    var routing = require('routing');

    MoveActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'random',
            label: _.__('oro.ui.jstree.actions.move'),
            routeName: null,
            routeParams: {}
        }),

        onClick: function() {
            var $tree = this.options.$tree;
            var selectedIds = $tree.jstree('get_checked');

            var url = false;
            if (this.options.routeName) {
                var routeParams = this.options.routeParams;
                routeParams['selected'] = selectedIds;
                url = routing.generate(this.options.routeName, routeParams);
            }

            this.dialogWidget = new DialogWidget({
                title: _.__('oro.ui.jstree.actions.move'),
                url: url,
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 900,
                    height: 400
                }
            });

            this.dialogWidget.once('formSave', _.bind(function(changed) {
                for (var key in changed) {
                    var data = changed[key];
                    $tree.jstree('move_node', data.id, data.parent, data.position);
                }
                $tree.jstree('uncheck_all');
            }, this));

            this.dialogWidget.render();
        }
    });

    return MoveActionView;
});
