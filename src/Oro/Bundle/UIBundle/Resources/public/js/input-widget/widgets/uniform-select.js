define(function(require) {
    'use strict';

    var UniformSelectInputWidget;
    var InputWidget = require('oroui/js/input-widget/widget');

    UniformSelectInputWidget = InputWidget.extend({
        widgetFunctionName: 'uniform',

        widgetInitialize: function() {
            UniformSelectInputWidget.__super__.widgetInitialize.apply(this, arguments);
            if (this.$input.is('.error:not([multiple])')) {
                this.$input.removeClass('error').closest('.selector').addClass('error');
            }
        },

        widgetDestroy: function() {
            this.$input.uniform.restore();
            UniformSelectInputWidget.__super__.widgetDestroy.apply(this, arguments);
        }
    });

    return UniformSelectInputWidget;
});
