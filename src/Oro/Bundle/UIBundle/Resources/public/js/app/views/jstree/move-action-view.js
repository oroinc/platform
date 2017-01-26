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

        onClick: function(e) {
            var $tree = this.options.$tree;
            var selectedIds = $tree.jstree('get_selected');

            var url = false;
            if (this.options.routeName) {
                url = routing.generate(this.options.routeName, this.options.routeParams);
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
                    height: 400,
                    close: _.bind(this.onDialogClose, this)
                }
            });

            this.dialogWidget.render();
        },

        onDialogClose: function() {
            this.$(this.inputSelector).off('.' + this.dialogWidget._wid);
        }
    });

    return MoveActionView;
});
