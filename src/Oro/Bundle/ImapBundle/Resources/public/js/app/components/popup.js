define(function(require) {
    'use strict';

    const $ = require('jquery');
    const __ = require('orotranslation/js/translator');

    return {
        /**
         * Opens popup window and returns the object
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
                __('oro.imap.connection.microsoft.oauth.popup.title'),
                'width=' + config.width + ', height=' + config.height + ', top=' + top + ', left=' + left
            );
        },

        /**
         * Returns authorized promise
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
                    .done(function() {
                        windowDefer.resolve();
                    });
            } catch (e) {
                windowDefer.reject();
            }

            return windowDefer.promise();
        },

        /**
         * Provides a promise of window closed
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

            this.whenCallbackLoaded(win).done(function() {
                win.close();
            });

            return windowClosedDefer.promise();
        },

        /**
         * Returns promise of auth callback loaded
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
                    // to get back when calback function is provided
                }
            }, 500);

            return loadedDefer.promise();
        }
    };
});
