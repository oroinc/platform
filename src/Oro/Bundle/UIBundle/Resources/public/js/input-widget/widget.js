define(function(require) {
    'use strict';

    var InputWidget;
    var _ = require('underscore');
    var Backbone = require('backbone');

    InputWidget = function($input) {
        this.$input = $input;
        this.input = $input[0];
        this.input.inputWidget = this;

        this.widgetFunction = _.bind(this.$input[this.widgetFunctionName], this.$input);

        this.initialize();
    };

    _.extend(InputWidget.prototype, {
        /** @property {jQuery} */
        $input: null,

        /** @property {HTMLElement} */
        input: null,

        /** @property {String} */
        widgetFunctionName: '',

        /** @property {Function} */
        widgetFunction: null,

        /** @property {mixed} */
        initializeOptions: null,

        /** @property {mixed} */
        destroyOptions: null,

        initialize: function() {
            if (this.initializeOptions) {
                this.widgetFunction(this.initializeOptions);
            } else {
                this.widgetFunction();
            }
        },

        destroy: function() {
            if (this.destroyOptions) {
                this.widgetFunction(this.destroyOptions);
            }

            delete this.input.inputWidget;
            for (var prop in this) {
                if (this.hasOwnProperty(prop)) {
                    delete this[prop];
                }
            }

            return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
        }
    });

    InputWidget.extend = Backbone.Model.extend;

    return InputWidget;
});
