define(function (require) {
    'use strict';
    var Select2AutocompleteComponent,
        $ = require('jquery'),
        Select2Component = require('./select2-component');
    Select2AutocompleteComponent = Select2Component.extend({
        initialize: function (options) {
            Select2AutocompleteComponent.__super__.initialize.call(this, options);
            this.$el.on('select2-init', function(e) {
                $(e.target).on('change', function(e){
                    $(this).data('selected-data', e.added);
                });
            });
        }
    });
    return Select2AutocompleteComponent;
});
