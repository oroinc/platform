define(function(require) {
    'use strict';

    var pageStateChecker;
    var _ = require('underscore');

    pageStateChecker = {
        /**
         * @type {Array.<Function>}
         */
        _checkers: [],

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
            var index = this._checkers.indexOf(checker);
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
        }
    };

    return pageStateChecker;
});
