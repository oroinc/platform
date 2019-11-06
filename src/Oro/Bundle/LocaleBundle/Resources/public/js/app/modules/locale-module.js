define(function(require) {
    'use strict';

    const _ = require('underscore');
    const localeSettings = require('orolocale/js/locale-settings');

    // google api configuration, set language for Google+ widgets
    window.___gcfg = _.extend(window.___gcfg || {}, {
        lang: localeSettings.getLocale()
    });
});
