/** @lends BaseClass */
define(function(require) {
    'use strict';

    var _ = require('underscore');
    var Backbone = require('backbone');
    var Chaplin = require('chaplin');

    /**
     * Base class that implement extending in backbone way.
     * Implements [Backbone events API](http://backbonejs.org/#Events), Chaplin's
     * [declarative event bindings](https://github.com/chaplinjs/chaplin/blob/master/docs/chaplin.view.md#listen) and
     * [Chaplin.EventBroker API](https://github.com/chaplinjs/chaplin/blob/master/docs/chaplin.event_broker.md)
     *
     *
     * @class
     * @param {Object} options - Options container
     * @param {Object} options.listen - Optional. Events to bind
     * @exports BaseClass
     */
    function BaseClass(options) {
        options = options || {};
        this.cid = _.uniqueId('class');
        if (!options) {
            options = {};
        }
        this.initialize(options);
        if (options.listen) {
            this.on(options.listen);
        }
        this.delegateListeners();
    }

    BaseClass.prototype = {
        constructor: BaseClass,

        /**
         * Flag shows if the class is disposed or not
         * @type {boolean}
         */
        disposed: false,

        initialize: function(options) {
            // should be defined in descendants
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.trigger('dispose', this);
            this.unsubscribeAllEvents();
            this.stopListening();
            this.off();

            this.disposed = true;
            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
        }
    };

    _.extend(BaseClass.prototype,
        Backbone.Events,
        Chaplin.EventBroker,
        _.pick(Chaplin.View.prototype, ['delegateListeners', 'delegateListener'])
    );

    BaseClass.extend = Backbone.Model.extend;

    return BaseClass;
});
