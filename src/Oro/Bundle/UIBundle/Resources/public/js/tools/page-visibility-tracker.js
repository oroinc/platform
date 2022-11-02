define(function(require) {
    'use strict';

    /**
     * Designed to provide timeout that takes in account hidden state of page and guaranteed waits
     * given time in visible page mode
     */
    const _ = require('underscore');
    const timeoutIds = {};
    let pageShownTime = window.document.visibilityState === 'visible' ? Date.now() : -1;

    /**
     * Recursively creates timeouts till the whole timeout is spent in visible mode of page and stores
     * current timeoutId to ability stop timeout chain by calling `pageVisibilityTracker.clearTimeout`
     *
     * @param {string} uid
     * @param {function} callback
     * @param {number} delay
     */
    function timeout(uid, callback, delay) {
        const startTime = Date.now();

        timeoutIds[uid] = setTimeout(function() {
            if (window.document.visibilityState === 'hidden' || pageShownTime > startTime) {
                timeout(uid, callback, delay);
            } else {
                callback();
            }
        }, delay);
    }

    window.document.addEventListener('visibilitychange', function() {
        if (window.document.visibilityState === 'visible') {
            pageShownTime = Date.now();
        }
    });

    const pageVisibilityTracker = {
        /**
         * @param {function} callback
         * @param {number} delay
         * @return {string}
         */
        setTimeout: function(callback, delay) {
            const uid = _.uniqueId();

            timeout(uid, callback, delay);

            return uid;
        },

        /**
         * @param {string} uid
         */
        clearTimeout: function(uid) {
            if (uid in timeoutIds) {
                clearTimeout(timeoutIds[uid]);
            }
        }
    };

    return pageVisibilityTracker;
});
