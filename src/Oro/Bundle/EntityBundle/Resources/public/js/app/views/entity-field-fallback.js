define(function(require) {
    'use strict';

    var EntityFallbackView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orolocale/js/app/views/fallback-view
     * @extends oroui.app.views.base.View
     * @class orolocale.app.views.FallbackView
     */
    EntityFallbackView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                useFallbackCheckbox: '.use-fallback-checkbox',
                entityFieldValue: '.entity-field-value',
                fallbackSelect: '.fallback-select'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var self = this;
            this.initLayout().done(function() {
                self.handleLayoutInit();
            });
        },

        handleLayoutInit: function() {
            this.handleUseFallbackCheckbox();
        },

        handleUseFallbackCheckbox: function() {
            var self = this;
            var $checkbox = $(this.options.selectors.useFallbackCheckbox);
            var $fieldValue = $(this.options.selectors.entityFieldValue);
            var $fallbackValue = $(this.options.selectors.fallbackSelect);

            this.handleCheckboxValue($checkbox, $fieldValue, $fallbackValue);
            $checkbox.on('click', function() {
                self.handleCheckboxValue($checkbox, $fieldValue, $fallbackValue);
            });
        },

        handleCheckboxValue: function($checkbox, $fieldValue, $fallbackValue) {
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
