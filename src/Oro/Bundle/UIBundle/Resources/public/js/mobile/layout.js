/*jshint browser: true*/
/*jslint browser: true*/
/*global define*/
define(function (require) {
    'use strict';
    var $ = require('jquery');
    require('oro/mobile/side-menu');

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
    }

    /**
     * Initializes mobile layout
     *
     * @export oro/mobile/layout
     * @name oro.mobile.layout
     */
    return {
        init: function () {
            $(initLayout);
        }
    };
});
