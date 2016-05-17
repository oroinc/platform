define(function(require) {
    'use strict';

    var UniformSelectInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');
    require('jquery.uniform');

    UniformSelectInputWidget = AbstractInputWidget.extend({
        widgetFunctionName: 'uniform',

        refreshOptions: 'update',

        containerClassSuffix: 'select',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            UniformSelectInputWidget.__super__.initialize.apply(this, arguments);
            if (this.$el.is('.error:not([multiple])')) {
                this.$el.removeClass('error');
                this.$container.addClass('error');
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.uniform.restore(this.$el);
            UniformSelectInputWidget.__super__.dispose.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        findContainer: function() {
            this.$container = this.$el.parent('.selector');
        },

        /**
         * @inheritDoc
         */
        setWidth: function(width) {
            UniformSelectInputWidget.__super__.setWidth.apply(this, arguments);
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
