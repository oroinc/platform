const config = require('module-config').default(module.id);

if ('publicPath' in config) {
    // eslint-disable-next-line no-undef, camelcase
    __webpack_public_path__ = config.publicPath;
}

module.exports = Promise.all(
    require('./polyfills').default
).then(() => Promise.all([
    require('oronavigation/js/routes-loader'),
    require('orotranslation/js/translation-loader')
])).then(() => {
    const $ = require('jquery');
    const _ = require('underscore');
    const Application = require('oroui/js/app/application');
    const routes = require('oroui/js/app/routes');
    const promises = require('app-modules').default;
    promises.push($.when($.ready));

    return Promise.all(promises).then(() => {
        const options = _.extend({}, config, {
            // load routers
            routes: function(match) {
                let i;
                for (i = 0; i < routes.length; i += 1) {
                    match(routes[i][0], routes[i][1]);
                }
            },
            // define template for page title
            titleTemplate: function(data) {
                return data.subtitle || '';
            }
        });
        return new Application(options);
    });
});
