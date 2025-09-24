const webdriver = require('webdriver');

function WebDriverInstance(baseBrowserDecorator, args, logger) {
    const log = logger.create('WebDriver');

    const config = args.config || {
        path: '/wd/hub',
        hostname: '127.0.0.1',
        port: 4444,
    };

    const spec = {
        headless: !!args.headless,
        platform: typeof args.platform === 'string' ? args.platform : 'ANY',
        tags: args.tags || [],
        testName: typeof args.testName === 'string' ? args.testName : 'Karma test',
        browserName: typeof args.browserName === 'string' ? args.browserName : null,
    };

    if (args.version) spec.version = args.version;

    if (!spec.browserName) {
        throw new Error('browserName is required!');
    }

    baseBrowserDecorator(this);
    this.name = spec.browserName + ' via Remote WebDriver';
    this.spec = spec;

    let client = null;
    let interval = null;

    this._start = async (url) => {
        log.debug(`Starting ${this.name} for url '${url}'`);
        log.debug('WebDriver config: ' + JSON.stringify(config));
        log.debug('Browser capabilities: ' + JSON.stringify(spec));

        const capabilities = {
            browserName: spec.browserName,
            browserVersion: spec.version,
            platformName: spec.platform,
        };

        if (args['goog:chromeOptions']) {
            capabilities['goog:chromeOptions'] = args['goog:chromeOptions'];
        }

        try {
            client = await webdriver.newSession({
                ...config,
                capabilities: {
                    alwaysMatch: capabilities,
                },
            });

            if (args.pseudoActivityInterval) {
                interval = setInterval(async () => {
                    log.debug('Imitate activity');
                    if (client) await client.getTitle();
                }, args.pseudoActivityInterval);
            }

            await client.navigateTo(String(url));
        } catch (error) {
            log.error('WebDriver command failed', { spec, error });
            this._done('failure');
        }
    };

    this.on('kill', async (done) => {
        log.info('Kill requested ' + spec.testName);
        if (interval) clearInterval(interval);

        try {
            if (client) await client.deleteSession();
            log.info('Killed ' + spec.testName);
        } catch (e) {
            log.error('Could not quit the webdriver connection:', e);
        }

        this._done();
        process.nextTick(done);
    });
}

WebDriverInstance.$inject = ['baseBrowserDecorator', 'args', 'logger'];

module.exports = {
    'launcher:Selenium4WebDriverLauncher': ['type', WebDriverInstance],
};