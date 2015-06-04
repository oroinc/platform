/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/modal'
], function (_, __, Modal) {
    'use strict';

    var ViewNameModal = Modal.extend({
        contentTemplate: null,

        nameErrorTemplate: null,

        initialize: function(options) {
            options = options || {};

            this.contentTemplate = _.template($('#template-datagrid-view-name-modal').html());
            this.nameErrorTemplate = _.template($('#template-datagrid-view-name-error-modal').html());

            options.title = options.title || __('oro.datagrid.name_modal.title');
            options.content = options.content || this.contentTemplate({
                value: options.defaultValue || '',
                label: __('oro.datagrid.gridView.name')
            });
            options.okText =  __('oro.datagrid.gridView.save_name');

            ViewNameModal.__super__.initialize.call(this, options);
        },

        open: function(cb) {
            ViewNameModal.__super__.open.call(this, cb);

            var self = this;

            $("#gridViewName").one('keydown', function(e) {
                if (e.which == 13) {
                    self.trigger('close');
                    self.trigger('ok');
                }
            });
        },

        setNameError: function(error) {
            this.$('.validation-failed').remove();
            if (error) {
                var error = this.nameErrorTemplate({
                    error: error
                });
                this.$('#gridViewName').after(error);
            }
        }
    });

    return ViewNameModal;
});
