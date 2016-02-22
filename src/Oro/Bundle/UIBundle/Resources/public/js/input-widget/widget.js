define(function(require) {
    'use strict';

    var InputWidget;
    var _ = require('underscore');
    var BaseClass = require('oroui/js/base-class');

    InputWidget = BaseClass.extend({
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

        initialize: function(options) {
            _.extend(this, options || {});

            this.input = this.$input[0];
            this.input.inputWidget = this;

            this.widgetFunction = _.bind(this.$input[this.widgetFunctionName], this.$input);

            this.widgetInitialize();
        },

        dispose: function() {
            delete this.input.inputWidget;
            for (var prop in this) {
                if (this.hasOwnProperty(prop)) {
                    delete this[prop];
                }
            }

            return InputWidget.__super__.dispose.apply(this, arguments);
        },

        widgetInitialize: function() {
            if (this.initializeOptions) {
                this.widgetFunction(this.initializeOptions);
            } else {
                this.widgetFunction();
            }
        },

        widgetDestroy: function() {
            if (this.destroyOptions) {
                this.widgetFunction(this.destroyOptions);
            }

            this.dispose();
        }
    });

    return InputWidget;
});
