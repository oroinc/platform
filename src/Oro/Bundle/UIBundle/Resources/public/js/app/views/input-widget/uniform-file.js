define(function(require) {
    'use strict';

    var UniformFileInputWidgetView;
    var AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');
    var __ = require('orotranslation/js/translator');
    require('jquery.uniform');

    UniformFileInputWidgetView = AbstractInputWidgetView.extend({
        widgetFunctionName: 'uniform',

        initializeOptions: {
            fileDefaultHtml: __('Please select a file...'),
            fileButtonHtml: __('Choose File')
        },

        refreshOptions: 'update',

        containerClassSuffix: 'file',

        /**
         * @inheritDoc
         */
        constructor: function UniformFileInputWidgetView() {
            UniformFileInputWidgetView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initializeWidget: function() {
            UniformFileInputWidgetView.__super__.initializeWidget.apply(this, arguments);
            if (this.$el.is('.error')) {
                this.$el.removeClass('error');
                this.getContainer().addClass('error');
            }
        },

        /**
         * @inheritDoc
         */
        disposeWidget: function() {
            this.$el.uniform.restore(this.$el);
            UniformFileInputWidgetView.__super__.disposeWidget.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        findContainer: function() {
            return this.$el.parent('.uploader');
        }
    });

    return UniformFileInputWidgetView;
});
