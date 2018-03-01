define(function(require) {
    'use strict';

    var DateGroupingView;
    var BaseView = require('oroui/js/app/views/base/view');

    DateGroupingView = BaseView.extend({
        autoRender: true,

        switcherCheckbox: '[data-name="field__use-date-group-filter"]',

        fieldSelect: '[data-name="field__field-name"]',

        allowEmptyCheckbox: '[data-name="field__use-skip-empty-periods-filter"]',

        events: {
            'change [data-name="field__use-date-group-filter"]': 'render'
        },

        listen: {
            'page:afterChange mediator [data-name="field__use-date-group-filter"]': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function DateGroupingView() {
            DateGroupingView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            if (this.$(this.switcherCheckbox).is(':checked')) {
                this.$(this.allowEmptyCheckbox).removeAttr('disabled');
                this.$(this.fieldSelect).inputWidget('disable', false);
            } else {
                this.$(this.allowEmptyCheckbox).attr('disabled', true);
                this.$(this.fieldSelect).inputWidget('disable', true);
            }
        }
    });

    return DateGroupingView;
});
