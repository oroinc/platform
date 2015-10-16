define(function(require) {
    'use strict';
    var BaseClass = require('../base-class');
    var MultiUseResourceManager = BaseClass.extend({
        counter: 0,
        isCreated: false,
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
            if (this.holders.length > 0 && !this.isCreated) {
                this.isCreated = true;
                this.trigger('construct');
                return;
            }
            if (this.holders.length <= 0 && this.isCreated) {
                this.isCreated = false;
                this.trigger('dispose');
            }
        },

        dispose: function() {
            if (this.isCreated) {
                this.trigger('dispose');
            }
            MultiUseResourceManager.__super__.dispose.call(this);
        }
    });

    return MultiUseResourceManager;
});
