/*global define*/
define(function (require) {
    'use strict';

    var DialogWidget,
        $ = require('jquery'),
        _ = require('underscore'),
        __= require('orotranslation/js/translator'),
        tools = require('oroui/js/tools'),
        error = require('oroui/js/error'),
        messenger = require('oroui/js/messenger'),
        mediator = require('oroui/js/mediator'),
        layout = require('oroui/js/layout'),
        AbstractWidget = require('oroui/js/widget/abstract-widget'),
        StateModel = require('orowindows/js/dialog/state/model');
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
            incrementalPosition: true
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
        /**
         * Flag if the widget is embedded to the page
         * (dialog has own life cycle)
         *
         * @type {boolean}
         */
        _isEmbedded: false,

        listen: {
            'adoptedFormResetClick': 'remove',
            'widgetRender': '_initAdjustHeight',
            'contentLoad': 'onContentUpdated',
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

            this.initializeWidget(options);
        },

        setTitle: function(title) {
            this.widget.dialog("option", "title", title);
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
        closeHandler: function (onClose) {
            if (_.isFunction(onClose)) {
                onClose();
            }
            this.dispose();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            if (this.model) {
                this.model.destroy({
                    error: _.bind(function(model, xhr) {
                        // Suppress error if it's 404 response and not debug mode
                        if (xhr.status != 404 || tools.debug) {
                            error.handle({}, xhr, {enforce: true});
                        }
                    }, this)
                });
            }
            this._hideLoading();

            // need to remove components in widget before DOM will be deleted
            this.disposePageComponents();
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
        isEmbedded: function () {
            // modal dialogs has same life cycle as embedded widgets
            return this._isEmbedded || this.options.dialogOptions.modal;
        },

        /**
         * Handles content load event and sets focus on first form input
         */
        onContentUpdated: function () {
            this.$('form:first').focusFirstInput();
        },

        /**
         * Handle content loading failure.
         * @private
         */
        _onContentLoadFail: function(jqxhr) {
            this.options.stateEnabled = false;
            if (jqxhr.status == 403) {
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
        onPageChange: function () {
            if (!this.options.stateEnabled) {
                this.remove();
            }
        },

        /**
         * Removes dialog widget
         */
        remove: function() {
            if (this.widget) {
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
        show: function () {
            var dialogOptions;
            if (!this.widget) {
                dialogOptions = _.extend({}, this.options.dialogOptions);
                if (typeof dialogOptions.position === 'undefined') {
                    dialogOptions.position = this._getWindowPlacement();
                }
                dialogOptions.stateChange = _.bind(this.handleStateChange, this);
                if (dialogOptions.state !== 'minimized') {
                    dialogOptions.dialogClass = 'invisible ' + (dialogOptions.dialogClass || '');
                }
                this.widget = $('<div/>');
                this._transmitDialogEvents(this.widget);
                this.widget.html(this.$el).dialog(dialogOptions);
                this.widget.attr('data-layout', 'separate');
            } else {
                this.widget.html(this.$el);
            }
            this.loadingElement = this.$el.closest('.ui-dialog');
            DialogWidget.__super__.show.apply(this);
            this.widget.dialog('adjustContentSize');

            this._fixDialogMinHeight(true);
            this.widget.on("dialogmaximize dialogrestore", _.bind(function() {
                this._fixDialogMinHeight(true);
                this.widget.trigger('resize');
            }, this));
            this.widget.on("dialogminimize", _.bind(function() {
                this._fixDialogMinHeight(false);
                this.widget.trigger('resize');
            }, this));

            this.widget.on("dialogresizestop", _.bind(this._fixBorderShifting, this));
        },

        _afterLayoutInit: function () {
            this.widget.closest('.invisible').removeClass('invisible');
            this.renderDeferred.resolve();
            delete this.renderDeferred;
        },

        _initAdjustHeight: function(content) {
            this.widget.off("dialogresize dialogmaximize dialogrestore", _.bind(this._fixScrollableHeight, this));
            var scrollableContent = content.find('.scrollable-container');
            if (scrollableContent.length) {
                scrollableContent.css('overflow', 'auto');
                this.widget.on("dialogresize dialogmaximize dialogrestore", _.bind(this._fixScrollableHeight, this));
                this._fixScrollableHeight();
            }
        },

        _fixDialogMinHeight: function(isEnabled) {
            if (isEnabled) {
                var minHeight = this.options.dialogOptions.minHeight + this.widget.dialog('actionsContainer').height();
                this.widget.dialog('widget').css('min-height', minHeight);
            } else {
                this.widget.dialog('widget').css('min-height', 0);
            }
        },

        _fixBorderShifting: function() {
            var dialogWidget = this.widget.dialog('widget');
            var widthShift
                = parseInt(dialogWidget.css('border-left-width')) + parseInt(dialogWidget.css('border-right-width'));
            var heightShift
                = parseInt(dialogWidget.css('border-top-width')) + parseInt(dialogWidget.css('border-bottom-width'));
            this.widget.width(this.widget.width() - widthShift);
            this.widget.height(this.widget.height() - heightShift);
            this._fixScrollableHeight();
        },

        _fixScrollableHeight: function() {
            var widget = this.widget;
            widget.find('.scrollable-container').each(_.bind(function(i, el){
                var $el = $(el);
                var height = widget.height() - $el.position().top;
                if (height) {
                    $el.outerHeight(height);
                }
            },this));
            layout.updateResponsiveLayout();
        },

        /**
         * Get next window position based
         *
         * @returns {{my: string, at: string, of: (*|jQuery|HTMLElement), within: (*|jQuery|HTMLElement)}}
         * @private
         */
        _getWindowPlacement: function() {
            if (!this.options.incrementalPosition) {
                return {
                    my: 'center center',
                    at: DialogWidget.prototype.defaultPos
                };
            }
            var offset = 'center+' + DialogWidget.prototype.windowX + ' center+' + DialogWidget.prototype.windowY;

            DialogWidget.prototype.openedWindows++;
            if (DialogWidget.prototype.openedWindows % DialogWidget.prototype.windowsPerRow === 0) {
                var rowNum = DialogWidget.prototype.openedWindows / DialogWidget.prototype.windowsPerRow;
                DialogWidget.prototype.windowX = rowNum * DialogWidget.prototype.windowsPerRow * DialogWidget.prototype.windowOffsetX;
                DialogWidget.prototype.windowY = 0;

            } else {
                DialogWidget.prototype.windowX += DialogWidget.prototype.windowOffsetX;
                DialogWidget.prototype.windowY += DialogWidget.prototype.windowOffsetY;
            }

            return {
                my: offset,
                at: DialogWidget.prototype.defaultPos
            };
        },

        /**
         * Transmits dialog window state events over system message bus
         *
         * @param {jQuery} $dialog
         * @protected
         */
        _transmitDialogEvents: function ($dialog) {
            var id = this.cid;
            function transmit(action, state) {
                mediator.trigger('widget_dialog:' + action, {
                    id: id,
                    state: state
                });
            }
            $dialog.on('dialogbeforeclose', function () {
                transmit('close', $dialog.dialog('state'));
            });
            $dialog.on('dialogopen', function () {
                transmit('open', $dialog.dialog('state'));
            });
            $dialog.on('dialogstatechange', function (event, data) {
                if (data.state === data.oldState) {
                    return;
                }
                transmit('stateChange', data.state);
            });
        }
    });

    return DialogWidget;
});
