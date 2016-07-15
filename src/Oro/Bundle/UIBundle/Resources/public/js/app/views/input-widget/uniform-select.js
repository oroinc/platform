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
        initializeWidget: function() {
            //support for readonly attr
            if (this.$el.is('[readonly]')) {
                this.$el.on('click mousedown', function(e) {
                    e.preventDefault();
                    return false;
                });
                this.$el.find('option:not(:selected), [value=""]').remove();
            }

            UniformSelectInputWidget.__super__.initializeWidget.apply(this, arguments);
            if (this.$el.is('.error:not([multiple])')) {
                this.$el.removeClass('error');
                this.container().addClass('error');
            }
        },

        /**
         * @inheritDoc
         */
        disposeWidget: function() {
            this.$el.uniform.restore(this.$el);
            UniformSelectInputWidget.__super__.disposeWidget.apply(this, arguments);
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
            UniformSelectInputWidget.__super__.width.apply(this, arguments);
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
