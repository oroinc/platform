import routes from 'oroui/js/app/routes';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

if ('publicPath' in config) {
    // eslint-disable-next-line no-undef, camelcase
    __webpack_public_path__ = config.publicPath;
}

if (!window.sleep) {
    window.sleep = async function(duration) {
        return new Promise(resolve => setTimeout(() => resolve(0), duration));
    };
}

// Add loadModules to global scope for inline scripts
window.loadModules = await import('oroui/js/app/services/load-modules').then(module => module.default);

const domReady = new Promise(resolve => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', resolve);
    } else {
        resolve();
    }
});

await Promise.all([
    import('oronavigation/js/routes-loader'),
    import('orotranslation/js/translation-loader')
]);

const promises = await import('app-modules').then(m => m.default ?? m);

promises.push(domReady);

await Promise.all(promises);

const options = {
    ...config,
    // load routers
    routes(match) {
        routes.forEach(route => {
            match(...route);
        });
    },
    // define template for page title
    titleTemplate(data) {
        return data.subtitle || '';
    }
};

const {default: Application} = await import('oroui/js/app/application');

export default new Application(options);
