/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var TransitionHandler = require('oroworkflow/js/transition-handler');
    var $ = require('jquery');
    var __ = require('orotranslation/js/translator');

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
                        $(this).data('executor').call();
                    });
                }
            } else {
                this.$button.on('click', function(e) {
                    e.preventDefault();
                });
                if (this.$button.data('transition-condition-messages')) {
                    this.$button.popover({
                        'html': true,
                        'placement': 'bottom',
                        'container': $('body'),
                        'trigger': 'hover',
                        'title': '<i class="icon-exclamation-sign"></i>' + __('Unmet conditions'),
                        'content': this.$button.data('transition-condition-messages')
                    });
                }
            }
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
