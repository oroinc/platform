define(function(require) {
    'use strict';

    var UniformSelectInputWidget;
    var InputWidget = require('oroui/js/input-widget/widget');

    UniformSelectInputWidget = InputWidget.extend({
        widgetFunctionName: 'uniform',

        initialize: function() {
            UniformSelectInputWidget.__super__.initialize.apply(this, arguments);
            if (this.$input.is('.error:not([multiple])')) {
                this.$input.removeClass('error').closest('.selector').addClass('error');
            }
        },

        destroy: function() {
            this.$input.uniform.restore();
            UniformSelectInputWidget.__super__.destroy.apply(this, arguments);
        }
    });

    return UniformSelectInputWidget;
});
