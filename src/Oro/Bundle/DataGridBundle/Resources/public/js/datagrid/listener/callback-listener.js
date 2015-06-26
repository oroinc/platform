/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    './abstract-listener'
], function(_, AbstractListener) {
    'use strict';

    var CallbackListener;

    /**
     * Listener with custom callback to execute
     *
     * @export  orodatagrid/js/datagrid/listener/callback-listener
     * @class   orodatagrid.datagrid.listener.CallbackListener
     * @extends orodatagrid.datagrid.listener.AbstractListener
     */
    CallbackListener = AbstractListener.extend({
        /** @param {Call} */
        processCallback: null,

        /**
         * Initialize listener object
         *
         * @param {Object} options
         */
        initialize: function(options) {
            if (!_.has(options, 'processCallback')) {
                throw new Error('Process callback is not specified');
            }

            this.processCallback = options.processCallback;

            CallbackListener.__super__.initialize.apply(this, arguments);
        },

        /**
         * Execute callback
         *
         * @param {*} value Value of model property with name of this.dataField
         * @param {Backbone.Model} model
         * @protected
         */
        _processValue: function(value, model) {
            this.processCallback(value, model, this);
        }
    });

    return CallbackListener;
});
