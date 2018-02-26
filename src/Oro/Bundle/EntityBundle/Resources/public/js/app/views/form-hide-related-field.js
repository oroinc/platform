define(function(require) {
    'use strict';

    var HideRelatedFieldView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    HideRelatedFieldView = BaseView.extend({

        /**
         * @property {Object}
         */
        options: {
            trackedFields: '',
            hideField: ''
        },
        /**
         * @inheritDoc
         */
        constructor: function HideRelatedFieldView() {
            HideRelatedFieldView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$trackedFieldScalarValue = $('[name="' + this.options.trackedFields + '[scalarValue]"]');
            this.$trackedFieldUseFallback = $('[name="' + this.options.trackedFields + '[useFallback]"]');
            this.$hideField = $('[name="' + this.options.hideField + '"]').closest('.control-group');

            this.$trackedFieldScalarValue.on('change', $.proxy(this.updateVisibility, this));
            this.$trackedFieldUseFallback.on('change', $.proxy(this.updateVisibility, this));

            this.updateVisibility();
        },

        updateVisibility: function() {
            var scalarValue = this.$trackedFieldScalarValue.val() === '1';
            var useFallback = this.$trackedFieldUseFallback.is(':checked');

            if (!useFallback && scalarValue) {
                this.$hideField.show();
            } else {
                this.$hideField.hide();
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$trackedFieldScalarValue.off('change', this.updateVisibility);
            this.$trackedFieldUseFallback.off('change', this.updateVisibility);
        }
    });

    return HideRelatedFieldView;
});
