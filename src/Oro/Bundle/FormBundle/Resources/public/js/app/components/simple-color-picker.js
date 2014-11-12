/*jslint nomen: true*/
/*global define*/
define(['jquery', 'underscore', 'jquery.simplecolorpicker'
    ], function ($, _) {
    'use strict';

    return function (options) {
        $(options.el).simplecolorpicker(_.omit(options, ['el']));
    };
});
