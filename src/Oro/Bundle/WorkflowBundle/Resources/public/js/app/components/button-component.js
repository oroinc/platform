define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var TransitionHandler = require('oroworkflow/js/transition-handler');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');

    var ButtonComponent = BaseComponent.extend({
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

            this._processButton();
        },

        /**
         * @private
         */
        _processButton: function() {
            var self = this;
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

            ButtonComponent.__super__.dispose.call(this);
        }
    });

    return ButtonComponent;
});
