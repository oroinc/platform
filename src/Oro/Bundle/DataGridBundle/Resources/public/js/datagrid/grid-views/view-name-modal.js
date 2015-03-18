/*global define*/
define([
    'orotranslation/js/translator',
    'oroui/js/modal'
], function (__, Modal) {
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

         initialize: function(options) {
             options = options || {};

             options.title = options.title || __('oro.datagrid.name_modal.title');
             options.content = options.content || this.contentTemplate({
                 value: options.defaultValue || ''
             });

             ViewNameModal.__super__.initialize.call(this, options);
         }
    });

    return ViewNameModal;
});
