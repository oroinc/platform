define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const UPSInvalidateCacheComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function UPSInvalidateCacheComponent(options) {
            UPSInvalidateCacheComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.$elem = options._sourceElement;

            this.invalidateType = $(this.$elem).find(this.options.invalidateType);
            this.invalidateNow = $(this.$elem).find(this.options.invalidateNow);
            this.invalidateAt = $(this.$elem).find(this.options.invalidateAt);
            this.removeInvalidationButton = $(this.options.removeInvalidationButton);
            this.form = $(this.$elem).find(this.options.form);

            $(this.removeInvalidationButton).on('click', this.onRemoveInvalidationClick.bind(this));
            $(this.invalidateType).on('change', this.onSelectChange.bind(this));
            $(this.invalidateType).trigger('change');

            $(this.invalidateAt).on('change', this.toggleRemoveInvalidationVisibility.bind(this));
            $(this.invalidateAt).trigger('change');
        },

        toggleRemoveInvalidationVisibility: function() {
            const value = $(this.invalidateAt).val();
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
            const value = $(this.invalidateType).val();
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
