define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var ButtonManager = require('oroaction/js/button-manager');
    var tools = require('oroui/js/tools');
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
        constructor: function ButtonComponent() {
            ButtonComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ButtonComponent.__super__.initialize.apply(this, arguments);

            this.options = options || {};

            this.$button = $(this.options._sourceElement);
            this.$button
                .on('click', _.bind(this.onClick, this));

            var buttonOptions = this.$button.data('options') || {};
            if (buttonOptions.confirmation && buttonOptions.confirmation.component) {
                this._deferredInit();
                tools.loadModules(buttonOptions.confirmation.component)
                    .then(this._resolveDeferredInit.bind(this));
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            var $target = $(e.currentTarget);
            $target.trigger('tohide.bs.dropdown');
            this._getButtonManager($target).execute(e);

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
