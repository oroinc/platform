define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var ButtonManager = require('oroaction/js/button-manager');
    var _ = require('underscore');
    var $ = require('jquery');

    var ButtonComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery.Element}
         */
        $button: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ButtonComponent.__super__.initialize.apply(this, arguments);

            this.options = options || {};

            this.$button = $(this.options._sourceElement);
            this.$button
                .on('click', _.bind(this.onClick, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            this._getButtonManager($(e.currentTarget)).execute(e);

            return false;
        },

        /**
         * @param {jQuery.Element} $element
         * @returns {ButtonManager}
         * @private
         */
        _getButtonManager: function($element) {
            if (!$element.data('button-manager')) {
                var options = $element.data('options') || {};

                $element.data('button-manager', new ButtonManager(options));
            }

            return $element.data('button-manager');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$button.off();

            ButtonComponent.__super__.dispose.call(this);
        }
    });

    return ButtonComponent;
});
