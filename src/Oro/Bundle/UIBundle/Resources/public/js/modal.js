define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const Backbone = require('backbone');
    const BaseView = require('oroui/js/app/views/base/view');
    const template = require('tpl-loader!oroui/templates/modal-dialog.html');
    const mediator = require('oroui/js/mediator');
    const tools = require('oroui/js/tools');
    const manageFocus = require('oroui/js/tools/manage-focus').default;
    let config = require('module-config').default(module.id);

    const SUSPEND_MODE_CLASS = 'suspend-mode';
    const DATA_KEY = 'bs.modal';
    const EVENT_KEY = '.bs.modal';
    const EVENTS = {
        CLOSE: 'close',
        CANCEL: 'cancel',
        CLICK: 'click',
        CLICK_DISMISS: 'click.dismiss',
        OK: 'ok',
        BUTTONCLICK: 'buttonClick',
        SHOWN: 'shown',
        HIDDEN: 'hidden',
        RESIZE: 'resize',
        KEYDOWN: 'keydown',
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
            secondaryButtonClass: 'btn',
            closeButtonClass: '',
            handleClose: false,
            allowCancel: true,
            allowOk: true,
            allowClose: true,
            title: null,
            focusOk: true,
            okCloses: true,
            animate: false,
            disposeOnHidden: true
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
    const ModalView = BaseView.extend({
        template: template,

        hasOpenModal: false,

        suspended: false,

        /**
         * @inheritdoc
         */
        events: function() {
            const events = {};

            events[EVENTS.CLICK + ' .cancel'] = this.handlerClick.bind(this, EVENTS.CANCEL);
            events[EVENTS.CLICK + ' .ok'] = this.handlerClick.bind(this, EVENTS.OK);
            events[EVENTS.CLICK + ' [data-button-id]'] = this.handlerClick.bind(this, EVENTS.BUTTONCLICK);
            events[EVENTS.HIDDEN + EVENT_KEY] = 'onModalHidden';
            events[EVENTS.SHOWN + EVENT_KEY] = 'onModalShown';
            events[EVENTS.FOCUSIN + EVENT_KEY] = 'onModalFocusin';
            events[EVENTS.KEYDOWN + EVENT_KEY] = event => manageFocus.preventTabOutOfContainer(event, this.$el);
            return events;
        },

        listen: {
            'page:beforeChange mediator': 'close'
        },

        /**
         * @inheritdoc
         */
        defaults: config.defaults,

        /**
         * @inheritdoc
         */
        _attributes: function() {
            return {
                'class': 'modal oro-modal-normal',
                'role': 'dialog',
                'aria-modal': 'true',
                'tabindex': '-1',
                'aria-labelledby': this.cid
            };
        },

        /**
         * @inheritdoc
         */
        constructor: function ModalView(options) {
            ModalView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.defaults);

            if (this.options.template) {
                this.template = this.options.template;
            }

            ModalView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            ModalView.__super__.render.call(this);

            const content = this.options.content;

            this.$content = this.$('.modal-body');

            // Insert the main content if it's a view
            if (content instanceof Backbone.View) {
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
         * @inheritdoc
         */
        getTemplateData: function() {
            const data = ModalView.__super__.getTemplateData.call(this);
            const fields = ['allowOk', 'allowCancel', 'cancelButtonClass', 'closeButtonClass', 'secondaryButtonClass',
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

            const eventName = EVENTS[triggerKey.toUpperCase()] || null;

            event.preventDefault();

            this.triggerEventOnContent(eventName);
            this.trigger(eventName, $(event.target).data('button-id'));

            if (this.options && this.options.okCloses &&
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
            if (this.options && this.options.content instanceof Backbone.View) {
                this.options.content.trigger(eventName, this);
            }
        },

        onModalHidden: function() {
            this.hasOpenModal = false;

            ModalView.count--;
            mediator.trigger('modal:close', this);
            this.trigger(EVENTS.CLOSE);
            this.trigger(EVENTS.HIDDEN);
            this.triggerEventOnContent(EVENTS.HIDDEN);
            this.undelegateEvents();

            if (this.options.disposeOnHidden) {
                this.dispose();
            }
        },

        onModalShown: function() {
            this.trigger(EVENTS.SHOWN);
            this.triggerEventOnContent(EVENTS.SHOWN);
        },

        onModalFocusin: function(e) {
            /*
             * Prevents jquery-ui from focusing different dialog
             * (which is happening when focusin is triggered on document
             */
            e.stopPropagation();
        },

        /**
         * Renders and shows the modal
         *
         * @param {Function} [callback] Optional callback that runs only when OK is pressed.
         */
        open: function(callback) {
            if (this.disposed) {
                return;
            }

            if (!this.isRendered) {
                this.render();
            }

            this.delegateEvents();

            // Create it
            this.$el.modal(_.extend({
                keyboard: this.options.keyboard !== void 0 ? this.options.keyboard : true,
                backdrop: this.options.allowCancel ? true : 'static'
            }, this.options.modalOptions));

            // Adjust the modal and backdrop z-index; for dealing with multiple modals
            const numModalViews = ModalView.count;
            const $backdrop = $('.modal-backdrop:eq(' + numModalViews + ')');
            const backdropIndex = parseInt($backdrop.css('z-index'), 10);
            const elIndex = parseInt($backdrop.css('z-index'), 10) + 1;

            $backdrop.css('z-index', backdropIndex + numModalViews);
            this.$el.css('z-index', elIndex + numModalViews);

            if (this.options.allowCancel) {
                $backdrop.one(EVENTS.CLICK_DISMISS + EVENT_KEY, function() {
                    this.trigger(EVENTS.CANCEL);
                    this.triggerEventOnContent(EVENTS.CANCEL);
                }.bind(this));
            }

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

            this.hasOpenModal = true;

            return this;
        },

        /**
         * Handle for close the modal
         */
        close: function() {
            if (this.disposed) {
                return;
            }

            // Check if the modal should stay open
            if (this._preventClose) {
                this._preventClose = false;
                return;
            }

            if (this.suspended) {
                this._setSuspendState(false);
            }

            this.$el.modal('hide');
        },

        isOpen: function() {
            return this.hasOpenModal;
        },

        suspend: function() {
            if (!this.suspended) {
                this._setSuspendState(true);
            }
        },

        restore: function() {
            if (this.suspended) {
                this._setSuspendState(false);
            }
        },

        _setSuspendState: function(isSuspended) {
            if (this.disposed) {
                return;
            }

            const modal = this.$el.data(DATA_KEY);

            $([modal._element, modal._backdrop]).toggleClass(SUSPEND_MODE_CLASS, isSuspended);
            this.suspended = isSuspended;
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
            const clientHeight = this.$el[0].clientHeight;
            if (clientHeight < this.$el[0].scrollHeight) {
                this.$('.modal-body').height(clientHeight -
                    this.$('.modal-header').outerHeight() -
                    this.$('.modal-footer').outerHeight());
            }
        },

        /**
         * @inheritdoc
         */
        delegateEvents: function(events) {
            ModalView.__super__.delegateEvents.call(this, events);

            $(document).one(EVENTS.KEYUP_DISMISS + EVENT_KEY + this.eventNamespace(), function(event) {
                if (event.which !== 27) {
                    return;
                }

                this.trigger(this.options.handleClose ? EVENTS.CLOSE : EVENTS.CANCEL);
                this.triggerEventOnContent(EVENTS.HIDDEN);
            }.bind(this));

            if (tools.isMobile()) {
                this._fixHeightForMobile();
                $(window).on(EVENTS.RESIZE + this.eventNamespace(), this._fixHeightForMobile.bind(this));
            }

            return this;
        },

        /**
         * @inheritdoc
         */
        undelegateEvents: function() {
            ModalView.__super__.undelegateEvents.call(this);
            $(document).off(this.eventNamespace());
            $(window).off(this.eventNamespace());

            return this;
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.isOpen()) {
                this.close();
            }

            if (this.disposed) {
                return;
            }

            const content = this.options.content;

            if (content instanceof Backbone.View && !content.disposed) {
                content.$el.detach();
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
