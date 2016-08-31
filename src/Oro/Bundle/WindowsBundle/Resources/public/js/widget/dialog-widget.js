define(function(require) {
    'use strict';

    var DialogWidget;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var error = require('oroui/js/error');
    var messenger = require('oroui/js/messenger');
    var mediator = require('oroui/js/mediator');
    var layout = require('oroui/js/layout');
    var AbstractWidget = require('oroui/js/widget/abstract-widget');
    var StateModel = require('orowindows/js/dialog/state/model');
    var DialogManager = require('orowindows/js/widget/dialog-manager');
    var dialogManager = new DialogManager();
    require('jquery.dialog.extended');

    /**
     * @export  oro/dialog-widget
     * @class   oro.DialogWidget
     * @extends oroui.widget.AbstractWidget
     */
    DialogWidget = AbstractWidget.extend({
        options: _.extend({}, AbstractWidget.prototype.options, {
            type: 'dialog',
            dialogOptions: null,
            stateEnabled: true,
            incrementalPosition: true,
            preventModelRemoval: false
        }),

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
            'content:changed': 'resetDialogPosition'
        },

        listen: {
            'widgetRender': 'onWidgetRender',
            'widgetReady': 'onContentUpdated',
            'page:request mediator': 'onPageChange'
        },

        /**
         * Initialize dialog
         */
        initialize: function(options) {
            var dialogOptions;
            options = options || {};
            this.options = _.defaults(options, this.options);

            dialogOptions = options.dialogOptions = options.dialogOptions || {};
            _.defaults(dialogOptions, {
                title: options.title,
                limitTo: '#container',
                minWidth: 375,
                minHeight: 150
            });
            if (tools.isMobile()) {
                options.incrementalPosition = false;
                options.dialogOptions.limitTo = 'body';
            }

            // it's possible to track state only for not modal dialogs
            options.stateEnabled = options.stateEnabled && !dialogOptions.modal;
            if (options.stateEnabled) {
                this._initModel();
            }

            dialogOptions.beforeClose = _.bind(this.closeHandler, this, dialogOptions.close);
            dialogOptions.close = undefined;

            dialogManager.add(this);

            this.initializeWidget(options);
        },

        onWidgetRender: function(content) {
            this._initAdjustHeight(content);
            this._setMaxSize();
        },

        setTitle: function(title) {
            this.widget.dialog('option', 'title', title);
        },

        _initModel: function() {
            if (this.model) {
                this.restoreMode = true;
                var attributes = this.model.get('data');
                $.extend(true, this.options, attributes);
                if (this.options.el) {
                    this.setElement(this.options.el);
                } else if (this.model.get('id')) {
                    var restoredEl = $('#widget-restored-state-' + this.model.get('id'));
                    if (restoredEl.length) {
                        this.setElement(restoredEl);
                    }
                }
            } else {
                this.model = new StateModel();
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
            if (_.isFunction(onClose)) {
                onClose();
            }
            if (!this.keepAliveOnClose) {
                this.dispose();
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            dialogManager.remove(this);
            if (this.model && !this.options.preventModelRemoval) {
                this.model.destroy({
                    error: _.bind(function(model, xhr) {
                        // Suppress error if it's 404 response and not debug mode
                        if (xhr.status !== 404 || tools.debug) {
                            error.handle({}, xhr, {enforce: true});
                        }
                    }, this)
                });
            }
            this._hideLoading();

            // need to remove components in widget before DOM will be deleted
            this.disposePageComponents();
            _.invoke(this.subviews, 'dispose');

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
            this.$('form:first').focusFirstInput();
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
                DialogWidget.__super__._onContentLoadFail.apply(this, arguments);
            }
        },

        handleStateChange: function(e, data) {
            if (!this.options.stateEnabled) {
                return;
            }
            if (this.restoreMode) {
                this.restoreMode = false;
                return;
            }
            var saveData = _.omit(this.options, ['dialogOptions', 'el', 'model']);
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
         * @inheritDoc
         */
        getLayoutElement: function() {
            // covers not only widget body, but whole .ui-dialog, including .ui-dialog-buttonpane
            return this.widget.parent();
        },

        getActionsElement: function() {
            if (!this.actionsEl) {
                this.actionsEl = $('<div class="pull-right"/>').appendTo(
                    $('<div class="form-actions widget-actions"/>').appendTo(
                        this.widget.dialog('actionsContainer')
                    )
                );
            }
            return this.actionsEl;
        },

        _clearActionsContainer: function() {
            this.widget.dialog('actionsContainer').empty();
            this.actionsEl = null;
        },

        _renderActions: function() {
            DialogWidget.__super__._renderActions.apply(this);
            if (this.hasActions()) {
                this.widget.dialog('showActionsContainer');
            }
        },

        /**
         * Show dialog
         */
        show: function() {
            var dialogOptions;
            if (!this.widget) {
                dialogOptions = _.extend({}, this.options.dialogOptions);
                dialogOptions.stateChange = _.bind(this.handleStateChange, this);
                if (dialogOptions.state !== 'minimized') {
                    dialogOptions.dialogClass = 'invisible ' + (dialogOptions.dialogClass || '');
                }
                this.widget = $('<div/>');
                this._bindDialogEvents();
                this.widget.html(this.$el).dialog(dialogOptions);
                this.getLayoutElement().attr('data-layout', 'separate');
            } else {
                if (this.widget.dialog('instance') !== void 0 && !this.widget.dialog('isOpen')) {
                    this.widget.dialog('open');
                }
                this.widget.html(this.$el);
            }
            this.loadingElement = this.$el.closest('.ui-dialog');
            DialogWidget.__super__.show.apply(this);

            this._fixDialogMinHeight(true);
            this.widget.on('dialogmaximize dialogrestore', _.bind(function() {
                this._fixDialogMinHeight(true);
                this.widget.trigger('resize');
            }, this));
            this.widget.on('dialogminimize', _.bind(function() {
                this._fixDialogMinHeight(false);
                this.widget.trigger('resize');
            }, this));
        },

        hide: function() {
            // keepAliveOnClose property is used to avoid disposing the widget on dialog close to be able open it again
            var keepAliveOnClose = this.keepAliveOnClose;
            this.keepAliveOnClose = true;
            this.widget.dialog('close');
            this.keepAliveOnClose = keepAliveOnClose;
        },

        _renderHandler: function() {
            this.resetDialogPosition();
            this.widget.closest('.invisible').removeClass('invisible');
            this.trigger('widgetReady', this);
        },

        _initAdjustHeight: function(content) {
            this.widget.off('.adjust-height-events');
            var scrollableContent = content.find('.scrollable-container');
            var resizeEvents = [
                'dialogresize.adjust-height-events',
                'dialogmaximize.adjust-height-events',
                'dialogrestore.adjust-height-events'
            ].join(' ');
            if (scrollableContent.length) {
                scrollableContent.css('overflow', 'auto');
                this.widget.on(resizeEvents, _.bind(this._fixScrollableHeight, this));
                this._fixScrollableHeight();
            }
        },
        _setMaxSize: function() {
            this.widget.off('.set-max-size-events');
            this.widget.on('dialogresizestart.set-max-size-events', _.bind(function() {
                var dialog = this.widget.closest('.ui-dialog');
                var containerEl = $(this.options.dialogOptions.limitTo || document.body)[0];
                dialog.css({
                    maxWidth: containerEl.clientWidth,
                    maxHeight: containerEl.clientHeight
                });
            }, this));
        },

        _fixDialogMinHeight: function(isEnabled) {
            if (isEnabled) {
                var minHeight = this.options.dialogOptions.minHeight + this.widget.dialog('actionsContainer').height();
                this.widget.dialog('widget').css('min-height', minHeight);
            } else {
                this.widget.dialog('widget').css('min-height', 0);
            }
        },

        _fixScrollableHeight: function() {
            var widget = this.widget;
            if (!tools.isMobile()) {
                // on mobile devices without setting these properties modal dialogs cannot be scrolled
                widget.find('.scrollable-container').each(_.bind(function(i, el) {
                    var $el = $(el);
                    var height = widget.height() - $el.position().top;
                    if (height) {
                        $el.outerHeight(height);
                    }
                }, this));
            }
            layout.updateResponsiveLayout();
        },

        /**
         * Resets dialog position to default
         */
        resetDialogPosition: function() {
            if (this.options.position) {
                this.setPosition(_.extend(this.options.position, {
                    of: '#container',
                    collision: 'fit'
                }));
            }
            if (!this.options.incrementalPosition) {
                this.setPosition({
                    my: 'center center',
                    at: this.defaultPos,
                    of: '#container',
                    collision: 'fit'
                });
            } else {
                dialogManager.updateIncrementalPosition(this);
            }
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
            var dialog = this.widget.closest('.ui-dialog');
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
            var containerEl = $(this.options.dialogOptions.limitTo || document.body)[0];
            var dialog = this.widget.closest('.ui-dialog');
            this.internalSetDialogPosition(position, leftShift, topShift);
            this.leftAndWidthAdjustments(dialog, containerEl);
            this.topAndHeightAdjustments(dialog, containerEl);
            this.widget.trigger('dialogreposition');
        },

        leftAndWidthAdjustments: function(dialog, containerEl) {
            // containerEl.offsetLeft will only work if offsetParent is document.body
            var left = parseFloat(dialog.css('left')) - containerEl.offsetLeft;
            var width = parseFloat(dialog.css('width'));
            var minWidth = parseFloat(dialog.css('min-width'));
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
            }
        },

        topAndHeightAdjustments: function(dialog, containerEl) {
            // containerEl.offsetTop will only work if offsetParent is document.body
            var top = parseFloat(dialog.css('top')) - containerEl.offsetTop;
            var height = parseFloat(dialog.css('height'));
            var minHeight = parseFloat(dialog.css('min-height'));
            var windowHeight = parseFloat($(window).height());
            if (containerEl.clientHeight > windowHeight) {
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
            var posY = dialog.offset().top - $(window).scrollTop();
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
            var self = this;
            this.widget.on('dialogbeforeclose', function() {
                mediator.trigger('widget_dialog:close', self);
            });
            this.widget.on('dialogopen', function() {
                mediator.trigger('widget_dialog:open', self);
            });
            this.widget.on('dialogstatechange', function(event, data) {
                if (data.state !== data.oldState) {
                    mediator.trigger('widget_dialog:stateChange', self);
                }
            });
            this.widget.on({
                'dialogresizestart': _.bind(this.onResizeStart, this),
                'dialogresize dialogmaximize dialogrestore': _.bind(this.onResize, this),
                'dialogresizestop': _.bind(this.onResizeStop, this)
            });
        },

        onResizeStart: function(event) {
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
        }
    });

    return DialogWidget;
});
