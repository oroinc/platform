define(function(require) {
    'use strict';

    /**
     * Defines transition interface
     */
    var AbstractTransition;
    var BaseClass = require('oroui/js/base-class');

    AbstractTransition = BaseClass.extend({
        constructor: function(options) {
            AbstractTransition.__super__.constructor.call(this, options);
            this.model = options.model;
            this.column = options.column;
            this.boardCollection = options.boardCollection;
            this.relativePosition = options.relativePosition;
            this.apiAccessor = options.apiAccessor;
        },
        /**
         * Function to override in child implementations

         * @param {Backbone.Model} model
         * @return {$.Deferred}
         */
        start: function(model) {
            throw new Error('Expected abstract method `start` to be implemented');
        }
    });

    return AbstractTransition;
});
