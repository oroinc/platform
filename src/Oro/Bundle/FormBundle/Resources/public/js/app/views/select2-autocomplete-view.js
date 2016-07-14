define([
    'jquery',
    './select2-view'
], function($, Select2View) {
    'use strict';

    var Select2AutocompleteView;
    Select2AutocompleteView = Select2View.extend({
        events: {
            'change': function(e) {
                var selectedData = $(this.$el).data().selectedData;
                if (e.added) {
                    selectedData = selectedData.push(e.added);
                    $(this).data('selected-data', selectedData);
                }
            }
        }
    });

    return Select2AutocompleteView;
});
