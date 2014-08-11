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
            Backbone.mediator.unsubscribe(null, null, this);
            this.off();
            this.stopListening();
            if (this.$el) {
                this.undelegateEvents();
                this.$el.removeData();
            }
            delete this.$content;
            delete this.$el;
            delete this.el;
        },
    });

    return Modal;
});
