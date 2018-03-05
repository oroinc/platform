define(function(require) {
    'use strict';

    var _ = require('underscore');
    var DialogWidget = require('oro/dialog-widget');
    var MassAction = require('./mass-action');
    var EditMassAction;
    var mediator = require('oroui/js/mediator');

    /**
     * Edit mass action class.
     *
     * @export  oro/datagrid/action/edit-mass-action
     * @class   oro.datagrid.action.EditMassAction
     * @extends oro.datagrid.action.MassAction
     */
    EditMassAction = MassAction.extend({
        /** @property {Object} */
        defaultMessages: {
            success: 'Selected items were edited.',
            error: 'Selected items were not edited.',
            empty_selection: 'Please select items to edit.'
        },

        /**
         * @inheritDoc
         */
        constructor: function EditMassAction() {
            EditMassAction.__super__.constructor.apply(this, arguments);
        },

        /** @inheritdoc */
        initialize: function(options) {
            EditMassAction.__super__.initialize.apply(this, arguments);
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

            var widget = new DialogWidget(this.frontend_options);
            widget.render();
            widget.once('formSave', _.bind(function(data) {
                widget.remove();
                this._showAjaxSuccessMessage(data);
                mediator.trigger('datagrid:doReset:' + this.datagrid.name);
            }, this));
        }
    });

    return EditMassAction;
});
