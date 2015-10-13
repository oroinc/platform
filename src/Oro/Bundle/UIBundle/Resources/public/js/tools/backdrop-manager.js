define(function(require) {
    'use strict';
    var $ = require('jquery');
    var backdropManager = {
        counter: 0,
        holders: [],
        hold: function() {
            this.holders.push(this.counter);
            this.checkState();
            return this.counter++;
        },

        release: function(id) {
            var index = this.holders.indexOf(id);
            if (index !== -1) {
                this.holders.splice(index, 1);
            }
            this.checkState();
        },

        isReleased: function(id) {
            return this.holders.indexOf(id) === -1;
        },

        checkState: function() {
            $(document.body).toggleClass('backdrop', this.holders.length > 0);
        }
    };

    return backdropManager;
});
