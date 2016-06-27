define([
    'underscore',
    'backbone',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/tools',
    'backbone-bootstrap-modal'
], function(_, Backbone, __, mediator, tools) {
    'use strict';

    var Modal;
    var $ = Backbone.$;

    /**
     * Implementation of Bootstrap Modal
     * Oro extension of Bootstrap Modal wrapper for use with Backbone.
     *
     * @export  oroui/js/modal
     * @class   oroui.Modal
     * @extends Backbone.BootstrapModal
     */
    Modal = Backbone.BootstrapModal.extend({
        defaults: {
            okText: __('OK'),
            cancelText: __('Cancel'),
            handleClose: false
        },

        /** @property {String} */
        className: 'modal oro-modal-normal',

        initialize: function(options) {
            options = options || {};
            _.defaults(options, this.defaults);

            if (options.handleClose) {
                this.events = _.extend({}, this.events, {'click .close': _.bind(this.onClose, this)});
            }
            Modal.__super__.initialize.call(this, options);
        },

        onClose: function(event) {
            event.preventDefault();

            this.trigger('close');

            if (this.options.content && this.options.content.trigger) {
                this.options.content.trigger('close', this);
            }
        },

        /**
         * Renders and shows the modal
         *
         * @param {Function} [cb]     Optional callback that runs only when OK is pressed.
         */
        open: function(cb) {
            if (!this.isRendered) {
                this.render();
            }
            this.delegateEvents();

            var self = this;
            var $el = this.$el;

            //Create it
            $el.modal(_.extend({
                keyboard: this.options.allowCancel,
                backdrop: this.options.allowCancel ? true : 'static'
            }, this.options.modalOptions));

            $el.one('shown', function() {
                if (self.options.content && self.options.content.trigger) {
                    self.options.content.trigger('shown', self);
                }

                self.trigger('shown');
            });

            //Adjust the modal and backdrop z-index; for dealing with multiple modals
            var numModals = Backbone.BootstrapModal.count;
            var $backdrop = $('.modal-backdrop:eq(' + numModals + ')');
            var backdropIndex = parseInt($backdrop.css('z-index'), 10);
            var elIndex = parseInt($backdrop.css('z-index'), 10) + 1;

            $backdrop.css('z-index', backdropIndex + numModals);
            this.$el.css('z-index', elIndex + numModals);

            if (this.options.allowCancel) {
                $backdrop.one('click', function() {
                    if (self.options.content && self.options.content.trigger) {
                        self.options.content.trigger('cancel', self);
                    }

                    self.trigger('cancel');
                });

                $(document).one('keyup.dismiss.modal' + this._eventNamespace(), function(e) {
                    if (e.which !== 27) {
                        return;
                    }
                    if (self.options.handleClose) {
                        self.trigger('close');
                    } else {
                        self.trigger('cancel');
                    }

                    if (self.options.content && self.options.content.trigger) {
                        self.options.content.trigger('shown', self);
                    }
                });
            }

            this.once('cancel', function() {
                self.close();
            });

            this.once('close', function() {
                self.close();
            });

            Backbone.BootstrapModal.count++;

            //Run callback on OK if provided
            if (cb) {
                self.on('ok', cb);
            }

            this.once('cancel', _.bind(function() {
                this.$el.trigger('hidden');
            }, this));

            if (tools.isMobile()) {
                this._fixHeightForMobile();
                $(window).on('resize' + this._eventNamespace(), _.bind(this._fixHeightForMobile, this));
            }
            mediator.trigger('modal:open', this);

            //Focus OK button
            if (self.options.focusOk) {
                $el.find('.btn.ok').focus();
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        close: function() {
            Modal.__super__.close.call(this);
            $(document).off(this._eventNamespace());
            $(window).off(this._eventNamespace());
            this.stopListening();
            mediator.trigger('modal:close', this);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.$content;
            Modal.__super__.dispose.call(this);
        },

        /**
         * Updates content of modal dialog
         */
        setContent: function(content) {
            this.options.content = content;
            this.$el.find('.modal-body').html(content);
        },

        /**
         * Returns event's name space
         *
         * @returns {string}
         * @protected
         */
        _eventNamespace: function() {
            return '.delegateEvents' + this.cid;
        },

        _fixHeightForMobile: function() {
            this.$('.modal-body').height('auto');
            var clientHeight = this.$el[0].clientHeight;
            if (clientHeight < this.$el[0].scrollHeight) {
                this.$('.modal-body').height(clientHeight -
                    this.$('.modal-header').outerHeight() -
                    this.$('.modal-footer').outerHeight());
            }
        }
    });

    return Modal;
});
