define([
    'jquery',
    'underscore',
    './select2-view'
], function($, _, Select2View) {
    'use strict';

    const Select2AutocompleteView = Select2View.extend({
        events: {
            change: function(e) {
                if (this.$el.data('select2').opts.multiple) {
                    const selectedData = this.$el.data('selected-data') || [];
                    if (e.added) {
                        selectedData.push(e.added);
                    }
                    if (e.removed) {
                        const index = _.findIndex(selectedData, function(obj) {
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
         * @inheritdoc
         */
        constructor: function Select2AutocompleteView(options) {
            Select2AutocompleteView.__super__.constructor.call(this, options);
        }
    });

    return Select2AutocompleteView;
});
