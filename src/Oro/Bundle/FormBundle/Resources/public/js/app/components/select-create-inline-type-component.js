define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const SelectCreateInlineTypeView = require('oroform/js/app/views/select-create-inline-type-view');
    const _ = require('underscore');
    require('jquery.select2');

    const SelectCreateInlineTypeComponent = BaseComponent.extend({
        ViewConstructor: SelectCreateInlineTypeView,

        /**
         * @inheritdoc
         */
        constructor: function SelectCreateInlineTypeComponent(options) {
            SelectCreateInlineTypeComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            SelectCreateInlineTypeComponent.__super__.initialize.call(this, options);
            this.view = new this.ViewConstructor(_.extend({
                el: options._sourceElement
            }, _.pick(options, 'urlParts', 'entityLabel', 'existingEntityGridId', 'inputSelector')));
        },
        getUrlParts: function() {
            return this.view.getUrlParts();
        },
        setUrlParts: function(newParts) {
            this.view.setUrlParts(newParts);
        },
        setSelection: function(value) {
            this.view.setSelection(value);
        },
        getSelection: function() {
            return this.view.getSelection();
        }
    });

    return SelectCreateInlineTypeComponent;
});
