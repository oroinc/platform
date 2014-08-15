/*global define*/
define([
    'underscore',
    'backbone',
    'backbone-bootstrap-modal'
], function (_, Backbone) {
    'use strict';

    var Modal;

    /**
     * Implementation of Bootstrap Modal
     * Oro extension of Bootstrap Modal wrapper for use with Backbone.
     *
     * @export  oroui/js/modal
     * @class   oroui.Modal
     * @extends Backbone.BootstrapModal
     */
    Modal = Backbone.BootstrapModal.extend({
        /** @property {String} */
        className: 'modal',

        open: function() {
            Modal.__super__.open.apply(this, arguments);

            this.once('cancel', _.bind(function() {
                this.$el.trigger('hidden');
            }, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            delete this.$content;
            Modal.__super__.dispose.call(this);
        },

        /**
         * Updates content of modal dialog
         */
        setContent: function (content) {
            this.options.content = content;
            this.$el.find('.modal-body').html(content);
        }
    });

    return Modal;
});
