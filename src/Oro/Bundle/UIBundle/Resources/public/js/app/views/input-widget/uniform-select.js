define(function(require) {
    'use strict';

    const $ = require('jquery');
    const AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    require('jquery.uniform');

    const UniformSelectInputWidget = AbstractInputWidget.extend({
        widgetFunctionName: 'uniform',

        refreshOptions: 'update',

        containerClassSuffix: 'select',

        /**
         * Default widget uniform options
         * @property
         */
        initializeOptions: {
            selectAutoWidth: false
        },

        /**
         * @inheritDoc
         */
        constructor: function UniformSelectInputWidget(options) {
            UniformSelectInputWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initializeWidget: function(options) {
            // support for readonly attr
            this.$el.on('click mousedown', function(e) {
                if ($(e.currentTarget).is('[readonly],[disabled]')) {
                    return false;
                }
            });
            if (this.$el.is('[readonly]')) {
                this.$el.find('option:not(:selected), [value=""]').remove();
            }

            UniformSelectInputWidget.__super__.initializeWidget.call(this, options);
            if (this.$el.is('.error:not([multiple])')) {
                this.$el.removeClass('error');
                this.getContainer().addClass('error');
            }
        },

        /**
         * @inheritDoc
         */
        disposeWidget: function() {
            this.$el.uniform.restore(this.$el);
            UniformSelectInputWidget.__super__.disposeWidget.call(this);
        },

        /**
         * @inheritDoc
         */
        findContainer: function() {
            return this.$el.parent('.selector');
        },

        /**
         * @inheritDoc
         */
        width: function(width) {
            UniformSelectInputWidget.__super__.width.call(this, width);
            this.$container.find('span').width(width);
        },

        /**
         * @inheritDoc
         */
        isInitialized: function() {
            return this.$el.data('uniformed') ? true : false;
        }
    });

    return UniformSelectInputWidget;
});
