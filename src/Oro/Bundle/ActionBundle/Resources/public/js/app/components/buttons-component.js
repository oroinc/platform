/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var BaseComponent = require('oroui/js/app/components/base/component');
    var ActionManager = require('oroaction/js/action-manager');
    var _ = require('underscore');
    var $ = require('jquery');

    var ButtonsComponent = BaseComponent.extend({

        /**
         * @property {jQuery.Element}
         */
        $container: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ButtonsComponent.__super__.initialize.apply(this, arguments);

            this.options = _.defaults(options || {}, this.options);

            this.$container = $(this.options._sourceElement);
            this.$container
                .on('click', 'a.action-button', _.bind(this.onClick, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            e.preventDefault();

            this._getActionManager($(e.currentTarget)).execute(e);
        },

        /**
         * @param {jQuery.Element} $element
         * @returns {ActionManager}
         * @private
         */
        _getActionManager: function($element) {
            if (!$element.data('action-manager')) {
                var options = {
                    showDialog: Boolean($element.data('dialog-show')),
                    hasDialog: Boolean($element.data('dialog-url')),
                    dialogUrl: $element.data('dialog-url'),
                    dialogOptions: $element.data('dialog-options'),
                    redirectUrl: $element.data('page-url'),
                    url: $element.attr('href'),
                    confirmation: Boolean($element.data('confirmation')),
                    messages: {
                        confirm_content: $element.data('confirmation')
                    }
                };

                $element.data('action-manager', new ActionManager(options));
            }

            return $element.data('action-manager');
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$container.off();

            ButtonsComponent.__super__.dispose.call(this);
        }
    });

    return ButtonsComponent;
});
