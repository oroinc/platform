define(function(require) {
    'use strict';

    const SelectCreateInlineTypeAsyncView = require('oroform/js/app/views/select-create-inline-type-async-view');
    const SelectCreateInlineTypeComponent = require('oroform/js/app/components/select-create-inline-type-component');

    const SelectCreateInlineTypeAsyncComponent = SelectCreateInlineTypeComponent.extend({
        ViewConstructor: SelectCreateInlineTypeAsyncView,

        /**
         * @inheritdoc
         */
        constructor: function SelectCreateInlineTypeAsyncComponent(options) {
            SelectCreateInlineTypeAsyncComponent.__super__.constructor.call(this, options);
        }
    });

    return SelectCreateInlineTypeAsyncComponent;
});
