define([
    'jquery',
    'underscore',
    './select2-view'
], function($, _, Select2View) {
    'use strict';

    var Select2AutocompleteView;
    Select2AutocompleteView = Select2View.extend({
        events: {
            change: function(e) {
                if (this.$el.data('select2').opts.multiple) {
                    var selectedData = this.$el.data('selected-data') || [];
                    if (e.added) {
                        selectedData.push(e.added);
                    }
                    if (e.removed) {
                        var index = _.findIndex(selectedData, function(obj) {
                            return obj.id === e.removed.id;
                        });
                        if (index !== -1) {
                            selectedData.splice(index, 1);
                        }
                    }
                } else {
                    this.$el.data('selected-data', e.added);
                }
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function Select2AutocompleteView() {
            Select2AutocompleteView.__super__.constructor.apply(this, arguments);
        }
    });

    return Select2AutocompleteView;
});
