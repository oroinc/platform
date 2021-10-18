define(function(require) {
    'use strict';

    const $ = require('jquery');

    return {
        /**
         * Opens OAuth authorization popup window and returns the Window object that represents it.
         *
         * @param {String} url
         * @param {Object} config
         * @return {Window}
         */
        openPopup: function(url, config) {
            config.width = config.width || 500;
            config.height = config.height || 600;

            const winLeft = window.screenLeft ? window.screenLeft : window.screenX;
            const winTop = window.screenTop ? window.screenTop : window.screenY;
            const width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            const height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
            const left = Math.max(0, ((width / 2) - (config.width / 2)) + winLeft);
            const top = Math.max(0, ((height / 2) - (config.height / 2)) + winTop);

            return window.open(
                url,
                'oro-oauth-authorization',
                'width=' + config.width + ', height=' + config.height + ', top=' + top + ', left=' + left
            );
        },

        /**
         * Opens OAuth authorization popup window and returns a promise object
         * that can be used to handle the authorization result.
         *
         * @param {String} url
         * @param {Object} config
         * @return {Promise}
         */
        whenAuthorized: function(url, config) {
            const windowDefer = $.Deferred();
            try {
                this
                    .whenWindowClosed(this.openPopup(url, config || {}))
                    .done(() => {
                        windowDefer.resolve(this.isAuthorized);
                    });
            } catch (e) {
                windowDefer.reject();
            }

            return windowDefer.promise();
        },

        /**
         * Returns a promise object that can be used to handle closing of OAuth authorization window.
         *
         * @param {Window} win
         * @return {Promise}
         */
        whenWindowClosed: function(win) {
            const windowClosedDefer = $.Deferred();
            const timerClosed = setInterval(function() {
                if (win.closed) {
                    clearInterval(timerClosed);
                    windowClosedDefer.resolve();
                }
            }, 500);

            this.whenCallbackLoaded(win).done(() => {
                this.isAuthorized = true;
                win.close();
            });

            return windowClosedDefer.promise();
        },

        /**
         * Returns a promise object that can be used to handle completion of OAuth authorization.
         *
         * @param {Window} win
         * @return {Promise}
         */
        whenCallbackLoaded: function(win) {
            const loadedDefer = $.Deferred();
            const timerLoaded = setInterval(function checkOriginFrameAccess() {
                try {
                    const location = String(win.location);
                    if (location === 'about:blank') {
                        return;
                    }
                    setTimeout(function() {
                        clearInterval(timerLoaded);
                        loadedDefer.resolve();
                    }, 800);
                } catch (err) {
                    // Cross origin interaction we want to catch
                    // to get back when callback function is provided
                }
            }, 500);

            return loadedDefer.promise();
        }
    };
});
