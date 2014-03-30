/*jshint browser: true*/
/*jslint browser: true*/
/*global define*/
define(function (require) {
    'use strict';
    var $ = require('jquery'),
        pageHeader = require('oroui/js/mobile/page-header');
    require('oroui/js/mobile/side-menu');

    /**
     * Instantiate sideMenu widget
     */
    function initMainMenu() {
        var menu = $('#main-menu');
        menu.insertAfter($('#oroplatform-header'));
        menu.sideMenu({
            toggleSelector: '#main-menu-toggle'
        });
    }

    /**
     * Initiate mobile layout
     */
    function initLayout() {
        initMainMenu();
        pageHeader.init();
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
