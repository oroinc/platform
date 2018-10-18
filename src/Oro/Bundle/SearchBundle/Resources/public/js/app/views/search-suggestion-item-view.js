define(function(require) {
    'use strict';

    var SearchSuggestionItemView;
    var BaseView = require('oroui/js/app/views/base/view');

    SearchSuggestionItemView = BaseView.extend({
        tagName: 'li',

        template: '<a href="<%-record_url %>" tabindex="-1"><div class="description"><%-record_string %></div>' +
            '<div class="entity-label"><%-entity_label %></div></a>',

        listen: {
            'change:selected model': 'setSelectedClass'
        },

        setSelectedClass: function() {
            this.$el.toggleClass('selected', this.model.get('selected'));
        },

        /**
         * @inheritDoc
         */
        constructor: function SearchSuggestionItemView() {
            SearchSuggestionItemView.__super__.constructor.apply(this, arguments);
        }
    });

    return SearchSuggestionItemView;
});
