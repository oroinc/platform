define(function(require) {
    'use strict';

    var Select2ShareComponent;
    var _ = require('underscore');
    var Select2Component = require('oro/select2-component');
    var mediator = require('oroui/js/mediator');

    Select2ShareComponent = Select2Component.extend({
        select2Selector: '.select2.select2-offscreen',

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            Select2ShareComponent.__super__.initialize.call(this, options);

            $(this.select2Selector).on('select2-selecting', function (e) {
                e.stopPropagation();
                mediator.trigger('datagrid:share-grid:add:data-from-select2', e.object);
                $(e.currentTarget).select2('close');
                return false;
            });
        }
    });
    return Select2ShareComponent;
});
