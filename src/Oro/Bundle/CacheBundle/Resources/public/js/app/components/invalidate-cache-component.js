define(function(require) {
    'use strict';

    var UPSInvalidateCacheComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    UPSInvalidateCacheComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            form: '.invalidate-cache-form',
            removeInvalidationButton: '#remove_scheduled_cache_invalidation_button',
            invalidateNow: '[name="oro_action_operation[invalidateNow]"]',
            invalidateAt: '[name="oro_action_operation[invalidateCacheAt]"]',
            invalidateType: '[name="oro_action_operation[invalidateType]"]'
        },

        /**
         * @inheritDoc
         */
        constructor: function UPSInvalidateCacheComponent() {
            UPSInvalidateCacheComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.invalidateType = $(this.$elem).find(this.options.invalidateType);
            this.invalidateNow = $(this.$elem).find(this.options.invalidateNow);
            this.invalidateAt = $(this.$elem).find(this.options.invalidateAt);
            this.removeInvalidationButton = $(this.options.removeInvalidationButton);
            this.form = $(this.$elem).find(this.options.form);

            $(this.removeInvalidationButton).on('click', _.bind(this.onRemoveInvalidationClick, this));
            $(this.invalidateType).on('change', _.bind(this.onSelectChange, this));
            $(this.invalidateType).trigger('change');

            $(this.invalidateAt).on('change', _.bind(this.toggleRemoveInvalidationVisibility, this));
            $(this.invalidateAt).trigger('change');
        },

        toggleRemoveInvalidationVisibility: function() {
            var value = $(this.invalidateAt).val();
            if (value === '') {
                $(this.removeInvalidationButton).hide();
            } else {
                $(this.removeInvalidationButton).show();
            }
        },

        onRemoveInvalidationClick: function() {
            $(this.invalidateAt).val('');
            $(this.invalidateNow).val('');
            $(this.form).submit();
        },

        onSelectChange: function() {
            var value = $(this.invalidateType).val();
            if (value === 'immediate') {
                $(this.invalidateNow).val(1);
                $(this.$elem).find('tr>td:gt(1)').hide();
                $(this.removeInvalidationButton).hide();
            } else if (value === 'scheduled') {
                $(this.invalidateNow).val('');
                $(this.$elem).find('tr>td:gt(1)').show();
                this.toggleRemoveInvalidationVisibility();
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(this.removeInvalidationButton).off('click');
            $(this.invalidateType).off('change');
            $(this.invalidateAt).off('change');

            UPSInvalidateCacheComponent.__super__.dispose.call(this);
        }
    });

    return UPSInvalidateCacheComponent;
});
