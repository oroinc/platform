define(function(require) {
    'use strict';

    var $ = require('jquery');
    var colorUtil = require('oroui/js/tools/color-util');

    colorUtil.configure({DARK: $('body').css('color')});
});
