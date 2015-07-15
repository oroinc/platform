define(function(require) {
    'use strict';

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
            if (this._z) {
                moment.updateOffset(this, keepTime);
            } else {
                origTz.apply(this, name);
            }
            return this;
        }
        if (this._z) { return this._z.name; }
    };

    return moment;
});
