/*
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
define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const tools = require('oroui/js/tools');
    let config = require('module-config').default(module.id);
    require('jquery-ui/widgets/dialog');

    config = $.extend(true, {}, {
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
        closeText: __('Close'),
        btnCloseClass: 'close-dialog',
        btnCloseAriaText: __('oro.orowindows.dialog.close.aria_label'),
        btnCloseIcon: null
    }, config);

    $.widget( 'ui.dialog', $.ui.dialog, {
        version: '2.0.0',

        _limitToEl: null,

        _resizeTries: 0,

        options: $.extend($.ui.dialog.options, config),

        _allowInteraction: function(e) {
            return !!$(e.target).closest('.ui-dialog, .ui-datepicker, .select2-drop, .tox-dialog, ' +
                '.dropdown-menu').length;
        },

        _create: function() {
            this._super();
            this._verifySettings();

            this._initBottomLine();

            this.uiDialog.attr('data-skip-focus-decoration', '');

            this._onBackspacePress = this._onBackspacePress.bind(this);
            this._windowResizeHandler = this._windowResizeHandler.bind(this);

            // prevents history navigation over backspace while dialog is opened
            $(document).on('keydown.dialog', this._onBackspacePress);

            // Handle window resize
            $(window).on('resize.dialog', this._windowResizeHandler);
        },

        _limitTo: function() {
            if (this.options.limitTo === 'viewport') {
                return this._limitToEl = $(document.documentElement);
            } else if (this.options.limitTo) {
                return this._limitToEl = $(this.options.limitTo);
            }

            return this._limitToEl = this._appendTo();
        },

        _init: function() {
            this._super();

            // Init dialog extended
            this._initButtons();
            this._initializeContainer();
            this._initializeState(this.options.state);
        },

        _destroy: function() {
            this._super();

            // remove custom handler
            $(document).off('keydown.dialog', this._onBackspacePress);
            $(window).off('resize.dialog', this._windowResizeHandler);
        },

        _makeDraggable: function() {
            this._super();
            this.uiDialog.draggable('option', 'containment',
                this.options.limitTo === 'viewport' ? 'window' : this._limitTo());
        },

        close: function() {
            $(window).off('.dialog');
            this._removeMinimizedEl();

            this._super();
        },

        actionsContainer: function() {
            return this.uiDialogButtonPane;
        },

        showActionsContainer: function() {
            if (!this.uiDialogButtonPane.parent().length) {
                this.uiDialog.addClass('ui-dialog-buttons');
                this.uiDialogButtonPane.appendTo(this.uiDialog);
            }
        },

        state: function() {
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

        _minimize: function() {
            if (this.state() !== 'minimized') {
                this._normalize();
            }

            const widget = this.widget();

            this._trigger('beforeMinimize');
            this._saveSnapshot();
            this._setState('minimized');
            this._toggleButtons();
            this._trigger('minimize');
            widget.hide();

            this._getMinimizeTo().show();

            // Make copy of widget to disable dialog events
            this.minimizedEl = widget.clone();
            this.minimizedEl.css({
                height: 'auto'
            });
            this.minimizedEl.find('.ui-dialog-content').remove();
            this.minimizedEl.find('.ui-resizable-handle').remove();
            // Add title attribute to be able to view full window title
            const title = this.minimizedEl.find('.ui-dialog-title');
            title.disableSelection().attr('title', title.text());
            const self = this;
            this.minimizedEl.find('.ui-dialog-titlebar').on('dblclick', function() {
                self.uiDialogTitlebar.trigger('dblclick');
            });
            // Proxy events to original window
            const buttons = ['close', 'maximize', 'restore'];
            for (let i = 0; i < buttons.length; i++) {
                const btnClass = '.ui-dialog-titlebar-' + buttons[i];
                this.minimizedEl.find(btnClass).on('click',
                    function(btnClass) {
                        return function() {
                            widget.find(btnClass).trigger('click');
                            return false;
                        };
                    }(btnClass));
            }
            this.minimizedEl.show();
            this.minimizedEl.appendTo(this._getMinimizeTo());

            return this;
        },

        _collapse: function() {
            const newHeight = this._getTitleBarHeight();

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

        _maximize: function() {
            if (this.state() !== 'maximized') {
                this._normalize();
            }

            this._trigger('beforeMaximize');
            this._saveSnapshot();
            this._calculateNewMaximizedDimensions(function() {
                this._setState('maximized');
                this._toggleButtons();
                this._trigger('maximize');
            }.bind(this));

            return this;
        },

        _restore: function() {
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
                }).appendTo(document.body);
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
                const newHeight = this._getContainerHeight();
                const newWidth = this._limitTo().width();
                this._setOptions({
                    resizable: false,
                    draggable: false,
                    height: newHeight,
                    width: newWidth,
                    position: {
                        my: 'left top',
                        at: 'left top',
                        of: this._limitTo()
                    }
                });
                this.widget().css('position', _.isMobile() ? 'relative' : 'fixed'); // remove scroll when maximized
                if (typeof onResizeCallback === 'function') {
                    onResizeCallback();
                }
            } else {
                this._resizeTries++;
                if (this._resizeTries < 100) {
                    setTimeout(function() {
                        this._calculateNewMaximizedDimensions(onResizeCallback);
                    }.bind(this), 500);
                } else {
                    this._resizeTries = 0;
                }
            }

            return this;
        },

        _size: function() {
            const cssProperties = _.pick(this.options, ['width', 'height', 'maxWidth', 'minWidth']);
            this.uiDialog.css(cssProperties);
            if ( this.uiDialog.is( ':data(ui-resizable)' ) ) {
                this.uiDialog.resizable( 'option', 'minHeight', this._minHeight() );
            }
        },

        _moveToVisible: function() {
            const $widget = this.widget();
            if ($widget.length > 0) {
                const offset = $widget.offset();
                this._setOptions({
                    position: [offset.left, offset.top]
                });
            }
            return this;
        },

        _position: function() {
            // Need to show the dialog to get the actual offset in the position plugin
            const isVisible = this.uiDialog.is(':visible');
            const initialDisplay = this.uiDialog[0].style.display;
            if (!isVisible) {
                this.uiDialog.show();
            }
            this.uiDialog.position(this.options.position);
            if (!isVisible) {
                this.uiDialog.css('display', initialDisplay);
            }
        },

        _getTitleBarHeight: function() {
            return this.uiDialogTitlebar.height() + 15;
        },

        _getContainerHeight: function() {
            let heightDelta = 0;
            if (this.options.maximizedHeightDecreaseBy) {
                if (tools.isNumeric(this.options.maximizedHeightDecreaseBy)) {
                    heightDelta = this.options.maximizedHeightDecreaseBy;
                } else if (this.options.maximizedHeightDecreaseBy === 'minimize-bar') {
                    heightDelta = this._getMinimizeTo().height();
                } else {
                    heightDelta = $(this.maximizedHeightDecreaseBy).height();
                }
            }

            // Maximize window to container, or to viewport in case when container is higher
            const baseHeight = this._limitTo().height();
            const visibleHeight = this.bottomLine.offset().top - this._limitTo().offset().top;
            const currentHeight = baseHeight > visibleHeight ? visibleHeight : baseHeight;
            return currentHeight - heightDelta;
        },

        _initButtons: function(el) {
            const self = this;
            if (typeof el === 'undefined') {
                el = this;
            }
            // start operation on titlebar
            // create container for buttons
            const buttonPane = $('<div class="ui-dialog-titlebar-buttonpane"></div>').appendTo(this.uiDialogTitlebar);
            // move 'close' button to button-pane
            this._buttons = {};
            this.uiDialogTitlebarClose
                .addClass(this.options.btnCloseClass)
                // override some unwanted jquery-ui styles
                .css({
                    position: 'static',
                    top: 'auto',
                    right: 'auto'
                })
                .attr({
                    'title': this.options.closeText,
                    'aria-label': this.options.btnCloseAriaText
                })
                // change icon
                .find('.ui-icon').removeClass('ui-icon-closethick').addClass(this.options.icons.close).end()
                // move to button-pane
                .appendTo(buttonPane)
                .end();
            this.uiDialogTitlebarClose.find('.ui-button-icon, .ui-button-icon-space').attr('aria-hidden', true);

            if (this.options.btnCloseIcon) {
                this.uiDialogTitlebarClose.append(this.options.btnCloseIcon);
            }
            // append other buttons to button-pane
            const types = ['maximize', 'restore', 'minimize'];
            for (const key in types) {
                if (typeof types[key] === 'string') {
                    const type = types[key];
                    let button = this.options.icons[type];
                    if (typeof this.options.icons[type] === 'string') {
                        button = `
                        <a class="ui-dialog-titlebar-${type} ui-corner-all" href="#" title="${_.escape(__(type))}">
                            <span class="ui-icon ${this.options.icons[type]}">${type}</span>
                        </a>`;
                    } else {
                        button.addClass('ui-dialog-titlebar-' + type);
                    }
                    button = $(button);
                    button
                        .attr('role', 'button')
                        .on('mouseover', function() {
                            $(this).addClass('ui-state-hover');
                        })
                        .on('mouseout', function() {
                            $(this).removeClass('ui-state-hover');
                        })
                        .on('focus', function() {
                            $(this).addClass('ui-state-focus');
                        })
                        .on('blur', function() {
                            $(this).removeClass('ui-state-focus');
                        });
                    this._buttons[type] = button;
                    buttonPane.append(button);
                }
            }

            this.uiDialogTitlebarClose.toggle(this.options.allowClose);

            this._buttons.maximize
                .toggle(this.options.allowMaximize)
                .on('click', function(e) {
                    e.preventDefault();
                    self.maximize();
                });

            this._buttons.minimize
                .toggle(this.options.allowMinimize)
                .on('click', function(e) {
                    e.preventDefault();
                    self.minimize();
                });

            this._buttons.restore
                .hide()
                .on('click', function(e) {
                    e.preventDefault();
                    self.restore();
                });

            // other titlebar behaviors
            this.uiDialogTitlebar
                // on-dblclick-titlebar : maximize/minimize/collapse/restore
                .on('dblclick', function(evt) {
                    if (self.options.dblclick && self.options.dblclick.length) {
                        if (self.state() !== 'normal') {
                            self.restore();
                        } else {
                            self[self.options.dblclick]();
                        }
                    }
                })
                // avoid text-highlight when double-click
                .on('select', function() {
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

        _onBackspacePress: function(e) {
            // prevents history navigation over backspace while dialog is opened
            const exclude = ':button,:reset,:submit,:checkbox,:radio,select,[type=image],[type=file]';
            if (this._isOpen && e.keyCode === 8 && !$(e.target).not(exclude).is(':input, [contenteditable]')) {
                e.preventDefault();
            }
        },

        _createTitlebar: function() {
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

        _restoreFromCollapsed: function() {
            const original = this._loadSnapshot();
            // restore dialog
            this._setOptions({
                resizable: original.config.resizable,
                height: original.size.height - this._getTitleBarHeight(),
                maxHeight: original.size.maxHeight
            });

            return this;
        },

        _restoreFromMaximized: function() {
            const original = this._loadSnapshot();
            const widget = this.widget().get(0);
            const widgetCSS = {
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
                position: [original.position.left, original.position.top]
            });

            // adjust widget position
            this.widget().css(widgetCSS);

            return this;
        },

        _restoreFromMinimized: function() {
            this._removeMinimizedEl();
            this.widget().show();

            const original = this._loadSnapshot();

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
            const containerWidth = this._limitTo().width();
            if (left + width > containerWidth) {
                return containerWidth - width;
            }
            return left;
        },

        _getVisibleTop: function(top, height) {
            const visibleTop = this.bottomLine.offset().top;
            if (top + height > visibleTop) {
                return visibleTop - height;
            }
            return top;
        },

        _restoreWithoutTriggerEvent: function() {
            const beforeState = this.state();
            const method = '_restoreFrom' + beforeState.charAt(0).toUpperCase() + beforeState.slice(1);
            if (typeof this[method] === 'function') {
                this[method]();
            } else {
                $.error('jQuery.dialogExtend Error : Cannot restore dialog from unknown state "' + beforeState + '"');
            }

            return this;
        },

        _saveSnapshot: function() {
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
                position: this.widget().offset()
            };
        },

        _loadSnapshot: function() {
            return this.options.snapshot;
        },

        _setOption: function(key, value) {
            if (key === 'state') {
                this._initializeState(value);
            }

            this._superApply([key, value]);

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
            const appendTo = this._appendTo();
            if (appendTo.css('position') === 'static') {
                appendTo.css('position', 'relative');
            }
        },

        _setState: function(state) {
            const oldState = this.options.state;
            this.options.state = state;
            // toggle data state
            this.widget()
                .removeClass('ui-dialog-normal ui-dialog-maximized ui-dialog-minimized ui-dialog-collapsed')
                .addClass('ui-dialog-' + state);

            // Trigger state change event
            if (!this.disableStateChangeTrigger) {
                let snapshot = this._loadSnapshot();
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

        _toggleButtons: function() {
            // show or hide buttons & decide position
            this._buttons.maximize
                .toggle(this.state() !== 'maximized' && this.options.allowMaximize);

            this._buttons.minimize
                .toggle(this.state() !== 'minimized' && this.options.allowMinimize);

            this._buttons.restore
                .toggle(this.state() !== 'normal' && ( this.options.allowMaximize || this.options.allowMinimize ))
                .css({
                    right: this.state() === 'maximized'
                        ? '1.4em'
                        : this.state() === 'minimized' ? !this.options.allowMaximize ? '1.4em' : '2.5em' : '-9999em'
                });

            return this;
        },

        _verifySettings: function() {
            const self = this;
            const checkOption = function(option, options) {
                if (self.options[option] && options.indexOf(self.options[option]) === -1) {
                    $.error(`jQuery.dialogExtend Error : Invalid <${option}> value "${self.options[option]}"`);
                    self.options[option] = false;
                }
            };

            checkOption('dblclick', ['maximize', 'minimize', 'collapse']);
            checkOption('titlebar', ['transparent']);

            return this;
        }
    });
});
