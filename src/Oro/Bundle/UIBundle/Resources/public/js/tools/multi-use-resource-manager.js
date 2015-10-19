define(function(require) {
    'use strict';
    var BaseClass = require('../base-class');
    /**
     * @class
     */
    var MultiUseResourceManager = BaseClass.extend({
        /**
         * @type {number} holders counter
         * @protected
         */
        counter: 0,
        /**
         * @type {boolean} true if resource is created
         */
        isCreated: false,
        /**
         * @type {Array} array of ids of current resource holders
         */
        holders: null,

        /**
         * @inheritDoc
         */
        constructor: function(options) {
            this.holders = [];
            MultiUseResourceManager.__super__.constructor.call(this, options);
        },

        /**
         * Holds resource
         *
         * @param holder {*} holder identifier
         * @returns {*} holder identifier
         */
        hold: function(holder) {
            if (!holder) {
                holder = this.counter;
                this.counter = holder + 1;
            }
            this.holders.push(holder);
            this.checkState();
            return holder;
        },

        /**
         * Releases resource
         *
         * @param id {*} holder identifier
         */
        release: function(id) {
            var index = this.holders.indexOf(id);
            if (index !== -1) {
                this.holders.splice(index, 1);
            }
            this.checkState();
        },

        /**
         * Returns true if resource holder has been already released
         *
         * @param id {*} holder identifier
         * @returns {boolean}
         */
        isReleased: function(id) {
            return this.holders.indexOf(id) === -1;
        },

        /**
         * Check state, creates or disposes resource if required
         *
         * @protected
         */
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

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.isCreated) {
                this.trigger('dispose');
            }
            MultiUseResourceManager.__super__.dispose.call(this);
        }
    });

    return MultiUseResourceManager;
});
