define(function(require) {
    'use strict';

    var ModalView;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!oroui/templates/modal-dialog.html');
    var mediator = require('oroui/js/mediator');
    var tools = require('oroui/js/tools');
    var module = require('module');
    var config = module.config();

    var EVENT_KEY = '.bs.modal';
    var EVENTS = {
        CLOSE: 'close',
        CANCEL: 'cancel',
        CLICK: 'click',
        CLICK_DISMISS: 'click.dismiss',
        OK: 'ok',
        BUTTONCLICK: 'buttonClick',
        SHOWN: 'shown',
        HIDDEN: 'hidden',
        RESIZE: 'resize',
        KEYUP_DISMISS: 'keydown.dismiss',
        FOCUSIN: 'focusin'
    };

    config = $.extend(true, {
        defaults: {
            templateSelector: '',
            okText: __('OK'),
            cancelText: __('Cancel'),
            secondaryText: __('Discard'),
            closeText: null,
            okButtonClass: 'btn btn-primary',
            cancelButtonClass: 'btn',
            closeButtonClass: '',
            handleClose: false,
            allowCancel: true,
            allowOk: true,
            allowClose: true,
            title: null,
            focusOk: true,
            okCloses: true,
            animate: false
        }
    }, config);

    /**
     * Implementation of Bootstrap ModalView
     * Oro extension of Bootstrap Modal wrapper for use with Backbone.
     *
     * @export  oroui/js/modal
     * @class   oroui.ModalView
     * @extends BaseView
     */
    ModalView = BaseView.extend({
        template: template,

        /**
         * @inheritDoc
         */
        events: function() {
            var events = {};

            events[EVENTS.CLICK + ' .close'] = this.handlerClick.bind(this, EVENTS.CANCEL);
            events[EVENTS.CLICK + ' .cancel'] = this.handlerClick.bind(this, EVENTS.CANCEL);
            events[EVENTS.CLICK + ' .ok'] = this.handlerClick.bind(this, EVENTS.OK);
            events[EVENTS.CLICK + ' [data-button-id]'] = this.handlerClick.bind(this, EVENTS.BUTTONCLICK);

            return events;
        },

        /**
         * @inheritDoc
         */
        defaults: config.defaults,

        /**
         * @inheritDoc
         */
        attributes: function() {
            var attrs = {};

            attrs['class'] = 'modal oro-modal-normal';
            attrs['role'] = 'modal';
            attrs['tabindex'] = '-1';
            attrs['aria-labelledby'] = this.cid;

            return attrs;
        },

        /**
         * @inheritDoc
         */
        constructor: function ModalView() {
            ModalView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.defaults);

            if (this.options.template) {
                this.template = this.options.template;
            }
            ModalView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            ModalView.__super__.render.apply(this, arguments);

            var content = this.options.content;

            this.$el.html(this.getTemplateFunction(this.options));
            this.$content = this.$('.modal-body');

            // Insert the main content if it's a view
            if (content.$el) {
                content.render();
                this.$content.html(content.$el);
            }

            if (this.options.animate) {
                this.$el.addClass('fade');
            }

            this.isRendered = true;

            return this;
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = ModalView.__super__.getTemplateData.apply(this, arguments);
            var fields = ['allowOk', 'allowCancel', 'cancelButtonClass', 'closeButtonClass',
                'okButtonClass', 'closeText', 'cancelText', 'okText', 'secondaryText', 'title', 'content'];

            return _.extend({
                modalId: this.cid
            }, data, _.pick(this.options, fields));
        },

        /**
         * Handler for button click
         *
         *  @param {String} triggerKey
         *  @param {jQuery.Event} event
         */
        handlerClick: function(triggerKey, event) {
            if (!this.$el) {
                return;
            }

            var eventName = EVENTS[triggerKey.toUpperCase()] || null;

            event.preventDefault();

            this.trigger(eventName, $(event.target).data('button-id') || this);
            this.triggerEventOnContent(eventName);

            if (this.options.okCloses &&
                (eventName === EVENTS.OK || eventName === EVENTS.BUTTONCLICK)
            ) {
                this.close();
            }
        },

        /**
         * Call event on content view if it present
         *
         * @params {String} eventName
         */
        triggerEventOnContent: function(eventName) {
            if (_.isObject(this.options.content) && this.options.content.trigger) {
                this.options.content.trigger(eventName, this);
            }
        },

        /**
         * Renders and shows the modal
         *
         * @param {Function} [callback] Optional callback that runs only when OK is pressed.
         */
        open: function(callback) {
            if (!this.isRendered) {
                this.render();
            }

            this.delegateEvents();

            // Create it
            this.$el.modal(_.extend({
                keyboard: this.options.allowCancel,
                backdrop: this.options.allowCancel ? true : 'static'
            }, this.options.modalOptions));

            // Adjust the modal and backdrop z-index; for dealing with multiple modals
            var numModalViews = ModalView.count;
            var $backdrop = $('.modal-backdrop:eq(' + numModalViews + ')');
            var backdropIndex = parseInt($backdrop.css('z-index'), 10);
            var elIndex = parseInt($backdrop.css('z-index'), 10) + 1;

            $backdrop.css('z-index', backdropIndex + numModalViews);
            this.$el.css('z-index', elIndex + numModalViews);

            if (this.options.allowCancel) {
                $backdrop.one(EVENTS.CLICK_DISMISS + EVENT_KEY, function() {
                    this.trigger(EVENTS.CANCEL);
                    this.triggerEventOnContent(EVENTS.CANCEL);
                }.bind(this));
            }

            this.once(EVENTS.CANCEL, function() {
                this.close();
            }.bind(this));

            this.once(EVENTS.CLOSE, function() {
                this.close();
            }.bind(this));

            ModalView.count++;

            // Run callback on OK if provided
            if (_.isFunction(callback)) {
                this.on(EVENTS.OK, callback);
            }

            mediator.trigger('modal:open', this);

            if (!_.isMobile()) {
                mediator.execute('layout:adjustLabelsWidth', this.$el);
            }

            // Focus OK button
            if (this.options.focusOk) {
                this.$('.ok').focus();
            }

            return this;
        },

        /**
         * Handle for close the modal
         */
        close: function() {
            // Check if the modal should stay open
            if (this._preventClose) {
                this._preventClose = false;
                return;
            }

            this.$el.modal('hide');
            ModalView.count--;

            this.undelegateEvents();
            this.stopListening();
            mediator.trigger('modal:close', this);
        },

        /**
         * Updates content of modal dialog
         */
        setContent: function(content) {
            this.options.content = content;
            this.$('.modal-body').html(content);
        },

        _fixHeightForMobile: function() {
            this.$('.modal-body').height('auto');
            var clientHeight = this.$el[0].clientHeight;
            if (clientHeight < this.$el[0].scrollHeight) {
                this.$('.modal-body').height(clientHeight -
                    this.$('.modal-header').outerHeight() -
                    this.$('.modal-footer').outerHeight());
            }
        },

        /**
         * @inheritDoc
         */
        delegateEvents: function(events) {
            ModalView.__super__.delegateEvents.call(this, events);

            this.$el.one(EVENTS.HIDDEN + EVENT_KEY, function onHidden(event) {
                // Ignore events propagated from interior objects, like bootstrap tooltips
                if (event.target !== event.currentTarget) {
                    return this.$el.one(EVENTS.HIDDEN + + EVENT_KEY, onHidden);
                }
                this.remove();

                this.trigger(EVENTS.HIDDEN);
                this.triggerEventOnContent(EVENTS.HIDDEN);
            }.bind(this));

            this.$el.one(EVENTS.SHOWN + EVENT_KEY, function() {
                this.trigger(EVENTS.SHOWN);
                this.triggerEventOnContent(EVENTS.SHOWN);
            }.bind(this));

            this.$el.on(EVENTS.FOCUSIN + EVENT_KEY, function(e) {
                /*
                 * Prevents jquery-ui from focusing different dialog
                 * (which is happening when focusin is triggered on document
                 */
                e.stopPropagation();
            });

            $(document).one(EVENTS.KEYUP_DISMISS + EVENT_KEY + this.eventNamespace(), function(event) {
                if (event.which !== 27) {
                    return;
                }

                this.trigger(this.options.handleClose ? EVENTS.CLOSE : EVENTS.CANCEL);
                this.triggerEventOnContent(EVENTS.SHOWN);
            }.bind(this));

            if (tools.isMobile()) {
                this._fixHeightForMobile();
                $(window).on(EVENTS.RESIZE + this.eventNamespace(), this._fixHeightForMobile.bind(this));
            }

            return this;
        },

        /**
         * @inheritDoc
         */
        undelegateEvents: function() {
            ModalView.__super__.undelegateEvents.call(this);
            $(document).off(this.eventNamespace());
            $(window).off(this.eventNamespace());

            return this;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            delete this.$content;
            this.$el.modal('dispose');

            ModalView.__super__.dispose.call(this);
        },

        /**
         * Stop the modal from closing.
         * Can be called from within a 'close' or 'ok' event listener.
         */
        preventClose: function() {
            this._preventClose = true;
        }
    }, {
        count: 0
    });

    return ModalView;
});
