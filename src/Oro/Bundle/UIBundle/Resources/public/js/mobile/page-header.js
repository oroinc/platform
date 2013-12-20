/*jshint browser: true*/
/*jslint browser: true*/
/*global define*/
define(['jquery', 'oro/mediator', 'oro/translator'], function ($, mediator, __) {
    'use strict';

    function updateButtonsGroup($header) {
        var $group, $buttons, $moreButtons, $mainButtons,
            $container = $header.find('.title-buttons-container');
        $container.find('.separator-btn').replaceWith('<li class="divider"></li>');
        $buttons = $container.find('.btn, .divider');
        if ($buttons.length <= 1) {
            return;
        }
        $mainButtons = $buttons.filter('.main-group:not(.more-group)');
        if (!$mainButtons.length) {
            $mainButtons = $buttons.first();
        }
        $moreButtons = $buttons.not($mainButtons);
        if ($moreButtons.length > 1) {
            $moreButtons = $('<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">' +
                __('More') + '<span class="caret"></span></a>')
                    .add($('<ul class="dropdown-menu"></ul>').append($moreButtons));
            $moreButtons.find('.btn').wrap('<li></li>').removeClass(function (index, css) {
                return (css.match(/\bbtn(-\S+)?/g) || []).join(' ');
            });
        }
        $group = $('<div class="btn-group pull-right"></div>')
            .append($mainButtons, $moreButtons);
        $buttons.removeClass('pull-right');
        $container.find('.btn-group').remove().end().prepend($group);
    }

    function updatePageHeader() {
        var $el = $('.navigation.navbar-extra');
        updateButtonsGroup($el);
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
            mediator.on('hash_navigation_request:complete', updatePageHeader);
        }
    };
});
