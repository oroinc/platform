define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');

    const CheckboxView = BaseView.extend({
        options: {
            selectors: {
                checkbox: null,
                hiddenInput: null
            }
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

            this.options = _.defaults(options || {}, this.options);
            this.$el.closest('form').on('submit' + this.eventNamespace(), this._onSubmit.bind(this));
        },

        /**
         * @inheritdoc
         */
        _onSubmit: function(e) {
            if (this.$el.find(this.options.selectors.checkbox).is(':checked')) {
                this.$el.find(this.options.selectors.hiddenInput).prop('disabled', true);
            } else {
                this.$el.find(this.options.selectors.hiddenInput).prop('disabled', false);
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.closest('form').off(this.eventNamespace());
            CheckboxView.__super__.dispose.call(this);
        }
    });

    return CheckboxView;
});
