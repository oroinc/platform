define(function(require) {
    'use strict';

    var Translator = require('translator');

    Translator.fromJSON(require('text!oro/translations'));

    return require('orotranslation/js/translator');
});
