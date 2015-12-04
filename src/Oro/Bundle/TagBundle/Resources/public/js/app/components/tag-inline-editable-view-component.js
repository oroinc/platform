define(function(require) {
    'use strict';

    var TagInlineEditableViewComponent;
    var InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');

    TagInlineEditableViewComponent = InlineEditableViewComponent.extend({

        /**
         * Resizes editor to cell width
         */
        resizeTo: function(view, cell) {
            view.$el.css({
                width: cell.$el.parent().outerWidth()
            });
        }
    });

    return TagInlineEditableViewComponent;
});
