define(function(require) {
    'use strict';

    var SelectCreateInlineTypeAsyncComponent;
    var SelectCreateInlineTypeAsyncView = require('oroform/js/app/views/select-create-inline-type-async-view');
    var SelectCreateInlineTypeComponent = require('oroform/js/app/components/select-create-inline-type-component');

    SelectCreateInlineTypeAsyncComponent = SelectCreateInlineTypeComponent.extend({
        ViewConstructor: SelectCreateInlineTypeAsyncView,

        /**
         * @inheritDoc
         */
        constructor: function SelectCreateInlineTypeAsyncComponent() {
            SelectCreateInlineTypeAsyncComponent.__super__.constructor.apply(this, arguments);
        }
    });

    return SelectCreateInlineTypeAsyncComponent;
});
