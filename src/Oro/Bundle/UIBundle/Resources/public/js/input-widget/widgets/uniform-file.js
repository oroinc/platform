define(function(require) {
    'use strict';

    var UniformFileInputWidget;
    var InputWidget = require('oroui/js/input-widget/widget');
    var __ = require('orotranslation/js/translator');

    UniformFileInputWidget = InputWidget.extend({
        widgetFunctionName: 'uniform',

        initializeOptions: {
            fileDefaultHtml: __('Please select a file...'),
            fileButtonHtml: __('Choose File')
        },

        initialize: function() {
            UniformFileInputWidget.__super__.initialize.apply(this, arguments);
            if (this.$input.is('.error')) {
                this.$input.removeClass('error').closest('.uploader').addClass('error');
            }
        },

        destroy: function() {
            this.$input.uniform.restore();
            UniformFileInputWidget.__super__.destroy.apply(this, arguments);
        }
    });

    return UniformFileInputWidget;
});
