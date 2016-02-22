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

        widgetInitialize: function() {
            UniformFileInputWidget.__super__.widgetInitialize.apply(this, arguments);
            if (this.$input.is('.error')) {
                this.$input.removeClass('error').closest('.uploader').addClass('error');
            }
        },

        widgetDestroy: function() {
            this.$input.uniform.restore();
            UniformFileInputWidget.__super__.widgetDestroy.apply(this, arguments);
        }
    });

    return UniformFileInputWidget;
});
