define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const TransitionHandler = require('oroworkflow/js/transition-handler');
    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');

    const ButtonComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            displayType: ''
        },

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

            this._processButton();
        },

        /**
         * @private
         */
        _processButton: function() {
            const self = this;
            if (this.$button.data('enabled')) {
                if (this.options.displayType === 'dialog') {
                    this.$button.data('executor', function() {
                        TransitionHandler.call(self.$button);
                    });
                    this.$button.on('click', function(e) {
                        e.preventDefault();

                        self._onClickButtonExecutor(this);
                    });
                } else {
                    this.$button.on('click', function(e) {
                        e.preventDefault();

                        self._onClickButtonRedirect(this);
                    });
                }
            } else {
                this.$button.on('click', function(e) {
                    e.preventDefault();
                });
                if (this.$button.data('transition-condition-messages')) {
                    this.$button.popover({
                        html: true,
                        placement: 'bottom',
                        container: 'body',
                        trigger: 'hover',
                        title: '<i class="fa-exclamation-circle"></i>' + __('Unmet conditions'),
                        content: this.$button.data('transition-condition-messages')
                    });
                }
            }
        },

        /**
         * @param clickedButton
         * @private
         */
        _onClickButtonExecutor: function(clickedButton) {
            $(clickedButton).data('executor').call();
        },

        /**
         * @param clickedButton
         * @private
         */
        _onClickButtonRedirect: function(clickedButton) {
            mediator.execute('redirectTo', {url: this.$button.data('transition-url')}, {redirect: true});
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$button.off();
            this.$button.data('disposed', true);

            ButtonComponent.__super__.dispose.call(this);
        }
    });

    return ButtonComponent;
});
