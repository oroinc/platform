define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const DateGroupingView = BaseView.extend({
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
         * @inheritdoc
         */
        constructor: function DateGroupingView(options) {
            DateGroupingView.__super__.constructor.call(this, options);
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
