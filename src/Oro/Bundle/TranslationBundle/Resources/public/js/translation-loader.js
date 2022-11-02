define(function(require, exports, module) {
    'use strict';

    const Translator = require('orotranslation/lib/translator');
    const config = require('module-config').default(module.id);

    return fetch(config.translationsResources)
        .then(function(response) {
            return response.json();
        })
        .then(function(translations) {
            Translator.fromJSON(translations);
            return Translator;
        })
        .catch(function() {
            throw new Error('Unable to load translations from "' + config.translationsResources + '"');
        });
});
