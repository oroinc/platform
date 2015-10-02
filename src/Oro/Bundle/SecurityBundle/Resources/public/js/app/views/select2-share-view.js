define(function(require) {
    'use strict';

    var Select2ShareView;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery.select2');

    Select2ShareView = BaseView.extend({
        select2Selector: '.select2.select2-offscreen',

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = options;

            $(this.select2Selector).on('select2-selecting', function(e) {
                e.stopPropagation();
                mediator.trigger('datagrid:shared-datagrid:add:data-from-select2', e.object);
                $(e.currentTarget).select2('close');

                return false;
            });
        }
    });

    return Select2ShareView;
});
