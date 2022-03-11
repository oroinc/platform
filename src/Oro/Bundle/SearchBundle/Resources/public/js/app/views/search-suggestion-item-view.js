define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const SearchSuggestionItemView = BaseView.extend({
        tagName: 'li',

        template: '<a href="<%-record_url %>" tabindex="-1">' +
            '<div class="description"><%-selected_data[\'name\'] %></div>' +
            '<div class="entity-label"><%-entity_label %></div></a>',

        listen: {
            'change:selected model': 'setSelectedClass'
        },

        setSelectedClass: function() {
            this.$el.toggleClass('selected', this.model.get('selected'));
        },

        /**
         * @inheritdoc
         */
        constructor: function SearchSuggestionItemView(options) {
            SearchSuggestionItemView.__super__.constructor.call(this, options);
        }
    });

    return SearchSuggestionItemView;
});
