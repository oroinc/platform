/** @lends MultiUseResourceManager */
define(function(require) {
    'use strict';
    var BaseClass = require('../base-class');
    /**
     * Allows to create/remove resource that could be used by multiple holders.
     *
     * Use case:
     * ```javascript
     * var backdropManager = new MultiUseResourceManager({
     *     listen: {
     *         'construct': function() {
     *             $(document.body).addClass('backdrop');
     *         },
     *         'dispose': function() {
     *             $(document.body).removeClass('backdrop');
     *         }
     *     }
     * });
     *
     * // 1. case with Ids
     * var holderId = backdropManager.hold();
     * // then somewhere
     * backdropManager.release(holderId);
     *
     * // 2. case with holder object
     * backdropManager.hold(this);
     * // then somewhere, please note that link to the same object should be provided
     * backdropManager.release(this);
     *
     * // 2. case with holder identifier
     * backdropManager.hold(this.cid);
     * // then somewhere, please note that link to the same object should be provided
     * backdropManager.release(this.cid);
     * ```
     *
     * @class
     * @augments [BaseClass](./base-class.md)
     * @exports MultiUseResourceManager
     */
    var MultiUseResourceManager = BaseClass.extend(/** @exports MultiUseResourceManager.prototype */{
        /**
         * Holders counter
         * @type {number}
         * @protected
         */
        counter: 0,
        /**
         * True if resource is created
         * @type {boolean}
         */
        isCreated: false,
        /**
         * Array of ids of current resource holders
         * @type {Array}
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
