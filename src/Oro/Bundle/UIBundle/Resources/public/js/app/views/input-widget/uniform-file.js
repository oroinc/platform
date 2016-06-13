define(function(require) {
    'use strict';

    var UniformFileInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    var __ = require('orotranslation/js/translator');
    require('jquery.uniform');

    UniformFileInputWidget = AbstractInputWidget.extend({
        widgetFunctionName: 'uniform',

        initializeOptions: {
            fileDefaultHtml: __('Attach file:'),
            fileButtonHtml: __('Upload')
        },

        refreshOptions: 'update',

        containerClassSuffix: 'file',

        /**
         * @inheritDoc
         */
        initializeWidget: function() {
            UniformFileInputWidget.__super__.initializeWidget.apply(this, arguments);
            if (this.$el.is('.error')) {
                this.$el.removeClass('error');
                this.container().addClass('error');
            }
        },

        /**
         * @inheritDoc
         */
        disposeWidget: function() {
            this.$el.uniform.restore(this.$el);
            UniformFileInputWidget.__super__.disposeWidget.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        findContainer: function() {
            return this.$el.parent('.uploader');
        }
    });

    return UniformFileInputWidget;
});
