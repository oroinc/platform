/*global define*/
define([
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/modal'
], function (_, __, Modal) {
    'use strict';

    var ViewNameModal = Modal.extend({
        contentTemplate: _.template(
            '<div class="form-horizontal">' +
                '<div class="control-group">' +
                    '<label class="control-label" for="gridViewName">' + __('oro.datagrid.gridView.name') + ':</label>' +
                    '<div class="controls">' +
                        '<input id="gridViewName" name="name" type="text" value="<%= value %>">' +
                    '</div>' +
                '</div>' +
            '</div>'
        ),

        nameErrorTemplate: _.template(
            '<span for="gridViewName" class="validation-failed"><%= error %></span>'
        ),

        initialize: function(options) {
            options = options || {};

            options.title = options.title || __('oro.datagrid.name_modal.title');
            options.content = options.content || this.contentTemplate({
                value: options.defaultValue || ''
            });
            options.okText =  __('oro.datagrid.gridView.save_name');

            ViewNameModal.__super__.initialize.call(this, options);
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
