define(function(require) {
    'use strict';

    const DialogWidget = require('oro/dialog-widget');
    const MassAction = require('./mass-action');
    const mediator = require('oroui/js/mediator');

    /**
     * Edit mass action class.
     *
     * @export  oro/datagrid/action/edit-mass-action
     * @class   oro.datagrid.action.EditMassAction
     * @extends oro.datagrid.action.MassAction
     */
    const EditMassAction = MassAction.extend({
        /** @property {Object} */
        defaultMessages: {
            success: 'Selected items were edited.',
            error: 'Selected items were not edited.',
            empty_selection: 'Please select items to edit.'
        },

        /**
         * @inheritdoc
         */
        constructor: function EditMassAction(options) {
            EditMassAction.__super__.constructor.call(this, options);
        },

        /** @inheritdoc */
        initialize: function(options) {
            EditMassAction.__super__.initialize.call(this, options);
        },

        /** @inheritdoc */
        executeConfiguredAction: function() {
            switch (this.frontend_handle) {
                case 'massedit':
                    this._handleWidget();
                    break;
                default:
                    throw new Error('Invalid mass action type');
            }
        },

        /** @inheritdoc */
        execute: function() {
            this.requestType = 'POST';
            if (this.checkSelectionState()) {
                EditMassAction.__super__.executeConfiguredAction.call(this);
            }
        },

        /** @inheritdoc */
        _handleWidget: function() {
            if (this.dispatched) {
                return;
            }
            this.frontend_options = this.frontend_options || {};
            this.frontend_options.url = this.getLinkWithParameters();
            this.frontend_options.title = this.frontend_options.title || this.label;

            const widget = new DialogWidget(this.frontend_options);
            widget.render();
            widget.once('formSave', data => {
                widget.remove();
                this._showAjaxSuccessMessage(data);
                mediator.trigger('datagrid:doReset:' + this.datagrid.name);
            });
        }
    });

    return EditMassAction;
});
