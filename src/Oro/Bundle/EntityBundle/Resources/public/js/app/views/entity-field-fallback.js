define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');

    /**
     * @export orolocale/js/app/views/fallback-view
     * @extends oroui.app.views.base.View
     * @class oroentity.app.views.EntityFallbackView
     */
    const EntityFallbackView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                useFallbackCheckbox: '.use-fallback-checkbox',
                entityFieldValue: '.entity-field-value',
                fallbackSelect: '.fallback-select',
                fallbackContainer: '.entity-fallback-container'
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function EntityFallbackView(options) {
            EntityFallbackView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.handleUseFallbackCheckbox();
        },

        handleUseFallbackCheckbox: function() {
            const self = this;
            const $checkboxes = $(this.options.selectors.useFallbackCheckbox);

            $checkboxes.each(function() {
                const $checkbox = $(this);
                self.handleCheckboxValue($checkbox);
                $checkbox.on('change', function() {
                    self.handleCheckboxValue($checkbox);
                });
            });
        },

        handleCheckboxValue: function($checkbox) {
            const container = $checkbox.parents(this.options.selectors.fallbackContainer);
            const $fieldValue = container.find(this.options.selectors.entityFieldValue);
            const $fallbackValue = container.find(this.options.selectors.fallbackSelect);

            if ($checkbox.is(':checked')) {
                this.disableElement($fieldValue);
                this.enableElement($fallbackValue);
            } else {
                this.disableElement($fallbackValue);
                this.enableElement($fieldValue);
            }
        },

        disableElement: function($element) {
            $element.attr('disabled', 'disabled');
            $element.parent().addClass('disabled');
        },

        enableElement: function($element) {
            $element.removeAttr('disabled');
            $element.parent().removeClass('disabled');
        }
    });

    return EntityFallbackView;
});
