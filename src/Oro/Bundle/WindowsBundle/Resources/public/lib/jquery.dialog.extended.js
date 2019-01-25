/*!
 * jQuery Extended Dialog 2.0
 *
 * Copyright (c) 2013 Oro Inc
 * Inspired by DialogExtend Copyright (c) 2010 Shum Ting Hin http://code.google.com/p/jquery-dialogextend/
 *
 * Licensed under MIT
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Depends:
 *   jQuery 1.7.2
 *   jQuery UI Dialog 1.10.2
 *
 */
define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/tools', 'jquery-ui'], function ($, _, __, tools) {
    'use strict';
    $.widget( 'ui.dialog', $.ui.dialog, {
        version: '2.0.0',

        _limitToEl: false,

        _resizeTries: 0,

        options: $.extend($.ui.dialog.options, {
            minimizeTo: false,
            maximizedHeightDecreaseBy: false,
            allowClose: true,
            allowMaximize: false,
            allowMinimize: false,
            dblclick: false,
            titlebar: false,
            icons: {
                close: 'ui-icon-closethick',
                maximize: 'ui-icon-extlink',
                minimize: 'ui-icon-minus',
                restore: 'ui-icon-newwin'
            },
            snapshot: null,
            state: 'normal',
            // Events
            beforeCollapse: null,
            beforeMaximize: null,
            beforeMinimize: null,
            beforeRestore: null,
            collapse: null,
            maximize: null,
            minimize: null,
            restore: null,
            closeText: __('Close')
        }),

        _allowInteraction: function(e) {
            return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop, .mce-window, ' +
                '.dropdown-menu, .ui-multiselect-menu').length;
        },

        _create: function () {
            this._super();

            this._verifySettings();
            this._initBottomLine();

            // inner-wrapper fixes max-height for flex container in IE11 https://jsfiddle.net/d158647x/
            this.uiDialog.wrapInner('<div class="ui-dialog-inner-wrapper"></div>');

            this._onBackspacePress = $.proxy(this._onBackspacePress, this);
            this._windowResizeHandler = $.proxy(this._windowResizeHandler, this);

            // prevents history navigation over backspace while dialog is opened
            $(document).bind('keydown.dialog', this._onBackspacePress);

            // Handle window resize
            $(window).bind('resize.dialog', this._windowResizeHandler);
        },

        _limitTo: function() {
            if (false === this._limitToEl) {
                this._limitToEl = this.options.limitTo ? $(this.options.limitTo) : this._appendTo();
            }

            return this._limitToEl;
        },

        _init: function() {
            this._super();

            // Init dialog extended
            this._initButtons();
            this._initializeContainer();
            this._initializeState(this.options.state);
        },

        _destroy: function () {
            this._super();

            // remove custom handler
            $(document).unbind('keydown.dialog', this._onBackspacePress);
            $(window).unbind('resize.dialog', this._windowResizeHandler);

            // @TODO: Remove this fix when Apple fix caret placement bug
            this.iOScaretFixer(false);
        },

        _makeDraggable: function() {
            this._super();
            this.uiDialog.draggable('option', 'containment', this.options.limitTo || 'parent');
        },

        open: function() {
            this._super();

            // @TODO: Remove this fix when Apple fix caret placement bug
            this.iOScaretFixer(true);
        },

        close: function() {
            $(window).unbind('.dialog');
            this._removeMinimizedEl();

            this._super();

            // @TODO: Remove this fix when Apple fix caret placement bug
            this.iOScaretFixer(false);
        },

        /**
         * Fix iOS11 bug when the caret is placed outside the input field
         * @TODO: Remove this fix when Apple fix caret placement bug
         * @param enabled
         */
        iOScaretFixer: function(enabled) {
            if (_.isMobile() && tools.isIOS()) {
                if (enabled) {
                    this.scrollTopPosition = $(document).scrollTop();
                } else {
                    if (this.scrollTopPosition) {
                        $(document).scrollTop(this.scrollTopPosition);
                    }
                }

                $('body').toggleClass('iosBugFixCaret', enabled)
                    .css('top', enabled ? -this.scrollTopPosition : '');
            }
        },

        actionsContainer: function() {
            return this.uiDialogButtonPane;
        },

        showActionsContainer: function() {
            if (!this.uiDialogButtonPane.parent().length) {
                this.uiDialog.addClass('ui-dialog-buttons');
                this.uiDialogButtonPane.appendTo(this.uiDialog.find('.ui-dialog-inner-wrapper'));
            }
        },

        state: function () {
            return this.options.state;
        },

        minimize: function() {
            this._setOption('state', 'minimized');
        },

        maximize: function() {
            this._setOption('state', 'maximized');
        },

        collapse: function() {
            this._setOption('state', 'collapsed');
        },

        restore: function() {
            this._setOption('state', 'normal');
        },

        _minimize: function () {
            if (this.state() !== 'minimized') {
                this._normalize();
            }

            var widget = this.widget();

            this._trigger('beforeMinimize');
            this._saveSnapshot();
            this._setState('minimized');
            this._toggleButtons();
            this._trigger('minimize');
            widget.hide();

            this._getMinimizeTo().show();

            // Make copy of widget to disable dialog events
            this.minimizedEl = widget.clone();
            this.minimizedEl.css({'height': 'auto'});
            this.minimizedEl.find('.ui-dialog-content').remove();
            this.minimizedEl.find('.ui-resizable-handle').remove();
            // Add title attribute to be able to view full window title
            var title = this.minimizedEl.find('.ui-dialog-title');
            title.disableSelection().attr('title', title.text());
            var self = this;
            this.minimizedEl.find('.ui-dialog-titlebar').dblclick(function() {
                self.uiDialogTitlebar.dblclick();
            });
            // Proxy events to original window
            var buttons = ['close', 'maximize', 'restore'];
            for (var i = 0; i < buttons.length; i++) {
                var btnClass = '.ui-dialog-titlebar-' + buttons[i];
                this.minimizedEl.find(btnClass).click(
                    function(btnClass) {
                        return function() {
                            widget.find(btnClass).click();
                            return false;
                        }
                    }(btnClass));
            }
            this.minimizedEl.show();
            this.minimizedEl.appendTo(this._getMinimizeTo());

            return this;
        },

        _collapse: function () {
            var newHeight = this._getTitleBarHeight();

            this._trigger('beforeCollapse');
            this._saveSnapshot();
            // modify dialog size (after hiding content)
            this._setOptions({
                resizable: false,
                height: newHeight,
                maxHeight: newHeight
            });
            // mark new state
            this._setState('collapsed');
            // trigger custom event
            this._trigger('collapse');

            return this;
        },

        _maximize: function () {
            if (this.state() !== 'maximized') {
                this._normalize();
            }

            this._trigger('beforeMaximize');
            this._saveSnapshot();
            this._calculateNewMaximizedDimensions($.proxy(function() {
                this._setState('maximized');
                this._toggleButtons();
                this._trigger('maximize');
            }, this));

            return this;
        },

        _restore: function () {
            this._trigger('beforeRestore');
            // restore to normal
            this._restoreWithoutTriggerEvent();
            this._setState('normal');
            this._toggleButtons();
            this._trigger('restore');

            return this;
        },

        _normalize: function() {
            if (this.state() !== 'normal') {
                this.disableStateChangeTrigger = true;
                this._setOption('state', 'normal');
                this.disableStateChangeTrigger = false;
            }
        },

        _initBottomLine: function() {
            this.bottomLine = $('#dialog-extend-parent-bottom');
            if (!this.bottomLine.length) {
                this.bottomLine = $('<div id="dialog-extend-parent-bottom"></div>');
                this.bottomLine.css({
                    position: 'fixed',
                    bottom: 0,
                    left: 0
                })
                .appendTo(document.body);
            }
            return this;
        },

        _initializeMinimizeContainer: function() {
            this.options.minimizeTo = $('#dialog-extend-fixed-container');
            if (!this.options.minimizeTo.length) {
                this.options.minimizeTo = $('<div id="dialog-extend-fixed-container"></div>');
                this.options.minimizeTo.addClass('ui-dialog-minimize-container');
                this.options.minimizeTo
                .css({
                    position: _.isMobile() ? 'relative' : 'fixed',
                    bottom: 1,
                    left: this._limitTo().offset().left,
                    zIndex: 9999
                })
                .hide()
                .appendTo(this._appendTo());
            }
        },

        _getMinimizeTo: function() {
            if (this.options.minimizeTo === false) {
                this._initializeMinimizeContainer();
            }
            return $(this.options.minimizeTo);
        },

        _calculateNewMaximizedDimensions: function(onResizeCallback) {
            if (this._limitTo().is(':visible')) {
                this._resizeTries = 0;
                var newHeight = this._getContainerHeight();
                var newWidth = this._limitTo().width();
                this._setOptions({
                    resizable: false,
                    draggable : false,
                    height: newHeight,
                    width: newWidth,
                    position: {
                        my: 'left top',
                        at: 'left top',
                        of: this._limitTo()
                    }
                });
                this.widget().css('position', _.isMobile() ? 'relative' : 'fixed'); // remove scroll when maximized
                if ($.isFunction(onResizeCallback)) {
                    onResizeCallback();
                }
            } else {
                this._resizeTries++;
                if (this._resizeTries < 100) {
                    setTimeout($.proxy(function() {this._calculateNewMaximizedDimensions(onResizeCallback);}, this), 500);
                } else {
                    this._resizeTries = 0;
                }
            }

            return this;
        },

        _size: function() {
            var cssProperties = _.pick(this.options, ['width', 'height', 'maxWidth', 'minWidth']);
            this.uiDialog.css(cssProperties);
            if ( this.uiDialog.is( ':data(ui-resizable)' ) ) {
                this.uiDialog.resizable( 'option', 'minHeight', this._minHeight() );
            }
        },

        _moveToVisible: function() {
            var $widget = this.widget();
            if ($widget.length > 0) {
                var offset = $widget.offset();
                this._setOptions({
                    position: [offset.left, offset.top]
                });
            }
            return this;
        },

        _getTitleBarHeight: function() {
            return this.uiDialogTitlebar.height() + 15;
        },

        _getContainerHeight: function() {
            var heightDelta = 0;
            if (this.options.maximizedHeightDecreaseBy) {
                if ($.isNumeric(this.options.maximizedHeightDecreaseBy)) {
                    heightDelta = this.options.maximizedHeightDecreaseBy;
                } else if (this.options.maximizedHeightDecreaseBy === 'minimize-bar') {
                    heightDelta = this._getMinimizeTo().height();
                } else {
                    heightDelta = $(this.maximizedHeightDecreaseBy).height();
                }
            }

            // Maximize window to container, or to viewport in case when container is higher
            var baseHeight = this._limitTo().height();
            var visibleHeight = this.bottomLine.offset().top - this._limitTo().offset().top;
            var currentHeight = baseHeight > visibleHeight ? visibleHeight : baseHeight;
            return currentHeight - heightDelta;
        },

        _initButtons: function (el) {
            var self = this;
            if (typeof el === 'undefined') {
                el = this;
            }
            // start operation on titlebar
            // create container for buttons
            var buttonPane = $('<div class="ui-dialog-titlebar-buttonpane"></div>').appendTo(this.uiDialogTitlebar);
            // move 'close' button to button-pane
            this._buttons = {};
            this.uiDialogTitlebarClose
            // override some unwanted jquery-ui styles
            .css({ 'position': 'static', 'top': 'auto', 'right': 'auto', 'margin': 0 })
            .attr('title', this.options.closeText)
            // change icon
            .find('.ui-icon').removeClass('ui-icon-closethick').addClass(this.options.icons.close).end()
            // move to button-pane
            .appendTo(buttonPane)
            .end();
            // append other buttons to button-pane
            var types =  ['maximize', 'restore', 'minimize'];
            for (var key in types) {
                if (typeof types[key] === 'string') {
                    var type = types[key];
                    var button = this.options.icons[type];
                    if (typeof this.options.icons[type] === 'string') {
                        button = '<a class="ui-dialog-titlebar-' + type + ' ui-corner-all" href="#" title="' + __(type)+ '"><span class="ui-icon ' + this.options.icons[type] + '">' + type + '</span></a>';

                    } else {
                        button.addClass('ui-dialog-titlebar-' + type);
                    }
                    button = $(button);
                    button
                    .attr('role', 'button')
                    .mouseover(function() {
                        $(this).addClass('ui-state-hover');
                    })
                    .mouseout(function() {
                        $(this).removeClass('ui-state-hover');
                    })
                    .focus(function() {
                        $(this).addClass('ui-state-focus');
                    })
                    .blur(function() {
                        $(this).removeClass('ui-state-focus');
                    });
                    this._buttons[type] = button;
                    buttonPane.append(button);
                }
            }

            this.uiDialogTitlebarClose.toggle(this.options.allowClose);

            this._buttons.maximize
            .toggle(this.options.allowMaximize)
            .click(function (e) {
                e.preventDefault();
                self.maximize();
            });

            this._buttons.minimize
            .toggle(this.options.allowMinimize)
            .click(function (e) {
                e.preventDefault();
                self.minimize();
            });

            this._buttons.restore
            .hide()
            .click(function (e) {
                e.preventDefault();
                self.restore();
            });

            // other titlebar behaviors
            this.uiDialogTitlebar
            // on-dblclick-titlebar : maximize/minimize/collapse/restore
            .dblclick(function (evt) {
                if (self.options.dblclick && self.options.dblclick.length) {
                    if (self.state() !== 'normal') {
                        self.restore();
                    } else {
                        self[self.options.dblclick]();
                    }
                }
            })
            // avoid text-highlight when double-click
            .select(function () {
                return false;
            });

            return this;
        },

        _windowResizeHandler: function(e) {
            if (e.target === window) {
                switch (this.state()) {
                    case 'maximized':
                        this._calculateNewMaximizedDimensions();
                        break;
                    case 'normal':
                        this._moveToVisible();
                        break;
                }
            }
        },

        _onBackspacePress: function (e) {
            // prevents history navigation over backspace while dialog is opened
            var exclude = ':button,:reset,:submit,:checkbox,:radio,select,[type=image],[type=file]';
            if (this._isOpen && e.keyCode === 8 && !$(e.target).not(exclude).is(':input, [contenteditable]')) {
                e.preventDefault();
            }
        },

        _createTitlebar: function () {
            this._super();
            this.uiDialogTitlebar.disableSelection();

            // modify title bar
            switch (this.options.titlebar) {
                case false:
                    // do nothing
                    break;
                case 'transparent':
                    // remove title style
                    this.uiDialogTitlebar
                    .css({
                        'background-color': 'transparent',
                        'background-image': 'none',
                        'border': 0
                    });
                    break;
                default:
                    $.error('jQuery.dialogExtend Error : Invalid <titlebar> value "' + this.options.titlebar + '"');
            }

            return this;
        },

        _restoreFromNormal: function() {
            return this;
        },

        _restoreFromCollapsed: function () {
            var original = this._loadSnapshot();
            // restore dialog
            this._setOptions({
                resizable: original.config.resizable,
                height: original.size.height - this._getTitleBarHeight(),
                maxHeight: original.size.maxHeight
            });

            return this;
        },

        _restoreFromMaximized: function () {
            var original = this._loadSnapshot(),
                widget = this.widget().get(0),
                widgetCSS = {
                    'min-height': widget.style.minHeight,
                    'border': widget.style.border,
                    'position': _.isMobile() ? 'relative' : 'fixed',
                    'left': this._getVisibleLeft(original.position.left, original.size.width),
                    'top': this._getVisibleTop(original.position.top, original.size.height)
                };
            // reset css props of widget to correct calculation non-content height in jquery-ui code
            this.widget().css({'min-height': '0', 'border': '0 none'});

            // restore dialog
            this._setOptions({
                resizable: original.config.resizable,
                draggable: original.config.draggable,
                height: original.size.height,
                width: original.size.width,
                maxHeight: original.size.maxHeight,
                position: [ original.position.left, original.position.top ]
            });

            // adjust widget position
            this.widget().css(widgetCSS);

            return this;
        },

        _restoreFromMinimized: function () {
            this._removeMinimizedEl();
            this.widget().show();

            var original = this._loadSnapshot();

            // Calculate position to be visible after maximize
            this.widget().css({
                position: _.isMobile() ? 'relative' : 'fixed',
                left: this._getVisibleLeft(original.position.left, original.size.width),
                top: this._getVisibleTop(original.position.top, original.size.height)
            });

            return this;
        },

        _removeMinimizedEl: function() {
            if (this.minimizedEl) {
                this.minimizedEl.remove();
            }
        },

        _getVisibleLeft: function(left, width) {
            var containerWidth = this._limitTo().width();
            if (left + width > containerWidth) {
                return containerWidth - width;
            }
            return left;
        },

        _getVisibleTop: function(top, height) {
            var visibleTop = this.bottomLine.offset().top;
            if (top + height > visibleTop) {
                return visibleTop - height;
            }
            return top;
        },

        _restoreWithoutTriggerEvent: function () {
            var beforeState = this.state();
            var method = '_restoreFrom' + beforeState.charAt(0).toUpperCase() + beforeState.slice(1);
            if ($.isFunction(this[method])) {
                this[method]();
            } else {
                $.error('jQuery.dialogExtend Error : Cannot restore dialog from unknown state "' + beforeState + '"');
            }

            return this;
        },

        _saveSnapshot: function () {
            // remember all configs under normal state
            if (this.state() === 'normal') {
                this._setOption('snapshot', this.snapshot());
            }

            return this;
        },

        snapshot: function() {
            return {
                config: {
                    resizable: this.options.resizable,
                    draggable: this.options.draggable
                },
                size: {
                    height: this.widget().height(),
                    width: this.options.width,
                    maxHeight: this.options.maxHeight
                },
                'position': this.widget().offset()
            };
        },

        _loadSnapshot: function() {
            return this.options.snapshot;
        },

        _setOption: function(key, value) {
            if (key === 'state') {
                this._initializeState(value);
            }

            this._superApply(arguments);

            if (key === 'appendTo') {
                this._initializeContainer();
            }
        },

        _initializeState: function(state) {
            if (!this.widget().hasClass('ui-dialog-' + state)) {
                switch (state) {
                    case 'maximized':
                        this._maximize();
                        break;
                    case 'minimized':
                        this._minimize();
                        break;
                    case 'collapsed':
                        this._collapse();
                        break;
                    default:
                        this._restore();
                }
            }
        },

        _initializeContainer: function() {
            // Fix parent position
            var appendTo = this._appendTo();
            if (appendTo.css('position') === 'static') {
                appendTo.css('position', 'relative');
            }
        },

        _setState: function (state) {
            var oldState = this.options.state;
            this.options.state = state;
            // toggle data state
            this.widget()
            .removeClass('ui-dialog-normal ui-dialog-maximized ui-dialog-minimized ui-dialog-collapsed')
            .addClass('ui-dialog-' + state);

            // Trigger state change event
            if (!this.disableStateChangeTrigger) {
                var snapshot = this._loadSnapshot();
                if (!snapshot && this.state() === 'normal') {
                    snapshot = this.snapshot();
                }
                this._trigger('stateChange', null, {
                    state: this.state(),
                    oldState: oldState,
                    snapshot: snapshot
                });
            }

            return this;
        },

        _toggleButtons: function () {
            // show or hide buttons & decide position
            this._buttons.maximize
            .toggle(this.state() !== 'maximized' && this.options.allowMaximize);

            this._buttons.minimize
            .toggle(this.state() !== 'minimized' && this.options.allowMinimize);

            this._buttons.restore
            .toggle(this.state() !== 'normal' && ( this.options.allowMaximize || this.options.allowMinimize ))
            .css({ 'right': this.state() === 'maximized' ? '1.4em' : this.state() === 'minimized' ? !this.options.allowMaximize ? '1.4em' : '2.5em' : '-9999em' });

            return this;
        },

        _verifySettings: function () {
            var self = this;
            var checkOption = function(option, options) {
                if (self.options[option] && options.indexOf(self.options[option]) === -1) {
                    $.error('jQuery.dialogExtend Error : Invalid <' + option + '> value "' + self.options[option] + '"');
                    self.options[option] = false;
                }
            };

            checkOption('dblclick', ['maximize', 'minimize', 'collapse']);
            checkOption('titlebar', ['transparent']);

            return this;
        }
    });
});
