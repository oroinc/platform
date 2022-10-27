define(function(require) {
    'use strict';

    const _ = require('underscore');

    const pageStateChecker = {
        /**
         * @type {Array.<Function>}
         */
        _checkers: [],

        /**
         * @type {boolean}
         */
        _isIgnored: false,

        /**
         * Registers checker function in service
         *
         * @param {Function} checker
         */
        registerChecker: function(checker) {
            if (this._checkers.indexOf(checker) === -1) {
                this._checkers.push(checker);
            }
        },

        /**
         * Removes checker function from service
         *
         * @param {Function} checker
         */
        removeChecker: function(checker) {
            const index = this._checkers.indexOf(checker);
            if (index !== -1) {
                this._checkers.splice(index, 1);
            }
        },

        /**
         * Iterates all existing checkers and tests if there's any changes on page
         *
         * @return {boolean}
         */
        isStateChanged: function() {
            return _.some(pageStateChecker._checkers, function(checker) {
                return checker();
            });
        },

        /**
         * Check if user has already decided to ignore page state changes
         *
         * @return {boolean}
         */
        hasChangesIgnored: function() {
            return this._isIgnored;
        },

        /**
         * Sets flag that user has decided to ignore page state changes
         */
        ignoreChanges: function() {
            this._isIgnored = true;
        },

        /**
         * Removes flag that user has decided to ignore page state changes
         */
        notIgnoreChanges: function() {
            this._isIgnored = false;
        }
    };

    return pageStateChecker;
});
