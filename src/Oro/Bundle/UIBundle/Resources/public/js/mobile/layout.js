define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var pageHeader = require('oroui/js/mobile/page-header');
    require('oroui/js/mobile/side-menu');

    /**
     * Instantiate sideMenu widget
     */
    function initMainMenu() {
        var menu = $('#main-menu');
        menu.insertAfter($('#oroplatform-header'));
        menu.mobileSideMenu({
            toggleSelector: '#main-menu-toggle'
        });
    }

    /**
     * Fixes issue when header with position fixed loses its place after blur event on some input
     *
     * @see http://dansajin.com/2012/12/07/fix-position-fixed/
     * @see http://stackoverflow.com/questions/14492613/ios-ipad-fixed-position-breaks-when-keyboard-is-opened
     */
    function fixStickyHeader() {
        var elementsWithKeyboardSelector = 'input[type=text], input[type=number], textarea, [content-editable]';
        var $body = $('body');
        var forceHeaderLayoutUpdate = _.debounce(function() {
            $(document).scrollTop($(document).scrollTop());
            mediator.trigger('layout:headerStateChange');
        }, 1);
        $(document)
            .on('focus', elementsWithKeyboardSelector, function() {
                $body.addClass('input-focused');
                mediator.trigger('layout:headerStateChange');
            })
            .on('blur', elementsWithKeyboardSelector, function() {
                $body.removeClass('input-focused');
                forceHeaderLayoutUpdate();
            });
    }

    /**
     * Binds to dialog state change events and locks/unlocks page scroll
     */
    function initDialogStateTracker() {
        var dialogs = {};
        var modals = {};

        function scrollUpdate() {
            var $mainEl = $('#container').find('>:first-child');
            if (_.some(dialogs) || _.some(modals)) {
                mediator.execute('layout:disablePageScroll', $mainEl);
            } else {
                mediator.execute('layout:enablePageScroll');
            }

            // any dialog is opened -- hide page under dialog window (increases performance)
            if (_.some(dialogs)) {
                $('#page').addClass('hidden-page');
            } else {
                $('#page').removeClass('hidden-page');
            }

            // any modal is opened  -- prevent page scrolling under the modal window
            if (_.some(modals)) {
                $('html').addClass('lock-page-scroll');
            } else {
                $('html').removeClass('lock-page-scroll');
            }
        }

        function moveToTop(dialog) {
            var $dialog = dialog.widget.dialog('instance').uiDialog;
            $dialog.removeClass('ui-dialog-on-background').siblings('.ui-dialog').addClass('ui-dialog-on-background');
        }

        function removeFromTop(dialog) {
            var $dialog = dialog.widget.dialog('instance').uiDialog;
            $dialog.siblings('.ui-dialog:last').removeClass('ui-dialog-on-background');
        }

        mediator.on({
            // widget dialogs
            'widget_dialog:open': function(dialog) {
                dialogs[dialog.cid] = dialog.getState() !== 'minimized';
                moveToTop(dialog);
                scrollUpdate();
            },
            'widget_dialog:close': function(dialog) {
                delete dialogs[dialog.cid];
                removeFromTop(dialog);
                scrollUpdate();
            },
            'widget_dialog:stateChange': function(dialog) {
                dialogs[dialog.cid] = dialog.getState() !== 'minimized';
                scrollUpdate();
            },
            // modals
            'modal:open': function(modal) {
                modals[modal.cid] = true;
                scrollUpdate();
            },
            'modal:close': function(modal) {
                delete modals[modal.cid];
                scrollUpdate();
            }
        });
    }

    /**
     * Initiate mobile layout
     */
    function initLayout() {
        fixStickyHeader();
        initMainMenu();
        pageHeader.init();
        initDialogStateTracker();
    }

    /**
     * Initializes mobile layout
     *
     * @export oroui/js/mobile/layout
     * @name oro.mobile.layout
     */
    return {
        init: function() {
            $(initLayout);
        }
    };
});
