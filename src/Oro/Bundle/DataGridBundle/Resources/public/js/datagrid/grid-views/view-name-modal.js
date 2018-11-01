define(function(require) {
    'use strict';

    var __ = require('orotranslation/js/translator');
    var Modal = require('oroui/js/modal');
    var contentTemplate = require('tpl!orodatagrid/templates/datagrid/view-name-modal.html');
    var nameErrorTemplate = require('tpl!orodatagrid/templates/datagrid/view-name-error-modal.html');

    var ViewNameModal = Modal.extend({
        contentTemplate: contentTemplate,

        nameErrorTemplate: nameErrorTemplate,

        /**
         * @inheritDoc
         */
        events: {
            'keydown [data-role="grid-view-input"]': 'onKeyDown'
        },

        /**
         * @inheritDoc
         */
        constructor: function ViewNameModal() {
            ViewNameModal.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
                this.$('[data-role="grid-view-input"]').after(error);
            }
        }
    });

    return ViewNameModal;
});
