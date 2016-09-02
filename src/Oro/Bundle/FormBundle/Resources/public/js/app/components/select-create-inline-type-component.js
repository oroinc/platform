define(function(require) {
    'use strict';

    var SelectCreateInlineTypeComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var SelectCreateInlineTypeView = require('oroform/js/app/views/select-create-inline-type-view');
    var _ = require('underscore');
    require('jquery.select2');

    SelectCreateInlineTypeComponent = BaseComponent.extend({
        ViewConstructor: SelectCreateInlineTypeView,
        initialize: function(options) {
            SelectCreateInlineTypeComponent.__super__.initialize.apply(this, arguments);
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
