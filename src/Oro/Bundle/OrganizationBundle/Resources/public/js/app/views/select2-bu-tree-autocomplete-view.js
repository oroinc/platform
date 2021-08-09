define(function(require) {
    'use strict';

    const Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');

    const Select2BuTreeAutocompleteView = Select2AutocompleteView.extend({
        /**
         * @inheritdoc
         */
        constructor: function Select2BuTreeAutocompleteView(options) {
            Select2BuTreeAutocompleteView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.$el.on('input-widget:init', this.setPlaceholder.bind(this));
            this.$el.on('select2-blur', this.setPlaceholder.bind(this));
            Select2BuTreeAutocompleteView.__super__.initialize.call(this, options);
        },

        setPlaceholder: function() {
            const select2 = this.$el.data('select2');
            const placeholder = select2.getPlaceholder();

            if (typeof placeholder !== 'undefined' &&
                !select2.opened() &&
                select2.search.val().length === 0
            ) {
                select2.search.val(placeholder).addClass('select2-default');
            }
        }
    });

    return Select2BuTreeAutocompleteView;
});
