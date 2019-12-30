define(function(require) {
    'use strict';

    var CheckboxView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    CheckboxView = BaseView.extend({
        options: {
            selectors: {
                checkbox: null,
                hiddenInput: null
            }
        },

        /**
         * @inheritDoc
         */
        constructor: function CheckboxView() {
            CheckboxView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$el.closest('form').on('submit' + this.eventNamespace(), _.bind(this._onSubmit, this));
            CheckboxView.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
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
            CheckboxView.__super__.dispose.apply(this, arguments);
        }
    });

    return CheckboxView;
});
