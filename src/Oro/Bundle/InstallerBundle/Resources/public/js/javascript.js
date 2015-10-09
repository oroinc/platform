/* global $ */
$(function() {
    'use strict';

    $('.progress-bar li:last-child em.fix-bg').width($('.progress-bar li:last-child').width() / 2);
    $('.progress-bar li:first-child em.fix-bg').width($('.progress-bar li:first-child').width() / 2);

    if (window.localStorage && !localStorage.getItem('oroInstallSplash')) {
        localStorage.setItem('oroInstallSplash', true);
        $('body').addClass('start-box-open');
        $('#begin-install').click(function() {
            $('body').removeClass('start-box-open');
        });
    }
});
