define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');

    var Select2BuTreeAutocompleteView = Select2AutocompleteView.extend({

        initialize: function() {
            this.$el.on('input-widget:init', _.bind(this.setPlaceholder, this));
            this.$el.on('select2-blur', _.bind(this.setPlaceholder, this));
            Select2BuTreeAutocompleteView.__super__.initialize.apply(this, arguments);
        },

        setPlaceholder: function() {
            var select2 = this.$el.data('select2');
            var placeholder = select2.getPlaceholder();

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
