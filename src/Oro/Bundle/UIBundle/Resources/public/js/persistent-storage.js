/** @exports persistentStorage */
define(function() {
    'use strict';

    /** @exports persistentStorage */
    var persistentStorage;

    try {
        // test localStorage
        window.localStorage.getItem('dummy-key');
        persistentStorage = window.localStorage;
    } catch (e) {

        // catch IE protected mode and browsers w/o localStorage support

        // use cookies storage instead
        // https://developer.mozilla.org/en-US/docs/Web/API/Storage/LocalStorage

        /**
         * Provides clint-side storage
         * Uses localStorage if supported, otherwise cookies
         * Realizes Storage Interface https://developer.mozilla.org/en-US/docs/Web/API/Storage
         */
        persistentStorage = {
            /**
             * When passed a key name, will return that key's value.
             *
             * @param sKey {string}
             * @returns {string}
             */
            getItem: function(sKey) {
                if (!sKey || !this.hasOwnProperty(sKey)) { return null; }
                return decodeURIComponent(document.cookie.replace(new RegExp('(?:^|.*;\\s*)' + encodeURIComponent(sKey)
                    .replace(/[\-\.\+\*]/g, '\\$&') + '\\s*\\=\\s*((?:[^;](?!;))*[^;]?).*'), '$1'));
            },

            /**
             * When passed a number n, this method will return the name of the nth key in the storage.
             *
             * @param nKeyId {number}
             */
            key: function(nKeyId) {
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
             * @param sValue {string}
             */
            setItem: function(sKey, sValue) {
                if (!sKey) { return; }
                document.cookie = encodeURIComponent(sKey) +
                    '=' + encodeURIComponent(sValue) + '; expires=Tue, 19 Jan 2038 03:14:07 GMT; path=/';
                this.length = document.cookie.match(/=/g).length;
            },

            /**
             * Returns an integer representing the number of data items stored in the Storage object.
             * @readonly
             * @type number
             */
            length: 0,

            /**
             * When passed a key name, will remove that key from the storage.
             *
             * @param sKey {string}
             */
            removeItem: function(sKey) {
                if (!sKey || !this.hasOwnProperty(sKey)) { return; }
                document.cookie = encodeURIComponent(sKey) + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
                this.length--;
            },

            /**
             * @inheritDoc
             */
            // jshint -W001
            hasOwnProperty: function(sKey) {
                return (new RegExp('(?:^|;\\s*)' + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, '\\$&') + '\\s*\\='))
                    .test(document.cookie);
            },
            // jshint +W001

            /**
             * When invoked, will empty all keys out of the storage.
             */
            clear: function() {
                throw new Error('Clearing of cookie persistent storage may cause issues');
            }
        };

        persistentStorage.length = (document.cookie.match(/=/g) || persistentStorage).length;
    }

    return persistentStorage;

});
