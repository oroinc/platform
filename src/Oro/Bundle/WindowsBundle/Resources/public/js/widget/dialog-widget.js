define(function(require, exports, module) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    const messenger = require('oroui/js/messenger');
    const mediator = require('oroui/js/mediator');
    const AbstractWidget = require('oroui/js/widget/abstract-widget');
    const StateModel = require('orowindows/js/dialog/state/model');
    const LoadingBarView = require('oroui/js/app/views/loading-bar-view');
    const DialogManager = require('orowindows/js/widget/dialog-manager');
    const dialogManager = new DialogManager();
    require('jquery.dialog.extended');

    const MOBILE_WIDTH = 375;
    const config = _.extend({
        type: 'dialog',
        limitTo: tools.isMobile() ? 'body' : '#container',
        stateEnabled: true,
        incrementalPosition: true,
        preventModelRemoval: false,
        messengerContainerClass: 'ui-dialog-messages',
        mobileLoadingBar: true,
        desktopLoadingBar: false,
        triggerEventOnMessagesRemoved: true
    }, require('module-config').default(module.id));

    /**
     * @export  oro/dialog-widget
     * @class   oro.DialogWidget
     * @extends oroui.widget.AbstractWidget
     */
    const DialogWidget = AbstractWidget.extend({
        options: _.extend({}, AbstractWidget.prototype.options, config),

        // Windows manager global variables
        windowsPerRow: 10,
        windowOffsetX: 15,
        windowOffsetY: 15,
        windowX: 0,
        windowY: 0,
        defaultPos: 'center center',
        openedWindows: 0,
        contentTop: null,
        keepAliveOnClose: false,
        /**
         * Flag if the widget is embedded to the page
         * (dialog has own life cycle)
         *
         * @type {boolean}
         */
        _isEmbedded: false,

        events: {
            'content:changed': 'resetDialogPosition',
            'shown.bs.collapse': 'resetDialogPosition',
            'hidden.bs.collapse': 'resetDialogPosition'
        },

        listen: {
            'widgetRender': 'onWidgetRender',
            'widgetReady': 'onContentUpdated',
            'page:request mediator': 'onPageChange',
            'layout:reposition mediator': 'onLayoutReposition'
        },

        $messengerContainer: null,

        /**
         * @property {Object}
         */
        loadingBar: null,

        dialogOptionsMap: {
            minWidth: {
                // Dialogs which have forms with wide fields like wysiwyg
                expanded: tools.isMobile() ? MOBILE_WIDTH : 812
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function DialogWidget(options) {
            const resetDialogPosition = this.resetDialogPosition.bind(this);
            this.resetDialogPosition = _.debounce(() => this.disposed || resetDialogPosition(), 10);
            DialogWidget.__super__.constructor.call(this, options);
        },

        /**
         * Initialize dialog
         */
        initialize: function(options) {
            options = options || {};
            this.options = _.defaults(options, this.options);

            options.dialogOptions = options.dialogOptions || {};
            const dialogOptions = this.doMapDialogOptions(options.dialogOptions);

            _.defaults(dialogOptions, {
                title: options.title,
                limitTo: this.options.limitTo,
                // minimal width is adjusted to dialog shows typical form without horizontal scroll
                minWidth: tools.isMobile() ? MOBILE_WIDTH : 604,
                minHeight: 150
            });

            if (!dialogOptions.position) {
                dialogOptions.position = this.getPositionProps();
            }

            if (tools.isMobile()) {
                options.incrementalPosition = false;
            }

            if (dialogOptions.modal) {
                // it's possible to track state and minimize only for not modal dialogs
                options.stateEnabled = false;
                dialogOptions.allowMinimize = false;
            }

            if (options.stateEnabled) {
                this._initModel();
            }

            dialogOptions.dragStop = this.onDragStop.bind(this);
            dialogOptions.beforeClose = this.closeHandler.bind(this, dialogOptions.close);
            delete dialogOptions.close;

            dialogManager.add(this);

            this.initializeWidget(options);
        },

        /**
         * Substitutes dialog options
         *
         * @param {Object} dialogOptions
         * @returns {Object}
         */
        doMapDialogOptions: function(dialogOptions) {
            Object.entries(this.dialogOptionsMap).forEach(([key, value]) => {
                const mapProperty = dialogOptions[key];

                if (mapProperty === void 0) {
                    return;
                }

                const mapValue = value[mapProperty];

                if (mapValue !== void 0) {
                    dialogOptions[key] = mapValue;
                }
            });

            return dialogOptions;
        },

        onDragStop: function(event, ui) {
            const {left, top} = $(this.getLimitToContainer()).offset();

            this.dndPosition = {
                left: ui.position.left - left,
                top: ui.position.top - top
            };
        },

        onWidgetRender: function(content) {
            this._initAdjustHeight(content);
            this._setMaxSize();
            this._addMessengerContainer();
            this._initLoadingBar();
        },

        /**
         * Add temporary container for messages into dialog window
         * @private
         */
        _addMessengerContainer: function() {
            const containerClass = this.options.messengerContainerClass;
            const $uiDialog = this.widget.dialog('instance').uiDialog;

            if (containerClass && !$uiDialog.find('.' + containerClass).length) {
                this.$messengerContainer = $('<div/>')
                    .addClass(containerClass)
                    .attr('data-role', 'messenger-temporary-container');

                this.widget.before(this.$messengerContainer);
            }
        },

        setTitle: function(title) {
            this.widget.dialog('option', 'title', title);
        },

        _initModel: function() {
            if (this.model) {
                this.restoreMode = true;
                const attributes = this.model.get('data');
                $.extend(true, this.options, attributes);
                if (this.options.el) {
                    this.setElement(this.options.el);
                } else if (this.model.get('id')) {
                    const restoredEl = $('#widget-restored-state-' + this.model.get('id'));
                    if (restoredEl.length) {
                        this.setElement(restoredEl);
                    }
                }
            } else {
                this.model = new StateModel();
            }
        },

        /**
         * Create loading bar under titlebar
         * @private
         */
        _initLoadingBar: function() {
            if ((this.options.mobileLoadingBar && tools.isMobile()) ||
                (this.options.desktopLoadingBar && !tools.isMobile())) {
                this.subview('LoadingBarView', new LoadingBarView({
                    container: this.widget.dialog('instance').uiDialogTitlebar,
                    ajaxLoading: true
                }));

                this.widget.on({
                    [`ajaxStart${this.eventNamespace()}`]: e => {
                        e.stopPropagation();
                        this.subview('LoadingBarView').showLoader();
                    },
                    [`ajaxComplete${this.eventNamespace()}`]: e => {
                        e.stopPropagation();
                        this.subview('LoadingBarView').hideLoader();
                    }
                });
            }
        },

        /**
         * Handles dialog close action
         *  - executes external close handler
         *  - disposes dialog widget
         *
         * @param {Function|undefined} onClose External onClose handler
         */
        closeHandler: function(onClose) {
            this.removeMessageContainer();

            if (_.isFunction(onClose)) {
                onClose();
            }
            if (!this.keepAliveOnClose) {
                this.dispose();
            }
        },

        removeMessageContainer: function() {
            if (this.$messengerContainer && this.$messengerContainer.length) {
                if (this.options.triggerEventOnMessagesRemoved) {
                    this.$messengerContainer.trigger('remove');
                }
                this.$messengerContainer.remove();
            }
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.removeMessageContainer();

            $(window).off(this.eventNamespace());
            dialogManager.remove(this);
            if (this.model && !this.options.preventModelRemoval) {
                this.model.destroy({
                    errorHandlerMessage: function(event, xhr) {
                        // Suppress error if it's 404 response
                        return xhr.status !== 404;
                    }
                });
            }
            this._hideLoading();

            // need to remove components in widget before DOM will be deleted
            this.disposePageComponents();
            _.invoke(this.subviews, 'dispose');
            this.subviews = {};

            if (this.widget) {
                this.widget.remove();
                delete this.widget;
            }

            DialogWidget.__super__.dispose.call(this);
        },

        /**
         * Returns flag if the widget is embedded to the parent content
         *
         * @returns {boolean}
         */
        isEmbedded: function() {
            // modal dialogs has same life cycle as embedded widgets
            return this._isEmbedded || this.options.dialogOptions.modal;
        },

        /**
         * Handles content load event and sets focus on first form input
         */
        onContentUpdated: function() {
            this._fixScrollableHeight();
            this.focusContent();
        },

        focusContent: function() {
            this.$('form:first').focusFirstInput();
            if (!$.contains(this.el, document.activeElement)) {
                this.widget.dialog('instance').element.attr('tabindex', '0').trigger('focus');
            }
        },

        /**
         * Handle content loading failure.
         * @private
         */
        _onContentLoadFail: function(jqxhr) {
            this.options.stateEnabled = false;
            if (jqxhr.status === 403) {
                messenger.notificationFlashMessage('error', __('oro.ui.forbidden_error'));
                this.remove();
            } else {
                DialogWidget.__super__._onContentLoadFail.call(this, jqxhr);
            }
        },

        handleStateChange: function(e, data) {
            if (!this.options.stateEnabled || this.disposed) {
                return;
            }
            if (this.restoreMode) {
                this.restoreMode = false;
                return;
            }
            const saveData = _.omit(this.options, ['dialogOptions', 'el', 'model']);
            if (!saveData.url) {
                saveData.el = $('<div/>').append(this.$el.clone()).html();
            }
            saveData.dialogOptions = {};
            _.each(this.options.dialogOptions, function(val, key) {
                if (!_.isFunction(val) && key !== 'position') {
                    saveData.dialogOptions[key] = val;
                }
            }, this);

            saveData.dialogOptions.title = $(e.target).dialog('option', 'title');
            saveData.dialogOptions.state = data.state;
            saveData.dialogOptions.snapshot = data.snapshot;
            saveData.wid = this.getWid();
            if (this.model) {
                this.model.save({data: saveData});
            }
        },

        /**
         * Handles page change
         *  - closes dialogs with not tracked state (eg. modal dialogs)
         */
        onPageChange: function() {
            if (!this.options.stateEnabled) {
                this.remove();
            }
        },

        /**
         * @param {HTMLElement} [context]
         */
        onLayoutReposition(context) {
            // there's no context of layout reposition (whole page is updated)
            // or context of reposition is within dialog
            const doReposition = context === void 0 || $.contains(this.widget.dialog('widget')[0], context);

            if (doReposition) {
                this.resetDialogPosition();
            }

            this._setMaxMinWith();
        },

        _onAdoptedFormResetClick: function() {
            this.remove();
        },

        /**
         * Removes dialog widget
         */
        remove: function() {
            if (this.widget && this.widget.dialog('isOpen')) {
                // There's widget, close it before remove.
                // Close handler will invoke dispose method,
                // where remove method will be called again
                this.widget.dialog('close');
            } else {
                DialogWidget.__super__.remove.call(this);
            }
        },

        getWidget: function() {
            return this.widget;
        },

        /**
         * @inheritdoc
         */
        getLayoutElement: function() {
            // covers not only widget body, but whole .ui-dialog, including .ui-dialog-buttonpane
            return this.widget.parent();
        },

        getActionsElement: function() {
            if (!this.actionsEl) {
                const className = 'pull-right';
                this.actionsEl = $('<div />', {'class': className}).appendTo(
                    $('<div class="form-actions widget-actions"/>').appendTo(
                        this.widget.dialog('actionsContainer')
                    )
                );
            }
            return this.actionsEl;
        },

        getLimitToContainer: function() {
            let limitTo = this.options.dialogOptions.limitTo;

            if (limitTo === 'viewport') {
                return document.documentElement;
            } else if (limitTo) {
                limitTo = $(limitTo)[0];
            }

            return limitTo || document.body;
        },

        _clearActionsContainer: function() {
            this.widget.dialog('actionsContainer').empty();
            this.actionsEl = null;
        },

        _renderActions: function() {
            DialogWidget.__super__._renderActions.call(this);
            if (this.hasActions()) {
                this.widget.dialog('showActionsContainer');
            }
        },

        /**
         * Show dialog
         */
        show: function() {
            let dialogOptions;
            if (!this.widget) {
                dialogOptions = _.extend({}, this.options.dialogOptions);
                dialogOptions.stateChange = this.handleStateChange.bind(this);
                if (dialogOptions.state !== 'minimized') {
                    dialogOptions.dialogClass = 'invisible ' + (dialogOptions.dialogClass || '');
                }
                this.widget = $('<div/>');
                this._bindDialogEvents();
                this.widget.html(this.$el).dialog(dialogOptions);
                this.getLayoutElement().attr('data-layout', 'separate');
                this._setMaxMinWith();
            } else {
                if (this.widget.dialog('instance') !== void 0 && !this.widget.dialog('isOpen')) {
                    this.widget.dialog('open');
                }
                this.widget.html(this.$el);
            }
            this.loadingElement = this.$el.closest('.ui-dialog');
            DialogWidget.__super__.show.call(this);

            this._fixDialogMinHeight(true);
            this.widget.on('dialogmaximize dialogrestore', () => {
                this._fixDialogMinHeight(true);
                this.widget.trigger('resize');
            });
            this.widget.on('dialogminimize', () => {
                this._fixDialogMinHeight(false);
                this.widget.trigger('resize');
            });
        },

        hide: function() {
            // keepAliveOnClose property is used to avoid disposing the widget on dialog close to be able open it again
            const keepAliveOnClose = this.keepAliveOnClose;
            this.keepAliveOnClose = true;
            this.widget.dialog('close');
            delete this.dndPosition;
            this.keepAliveOnClose = keepAliveOnClose;
        },

        _renderHandler: function() {
            this.resetDialogPosition();
            this.trigger('widgetReady', this);
            // Waiting a little bite while the dialog will be positioned correctly and its content rendered
            _.delay(() => {
                if (!this.disposed) {
                    this.widget.dialog('widget').removeClass('invisible');
                    this.focusContent();
                }
            }, 50);
        },

        _initAdjustHeight: function(content) {
            this.widget.off('.adjust-height-events');
            const scrollableContent = content.find('.scrollable-container');
            const resizeEvents = [
                'dialogresize.adjust-height-events',
                'dialogmaximize.adjust-height-events',
                'dialogrestore.adjust-height-events'
            ].join(' ');
            if (scrollableContent.length) {
                scrollableContent.css('overflow', 'auto');
                this.widget.on(resizeEvents, this._fixScrollableHeight.bind(this));
            }
        },

        /**
         * Adjusts dialog width to its limit container.
         * There may be a case when content enlarges dialog size
         * @private
         */
        _setMaxMinWith: function() {
            if (!this.widget) {
                // widget is not initialized -- where's nothing to position yet
                return;
            }

            const minWidth = this.widget.dialog('option', 'minWidth');
            let maxWidth = this.widget.dialog('option', 'maxWidth');

            if (minWidth || maxWidth) {
                if (maxWidth > this.getLimitToContainer().clientWidth) {
                    maxWidth = this.getLimitToContainer().clientWidth;
                }

                this.widget.dialog('instance').element.css({
                    minWidth: minWidth,
                    maxWidth: maxWidth || this.getLimitToContainer().clientWidth
                });
            }
        },

        _setMaxSize: function() {
            this.widget.off('.set-max-size-events');
            this.widget.on('dialogresizestart.set-max-size-events', () => {
                const dialog = this.widget.closest('.ui-dialog');
                const containerEl = this.getLimitToContainer();
                dialog.css({
                    maxWidth: containerEl.clientWidth,
                    maxHeight: containerEl.clientHeight
                });
            });
        },

        _fixDialogMinHeight: function(isEnabled) {
            if (isEnabled) {
                const minHeight = this.options.dialogOptions.minHeight +
                    this.widget.dialog('actionsContainer').height();
                this.widget.dialog('widget').css('min-height', minHeight);
            } else {
                this.widget.dialog('widget').css('min-height', 0);
            }
        },

        _clearScrollableHeight: function() {
            if (!tools.isMobile()) {
                // on mobile devices without setting these properties modal dialogs cannot be scrolled
                this.widget.find('.scrollable-container').each(function() {
                    $(this).prop({prevScrollTop: $(this).scrollTop()});
                    $(this).css('max-height', '');
                });
            }
        },

        _fixScrollableHeight: function() {
            if (!tools.isMobile()) {
                // on mobile devices without setting these properties modal dialogs cannot be scrolled
                const widget = this.widget;
                const content = widget.find('.widget-content:first');
                const contentHeight = Math.ceil(widget.height() - content.outerHeight(true) + content.height());

                widget.find('.scrollable-container').each(function() {
                    const $el = $(this);
                    const height = contentHeight - $el.position().top;

                    if (height) {
                        $el.css('max-height', height);

                        const restoredScrollTop = $el.prop('prevScrollTop');

                        if (restoredScrollTop) {
                            $el.scrollTop(restoredScrollTop);
                        }
                    }
                });
            }

            mediator.execute({name: 'responsive-layout:update', silent: true}, this.el);
        },

        /**
         * Resets dialog position to default
         */
        resetDialogPosition: function() {
            if (!this.widget) {
                // widget is not initialized -- where's nothing to position yet
                return;
            }

            this._clearScrollableHeight();

            if (this.options.position) {
                this.setPosition(_.extend(this.options.position, {
                    of: this.getLimitToContainer(),
                    collision: 'fit'
                }));
            }

            if (this.dndPosition && this.getState() !== 'maximized') {
                const {left, top} = this.dndPosition;

                this.setPosition({
                    my: 'left top',
                    at: `left+${left} top+${top}`,
                    of: this.getLimitToContainer(),
                    collision: 'fit'
                });
            } else if (this.options.incrementalPosition) {
                dialogManager.updateIncrementalPosition(this);
            } else {
                this.setPosition(this.getPositionProps());
            }

            this._fixScrollableHeight();
        },

        internalSetDialogPosition: function(position, leftShift, topShift) {
            if (!leftShift) {
                leftShift = 0;
            }
            if (!topShift) {
                topShift = 0;
            }
            if (!this.widget) {
                throw new Error('this function must be called only after dialog is created');
            }
            const dialog = this.widget.closest('.ui-dialog');
            dialog.position(position);
            // must update manually 'cause $.position call gives side effects
            dialog.css({
                top: parseInt(dialog.css('top')) + topShift,
                left: parseInt(dialog.css('left')) + leftShift
            });
        },

        setPosition: function(position, leftShift, topShift) {
            if (!this.widget) {
                throw new Error('this function must be called only after dialog is created');
            }
            const dialog = this.widget.closest('.ui-dialog');

            const initialDialogPosition = dialog.css('position');
            const scrollableContainer = tools.isMobile() ? $('html, body') : this.widget;
            const widgetScrollTop = scrollableContainer.scrollTop();

            if (tools.isIOS() && initialDialogPosition === 'fixed') {
                // Manipulating with position to fix iOS bug,
                // when orientation is changed
                $('html, body').scrollTop(0);
                dialog.css({
                    position: 'absolute'
                });
            }

            this.internalSetDialogPosition(position, leftShift, topShift);
            this.leftAndWidthAdjustments(dialog);
            this.topAndHeightAdjustments(dialog);
            if (!_.isMobile()) {
                mediator.execute('layout:adjustLabelsWidth', this.widget);
            }
            this.widget.trigger('dialogreposition');

            if (tools.isIOS() && initialDialogPosition === 'fixed') {
                // Manipulating with position to fix iOS bug,
                // when orientation is changed
                dialog.css({
                    position: initialDialogPosition
                });
            }

            scrollableContainer.scrollTop(widgetScrollTop);
        },

        leftAndWidthAdjustments: function(dialog) {
            // containerEl.offsetLeft will only work if offsetParent is document.body
            const containerEl = this.getLimitToContainer();
            let left = parseFloat(dialog.css('left')) - containerEl.offsetLeft;
            const width = parseFloat(dialog.css('width'));
            const minWidth = parseFloat(dialog.css('min-width'));
            if (left < 0) {
                dialog.css('left', containerEl.offsetLeft);
                left = 0;
            }
            if (left + width > containerEl.clientWidth) {
                if (containerEl.clientWidth - left < this.options.dialogOptions.minWidth &&
                    this.options.dialogOptions.minWidth <= containerEl.clientWidth) {
                    dialog.css('left', containerEl.clientWidth - this.options.dialogOptions.minWidth +
                        containerEl.offsetLeft);
                    dialog.css('width', this.options.dialogOptions.minWidth);
                    return;
                }
                dialog.css('width', containerEl.clientWidth - left);
                if (minWidth > containerEl.clientWidth - left) {
                    dialog.css('min-width', containerEl.clientWidth - left);
                }
            } else {
                if (this.getState() !== 'maximized' &&
                    (!this.widgetIsResizable() && !this.options.dialogOptions.autoResize)) {
                    dialog.css('width', this.options.dialogOptions.width);
                }
            }
        },

        topAndHeightAdjustments: function(dialog) {
            // containerEl.offsetTop will only work if offsetParent is document.body
            const containerEl = this.getLimitToContainer();

            // Set auto height for dialog before calc
            if (this.getState() !== 'maximized' && !this.widgetIsResizable()) {
                dialog.css('height', 'auto');
            }

            let top = parseFloat(dialog.css('top')) - containerEl.offsetTop;
            const height = parseFloat(dialog.css('height'));
            const minHeight = parseFloat(dialog.css('min-height'));
            const windowHeight = parseFloat($(window).height());
            if (containerEl.clientHeight >= windowHeight) {
                top = (windowHeight - height) / 2;
                dialog.css('top', top);
            }
            if (top < 0) {
                dialog.css('top', containerEl.offsetTop);
                top = 0;
            }
            if (top + height > containerEl.clientHeight) {
                if (containerEl.clientHeight - top < this.options.dialogOptions.minHeight &&
                    this.options.dialogOptions.minHeight <= containerEl.clientHeight) {
                    dialog.css('top', containerEl.clientHeight - this.options.dialogOptions.minHeight +
                        containerEl.offsetTop);
                    dialog.css('height', this.options.dialogOptions.minHeight);
                    return;
                }
                dialog.css('height', containerEl.clientHeight - top);
                if (minHeight > containerEl.clientHeight - top) {
                    dialog.css('min-height', containerEl.clientHeight - top);
                }
            }
            const posY = dialog.offset().top - $(window).scrollTop();
            if (posY + height > windowHeight) {
                if (windowHeight - top < this.options.dialogOptions.minHeight &&
                    this.options.dialogOptions.minHeight <= windowHeight) {
                    dialog.css('top', windowHeight - this.options.dialogOptions.minHeight + containerEl.offsetTop);
                    dialog.css('height', this.options.dialogOptions.minHeight);
                    return;
                }
                dialog.css('height', windowHeight - posY);
                if (minHeight > windowHeight - posY) {
                    dialog.css('min-height', windowHeight - posY);
                }
            }
        },

        /**
         * Returns state of the dialog
         *
         * @returns {string}
         */
        getState: function() {
            return this.widget.dialog('state');
        },

        /**
         * Binds dialog window state events,
         * Transmits open/close/statechange events over system message bus
         *
         * @protected
         */
        _bindDialogEvents: function() {
            this.widget.on('dialogbeforeclose', () => {
                mediator.trigger('widget_dialog:close', this);
                this.trigger('close');
            });
            this.widget.on('dialogopen', () => {
                mediator.trigger('widget_dialog:open', this);
                this.trigger('open');
            });
            this.widget.on('dialogstatechange', (event, data) => {
                if (data.state !== data.oldState) {
                    mediator.trigger('widget_dialog:stateChange', this, data);
                    this.trigger('stateChange', data);
                }
            });
            this.widget.on({
                'dialogresizestart': this.onResizeStart.bind(this),
                'dialogresize dialogmaximize dialogrestore': this.onResize.bind(this),
                'dialogresizestop': this.onResizeStop.bind(this)
            });
        },

        onResizeStart: function(event) {
            this.widget.dialog('widget').addClass('ui-dialog-resized');
            this.$el.css({overflow: 'hidden'});
            this.forEachComponent(function(component) {
                component.trigger('parentResizeStart', event, this);
            });
        },

        onResize: function(event) {
            this.forEachComponent(function(component) {
                component.trigger('parentResize', event, this);
            });
        },

        onResizeStop: function(event) {
            this.$el.css({overflow: ''});
            this.forEachComponent(function(component) {
                component.trigger('parentResizeStop', event, this);
            });
        },

        widgetIsResizable: function() {
            return this.options.dialogOptions.resizable;
        },

        getPositionProps() {
            return {
                my: 'center center',
                at: this.defaultPos,
                of: this.getLimitToContainer(),
                collision: 'fit'
            };
        }
    });

    return DialogWidget;
});
