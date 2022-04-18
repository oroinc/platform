define(function(require) {
    'use strict';

    const __ = require('orotranslation/js/translator');
    const Modal = require('oroui/js/modal');
    const contentTemplate = require('tpl-loader!orodatagrid/templates/datagrid/view-name-modal.html');
    const nameErrorTemplate = require('tpl-loader!orodatagrid/templates/datagrid/view-name-error-modal.html');

    const ViewNameModal = Modal.extend({
        contentTemplate: contentTemplate,

        nameErrorTemplate: nameErrorTemplate,

        /**
         * @inheritdoc
         */
        events: {
            'keydown [data-role="grid-view-input"]': 'onKeyDown'
        },

        /**
         * @inheritdoc
         */
        constructor: function ViewNameModal(options) {
            ViewNameModal.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            options = options || {};

            options.title = options.title || __('oro.datagrid.name_modal.title');
            options.content = options.content || this.contentTemplate({
                value: options.defaultValue || '',
                label: __('oro.datagrid.gridView.name'),
                defaultLabel: __('oro.datagrid.action.set_as_default_grid_view'),
                defaultChecked: options.defaultChecked || false
            });
            options.okText = __('oro.datagrid.gridView.save_name');
            options.disposeOnHidden = false;

            ViewNameModal.__super__.initialize.call(this, options);
        },

        onKeyDown: function(e) {
            if (e.which === 13) {
                this.trigger('close');
                this.trigger('ok');
            }
        },

        setNameError: function(error) {
            this.$('.validation-failed').remove();

            if (error) {
                error = this.nameErrorTemplate({
                    error: error
                });
                this.$('[data-role="grid-view-input"]')
                    .addClass('error')
                    .after(error);
            }
        }
    });

    return ViewNameModal;
});
