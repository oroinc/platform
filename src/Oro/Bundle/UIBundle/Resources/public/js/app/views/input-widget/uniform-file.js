define(function(require) {
    'use strict';

    const AbstractInputWidgetView = require('oroui/js/app/views/input-widget/abstract');
    const __ = require('orotranslation/js/translator');
    const $ = require('jquery');
    const clearButtonTemplate = require('tpl-loader!oroui/templates/clear_button.html');
    require('jquery.uniform');

    const UniformFileInputWidgetView = AbstractInputWidgetView.extend({
        widgetFunctionName: 'uniform',

        initializeOptions: {
            fileDefaultHtml: __('Please select a file...'),
            fileButtonHtml: __('Choose File')
        },

        refreshOptions: 'update',

        containerClassSuffix: 'file',

        /** @property {jQuery} */
        $clearButton: null,

        /**
         * @inheritdoc
         */
        constructor: function UniformFileInputWidgetView(options) {
            UniformFileInputWidgetView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initializeWidget: function(options) {
            UniformFileInputWidgetView.__super__.initializeWidget.call(this, options);
            if (this.$el.is('.error')) {
                this.$el.removeClass('error');
                this.getContainer().addClass('error');
            }

            this.getContainer().append(this.getClearButton());

            this.toggleEmptyState();

            this.$el.on(`change${this.eventNamespace()}`, () => this.toggleEmptyState());

            this.getClearButton().on(`click${this.eventNamespace()}`, () => {
                this.$el.val('').trigger('change').trigger('focus');
            });
        },

        /**
         * @inheritdoc
         */
        disposeWidget: function() {
            this.getClearButton().off(this.eventNamespace());
            this.$el.off(this.eventNamespace());
            this.$el.uniform.restore(this.$el);
            UniformFileInputWidgetView.__super__.disposeWidget.call(this);
        },

        /**
         * Get widget root element
         *
         * @returns {jQuery}
         */
        getClearButton: function() {
            if (this.$clearButton) {
                return this.$clearButton;
            }

            this.$clearButton = $(clearButtonTemplate({
                ariaLabel: __('Clear')
            }));

            return this.$clearButton;
        },

        toggleEmptyState: function() {
            this.getContainer().toggleClass('empty', this.isEmpty());
        },

        isEmpty: function() {
            return !this.$el.val().length;
        },

        getFilenameButton: function() {
            return this.getContainer().find('.filename');
        },

        /**
         * @inheritdoc
         */
        findContainer: function() {
            return this.$el.parent('.uploader');
        }
    });

    return UniformFileInputWidgetView;
});
