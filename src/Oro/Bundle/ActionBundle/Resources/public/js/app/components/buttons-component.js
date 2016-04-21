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
         * @property {Object}
         */
        options: {},

        /**
         * @property {jQuery.Element}
         */
        $container: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ButtonsComponent.__super__.initialize.apply(this, arguments);

            this.options = options || {};

            this.$container = $(this.options._sourceElement);
            this.$container
                .on('click', 'a.action-button', _.bind(this.onClick, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            this._getActionManager($(e.currentTarget)).execute(e);

            return false;
        },

        /**
         * @param {jQuery.Element} $element
         * @returns {ActionManager}
         * @private
         */
        _getActionManager: function($element) {
            if (!$element.data('action-manager')) {
                var options = $element.data('options') || {};

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
