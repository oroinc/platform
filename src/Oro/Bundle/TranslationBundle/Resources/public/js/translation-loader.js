define(function(require) {
    'use strict';

    var Translator = require('translator');

    try {
        Translator.fromJSON(require('text!oro/translations'));
    } catch (e) {}

    return require('orotranslation/js/translator');
});
