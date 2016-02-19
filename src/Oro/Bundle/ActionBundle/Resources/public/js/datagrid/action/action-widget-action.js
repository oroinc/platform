/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var ModelAction = require('oro/datagrid/action/model-action');
    var ActionManager = require('oroaction/js/action-manager');
    var routing = require('routing');

    var ActionWidgetAction = ModelAction.extend({

        /**
         * @property {Object}
         */
        options: {
            datagrid: null,
            confirmation: null,
            showDialog: null,
            executionRoute: null,
            dialogRoute: null,
            dialogOptions: {
                title: 'Action',
                allowMaximize: false,
                allowMinimize: false,
                modal: true,
                resizable: false,
                maximizedHeightDecreaseBy: 'minimize-bar',
                width: 550
            }
        },

        /**
         * @property {ActionManager}
         */
        actionManager: null,

        /**
         * @inheritDoc
         */
        initialize: function() {
            ActionWidgetAction.__super__.initialize.apply(this, arguments);

            var routeParams = this._getRouteParams();

            var options = {
                showDialog: this.options.showDialog,
                dialogUrl: routing.generate(this.options.dialogRoute, routeParams),
                dialogOptions: this.options.dialogOptions,
                url: routing.generate(this.options.executionRoute, routeParams),
                confirmation: Boolean(this.options.confirmation),
                messages: {
                    confirm_content: this.options.confirmation
                }
            };

            this.actionManager = new ActionManager(options);
        },

        /**
         * @inheritdoc
         */
        run: function() {
            this.actionManager.execute();
        },

        /**
         * @return {Object}
         */
        _getRouteParams: function() {
            var entityId = this.model[this.model.idAttribute];

            return {
                'actionName': this.options.actionName,
                'entityId': entityId,
                'entityClass': this.options.entityClass,
                'datagrid': this.options.datagrid
            };
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.actionManager;

            ActionWidgetAction.__super__.dispose.call(this);
        }
    });

    return ActionWidgetAction;
});
