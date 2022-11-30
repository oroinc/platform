define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const ButtonManager = require('oroaction/js/button-manager');
    const loadModules = require('oroui/js/app/services/load-modules');
    const _ = require('underscore');
    const $ = require('jquery');

    const ButtonComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery.Element}
         */
        $button: null,

        /**
         * @inheritdoc
         */
        constructor: function ButtonComponent(options) {
            ButtonComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ButtonComponent.__super__.initialize.call(this, options);

            this.options = options || {};

            this.$button = $(this.options._sourceElement);
            this.$button
                .on('click', this.onClick.bind(this));

            const buttonOptions = this.$button.data('options') || {};
            if (buttonOptions.confirmation && buttonOptions.confirmation.component) {
                this._deferredInit();
                loadModules(buttonOptions.confirmation.component)
                    .then(this._resolveDeferredInit.bind(this));
            }
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            const $target = $(e.currentTarget);
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
                const options = $element.data('options') || {};
                const redirectUrl = $element.data('redirecturl');
                if (redirectUrl) {
                    _.extend(options, {
                        redirectUrl: redirectUrl,
                        redirectUrlOptions: $element.data('redirecturloptions') || {}
                    });
                }

                this.buttonManager = new ButtonManager(options);
                $element.data('button-manager', this.buttonManager);
            }

            return $element.data('button-manager');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$button.off();
            this.$button.data('disposed', true);

            if (this.buttonManager && _.isFunction(this.buttonManager.dispose)) {
                this.buttonManager.dispose();
                delete this.buttonManager;
            }

            ButtonComponent.__super__.dispose.call(this);
        }
    });

    return ButtonComponent;
});
