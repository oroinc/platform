define(function(require) {
    'use strict';

    const AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    const _ = require('underscore');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const DialogWidget = require('oro/dialog-widget');
    const routing = require('routing');
    const messenger = require('oroui/js/messenger');

    const MoveActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'random',
            label: __('oro.ui.jstree.actions.move'),
            routeName: null,
            routeParams: {}
        }),

        /**
         * @inheritdoc
         */
        constructor: function MoveActionView(options) {
            MoveActionView.__super__.constructor.call(this, options);
        },

        onClick: function() {
            const $tree = this.options.$tree;
            const selectedIds = $tree.jstree('get_checked');

            if (!selectedIds.length) {
                messenger.notificationFlashMessage('warning', __('oro.ui.jstree.no_node_selected_error'));
                return;
            }

            let url = false;
            if (this.options.routeName) {
                const routeParams = this.options.routeParams;
                routeParams.selected = selectedIds;
                url = routing.generate(this.options.routeName, routeParams);
            }

            this.dialogWidget = new DialogWidget({
                title: __('oro.ui.jstree.actions.move'),
                url: url,
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 650,
                    minHeight: 100,
                    close: this.onDialogClose.bind(this)
                }
            });

            $tree.data('treeView').moveTriggered = true;

            this.dialogWidget.once('formSave', changed => {
                $.when(_.each(changed, data => {
                    const defer = $.Deferred();
                    $tree.jstree('move_node', data.id, data.parent, data.position);
                    $tree.jstree('uncheck_node', '#' + data.id);

                    return defer.resolve();
                })).done(() => {
                    $tree.data('treeView').moveTriggered = false;
                });
            });

            this.dialogWidget.render();
        },

        onDialogClose: function() {
            this.options.$tree.data('treeView').moveTriggered = false;
        }
    });

    return MoveActionView;
});
