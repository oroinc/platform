/* global $ */
$(function() {
    'use strict';

    $('.progress-bar li:last-child em.fix-bg').width($('.progress-bar li:last-child').width() / 2);
    $('.progress-bar li:first-child em.fix-bg').width($('.progress-bar li:first-child').width() / 2);

    var $body = $('body');

    if (window.localStorage && !localStorage.getItem('oroInstallSplash')) {
        $body.addClass('start-box-open');
        localStorage.setItem('oroInstallSplash', true);
    }

    $('#begin-install').click(function() {
        $body.removeClass('start-box-open');
    });
});
