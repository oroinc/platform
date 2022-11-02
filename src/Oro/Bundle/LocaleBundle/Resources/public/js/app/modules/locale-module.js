import localeSettings from 'orolocale/js/locale-settings';

// google api configuration, set language for Google+ widgets
window.___gcfg = Object.assign(window.___gcfg || {}, {
    lang: localeSettings.getLocale()
});
