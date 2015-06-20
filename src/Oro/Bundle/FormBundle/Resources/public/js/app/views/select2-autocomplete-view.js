define([
    'jquery',
    './select2-view'
], function ($, Select2View) {
    'use strict';

    var Select2AutocompleteView;
    Select2AutocompleteView = Select2View.extend({
        events: {
            'change': function(e){
                $(this).data('selected-data', e.added);
            }
        }
    });

    return Select2AutocompleteView;
});
