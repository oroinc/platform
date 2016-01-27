define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view'
], function($, _, BaseView) {
    'use strict';

    return BaseView.extend({
        relatedCheckboxesSelector: null,

        events: {
            'change': '_onChange'
        },

        requiredOptions: [
            'relatedCheckboxesSelector'
        ],

        initialize: function(options) {
            if (!_.has(options, 'relatedCheckboxesSelector')) {
                throw new Error('Required option "relatedCheckboxesSelector" not found.');
            }

            this.relatedCheckboxesSelector = options.relatedCheckboxesSelector;
        },

        _onChange: function() {
            $(this.relatedCheckboxesSelector).prop('checked', this.$el.is(':checked'));
        }
    });
});
