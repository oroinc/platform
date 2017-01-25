define(function(require) {
    'use strict';

    var MoveActionView;
    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    var _ = require('underscore');
    var $ = require('jquery');
    var DialogWidget = require('oro/dialog-widget');
    var routing = require('routing');

    MoveActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            // icon: 'minus-square-o',
            label: _.__('oro.ui.jstree.actions.move')
        }),

        onClick: function(e) {
            var $tree = this.options.$tree;
            var selectedIds = $tree.jstree('get_selected');

            if (selectedIds.length == 0) {
                return;
            }

            var routeParams = {ids: selectedIds.join(",")};

            //ToDo: make valid route with params
            // var routeName = 'some_route_name';

            this.dialogWidget = new DialogWidget({
                title: 'Move To',
                // url: routing.generate(routeName, routeParams),
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

            // this.dialogWidget.once('formSave', _.bind(function(id) {
            //     var $input = this.$(this.inputSelector);
            //     $input.inputWidget('val', id, true);
            //     this.dialogWidget.remove();
            //     this.dialogWidget = null;
            //     $input.inputWidget('focus');
            // }, this));

            this.dialogWidget.render();
        },
        onDialogClose: function() {
            this.$(this.inputSelector).off('.' + this.dialogWidget._wid);
        }
    });

    return MoveActionView;
});
