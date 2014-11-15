/*jslint nomen: true*/
/*global define*/
define(['underscore', 'jquery.simplecolorpicker'
    ], function (_) {
    'use strict';

    return function (options) {
        options._sourceElement.simplecolorpicker(_.defaults(_.omit(options, ['_sourceElement']), {
            emptyColor: '#FFFFFF'
        }));
    };
});
