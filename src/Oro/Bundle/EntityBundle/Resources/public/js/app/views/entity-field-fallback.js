define(function(require) {
    'use strict';

    var EntityFallbackView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    /**
     * @export orolocale/js/app/views/fallback-view
     * @extends oroui.app.views.base.View
     * @class oroentity.app.views.EntityFallbackView
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
        constructor: function EntityFallbackView() {
            EntityFallbackView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.handleUseFallbackCheckbox();
        },

        handleUseFallbackCheckbox: function() {
            var self = this;
            var $checkboxes = $(this.options.selectors.useFallbackCheckbox);

            $checkboxes.each(function() {
                var $checkbox = $(this);
                self.handleCheckboxValue($checkbox);
                $checkbox.on('click', function() {
                    self.handleCheckboxValue($checkbox);
                });
            });
        },

        handleCheckboxValue: function($checkbox) {
            var $fieldValue = $checkbox.parents('table').find(this.options.selectors.entityFieldValue);
            var $fallbackValue = $checkbox.parents('table').find(this.options.selectors.fallbackSelect);
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
