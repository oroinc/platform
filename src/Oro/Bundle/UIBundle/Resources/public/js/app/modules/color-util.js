define(function(require) {
    'use strict';

    const $ = require('jquery');
    const colorUtil = require('oroui/js/tools/color-util');

    colorUtil.configure({DARK: $('body').css('color')});
});
