define(function(require) {
    'use strict';

    const AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');
    const __ = require('orotranslation/js/translator');
    require('jquery.uniform');

    const UniformFileInputWidgetView = AbstractInputWidgetView.extend({
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
        constructor: function UniformFileInputWidgetView(options) {
            UniformFileInputWidgetView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initializeWidget: function(options) {
            UniformFileInputWidgetView.__super__.initializeWidget.call(this, options);
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
            UniformFileInputWidgetView.__super__.disposeWidget.call(this);
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
