/*global define*/
define([
    'underscore',
    'backbone',
    'backbone-bootstrap-modal'
], function (_, Backbone) {
    'use strict';

    var Modal, $;
    $ = Backbone.$;

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

        /**
         * Renders and shows the modal
         *
         * @param {Function} [cb]     Optional callback that runs only when OK is pressed.
         */
        open: function (cb) {
            if (!this.isRendered) this.render();
            this.delegateEvents();

            var self = this,
                $el = this.$el;

            //Create it
            $el.modal(_.extend({
                keyboard: this.options.allowCancel,
                backdrop: this.options.allowCancel ? true : 'static'
            }, this.options.modalOptions));

            //Focus OK button
            $el.one('shown', function () {
                if (self.options.focusOk) {
                    $el.find('.btn.ok').focus();
                }

                if (self.options.content && self.options.content.trigger) {
                    self.options.content.trigger('shown', self);
                }

                self.trigger('shown');
            });

            //Adjust the modal and backdrop z-index; for dealing with multiple modals
            var numModals = Backbone.BootstrapModal.count,
                $backdrop = $('.modal-backdrop:eq(' + numModals + ')'),
                backdropIndex = parseInt($backdrop.css('z-index'), 10),
                elIndex = parseInt($backdrop.css('z-index'), 10) + 1;

            $backdrop.css('z-index', backdropIndex + numModals);
            this.$el.css('z-index', elIndex + numModals);

            if (this.options.allowCancel) {
                $backdrop.one('click', function () {
                    if (self.options.content && self.options.content.trigger) {
                        self.options.content.trigger('cancel', self);
                    }

                    self.trigger('cancel');
                });

                $(document).one('keyup.dismiss.modal' + this._eventNamespace(), function (e) {
                    e.which === 27 && self.trigger('cancel');

                    if (self.options.content && self.options.content.trigger) {
                        e.which === 27 && self.options.content.trigger('shown', self);
                    }
                });
            }

            this.once('cancel', function () {
                self.close();
            });

            Backbone.BootstrapModal.count++;

            //Run callback on OK if provided
            if (cb) {
                self.on('ok', cb);
            }

            this.once('cancel', _.bind(function () {
                this.$el.trigger('hidden');
            }, this));

            return this;
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            delete this.$content;
            $(document).off(this._eventNamespace());
            Modal.__super__.dispose.call(this);
        },

        /**
         * Updates content of modal dialog
         */
        setContent: function (content) {
            this.options.content = content;
            this.$el.find('.modal-body').html(content);
        },

        /**
         * Returns event's name space
         *
         * @returns {string}
         * @protected
         */
        _eventNamespace: function () {
            return '.delegateEvents' + this.cid;
        }
    });

    return Modal;
});
