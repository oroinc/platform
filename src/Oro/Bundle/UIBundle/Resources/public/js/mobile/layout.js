/*jshint browser: true*/
/*jslint browser: true*/
/*global define*/
define(function (require) {
    'use strict';
    var $ = require('jquery'),
        _ = require('underscore'),
        mediator = require('oroui/js/mediator'),
        pageHeader = require('oroui/js/mobile/page-header');
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
        var $body, forceHeaderLayoutUpdate;
        $body = $('body');
        forceHeaderLayoutUpdate = _.debounce(function () {
            $(document).scrollTop($(document).scrollTop());
        }, 1);
        $(document)
            .on('focus', ':input', function () {
                $body.addClass('input-focused');
            })
            .on('blur', ':input', function () {
                $body.removeClass('input-focused');
                forceHeaderLayoutUpdate();
            });
    }

    /**
     * Binds to dialog state change events and locks/unlocks page scroll
     */
    function initDialogStateTracker() {
        var  dialogs = {};

        function scrollUpdate() {
            var $mainEl = $('#container').find('>:first-child');
            if (_.some(dialogs)) {
                mediator.execute('layout:disablePageScroll', $mainEl);
            } else {
                mediator.execute('layout:enablePageScroll', $mainEl);
            }
        }

        mediator.on('widget_dialog:open', function (dialog) {
            dialogs[dialog.cid] = dialog.getState() !== 'minimized';
            scrollUpdate();
        });
        mediator.on('widget_dialog:close', function (dialog) {
            delete dialogs[dialog.cid];
            scrollUpdate();
        });
        mediator.on('widget_dialog:stateChange', function (dialog) {
            dialogs[dialog.cid] = dialog.getState() !== 'minimized';
            scrollUpdate();
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
        init: function () {
            $(initLayout);
        }
    };
});
