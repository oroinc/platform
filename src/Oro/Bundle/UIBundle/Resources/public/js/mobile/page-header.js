/*jshint browser: true*/
/*jslint browser: true*/
/*global define*/
define(['jquery', 'oro/mediator'], function ($, mediator) {
    'use strict';

    function joinButtonsGroup($header) {
        var group,
            groups = $header.find('.separator-btn').parent().nextAll('.btn-group');
        if (!groups.length) {
            groups = $header.find('.btn-group');
        }
        group = $(groups.get(0));
        groups.not(group).each(function () {
            var $el = $(this);
            group.append($el.children());
        }).remove();
    }

    function updatePageHeader() {
        var $el = $('.navigation.navbar-extra');
        joinButtonsGroup($el);
    }

    /**
     * Initializes mobile layout for page-header
     *
     * @export oro/mobile/page-header
     * @name oro.mobile.pageHeader
     */
    return {
        init: function () {
            updatePageHeader();
            mediator.on('hash_navigation_request:refresh', updatePageHeader);
        }
    };
});
