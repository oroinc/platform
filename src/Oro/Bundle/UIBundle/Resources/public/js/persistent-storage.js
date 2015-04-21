/** @exports persistentStorage */
define(function (require) {
    'use strict';
    require('jquery.cookie');

    try {
        // test localStorage
        window.localStorage.getItem('dummy-key');
        return window.localStorage;
    } catch (e) {
        // catch IE protected mode or browsers w/o localStorage support
        // use cookies storage instead
        // https://developer.mozilla.org/en-US/docs/Web/API/Storage/LocalStorage

        /**
         * Provides clint-side storage storage
         * Uses localStorage if supported, otherwise cookies
         * Realizes Storage Interface https://developer.mozilla.org/en-US/docs/Web/API/Storage
         */
        var persistentStorage = {
            /**
             * Keys which should be ignored on clear
             * @type {Array.<string>}
             */
            ignoreKeys: ['BAPID_DIST', 'CRMID'],

            /**
             * When passed a key name, will return that key's value.
             *
             * @param sKey {string}
             * @returns {*}
             */
            getItem: function (sKey) {
                if (!sKey || !this.hasOwnProperty(sKey)) { return null; }
                return $.cookie(sKey);
            },

            /**
             * When passed a number n, this method will return the name of the nth key in the storage.
             */
            key: function (nKeyId) {
                return decodeURIComponent(
                        document.cookie
                            .replace(/\s*=(?:.(?!;))*$/, '')
                            .split(/\s*=(?:[^;](?!;))*[^;]?;\s*/
                    )[nKeyId]);
            },

            /**
             * When passed a key name and value, will add that key to the storage, or update that key's value if it
             * already exists.
             *
             * @param sKey {string}
             * @param sValue {*}
             */
            setItem: function (sKey, sValue) {
                if (!sKey) { return; }
                $.cookie(sKey, sValue, {expires: 365, path: '/'});
                this.length = document.cookie.match(/=/g).length;
            },

            /**
             * Returns an integer representing the number of data items stored in the Storage object.
             * @readonly
             */
            length: 0,

            /**
             * When passed a key name, will remove that key from the storage.
             *
             * @param sKey
             */
            removeItem: function (sKey) {
                if (!sKey || !this.hasOwnProperty(sKey)) { return; }
                $.cookie(sKey, null);
                this.length--;
            },

            /**
             * When invoked, will empty all keys out of the storage.
             */
            clear: function () {
                var i, key,
                    currentKeys = document.cookie
                        .replace(/\s*=(?:.(?!;))*$/, '')
                        .split(/\s*=(?:[^;](?!;))*[^;]?;\s*/;
                for (i = 0; i < currentKeys.length;i++) {
                    key = decodeURIComponent(currentKeys[i]);
                    if (-1 === this.ignoreKeys.indexOf(key)) {
                        this.removeItem(key);
                    }
                }
            }
        };

        persistentStorage.length = (document.cookie.match(/=/g) || persistentStorage).length;

        return persistentStorage;
    }
});
