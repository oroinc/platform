define(function(require) {
    'use strict';

    var _ = require('underscore');
    var moment = require('moment-timezone');
    var origTz = moment.fn.tz;

    /**
     * Getter/Setter for moment's timezone
     *
     * @param {string} name of timezone
     * @param {boolean=} keepTime flag if datetime have to be preserved as it is on timezone change
     * @returns {Moment|string|undefined} updated moment object or timezone name (if it is defined)
     */
    moment.fn.tz = function(name, keepTime) {
        if (name) {
            this._z = moment.tz.zone(name);
            if (this._z && keepTime) {
                var dateTimeString;
                var dateTimeFormat = 'YYYY-MM-DD[T]HH:mm:ss';
                if (this.hasOwnProperty('_tzm')) {
                    dateTimeString = this.add(this._tzm, 'minutes').clone().utc().format(dateTimeFormat);
                    delete this._tzm;
                } else {
                    dateTimeString = this.format(dateTimeFormat);
                }
                var momentWithCorrectTZ = moment.tz(dateTimeString, dateTimeFormat, true, name);
                _.extend(this, _.pick(momentWithCorrectTZ, '_d', '_isUTC', '_offset'));
            } else {
                origTz.call(this, name);
            }
            return this;
        }
        if (this._z) {
            return this._z.name;
        }
    };

    return moment;
});
