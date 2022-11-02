define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const CheckboxView = BaseView.extend({
        options: {
            selectors: {
                checkbox: null,
                hiddenInput: null
            }
        },

        events: {
            'change input[type="checkbox"]': 'onChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function CheckboxView(...args) {
            CheckboxView.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            CheckboxView.__super__.initialize.call(this, options);

            this.options = Object.assign({}, this.options, options);
        },

        onChange() {
            const {checkbox, hiddenInput} = this.options.selectors;

            this.$(hiddenInput).prop('disabled', this.$(checkbox).is(':checked'));
        }
    });

    return CheckboxView;
});
