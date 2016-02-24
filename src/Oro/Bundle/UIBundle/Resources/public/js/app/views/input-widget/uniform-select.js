define(function(require) {
    'use strict';

    var UniformSelectInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    UniformSelectInputWidget = AbstractInputWidget.extend({
        widgetFunctionName: 'uniform',

        refreshOptions: 'update',

        containerClassSuffix: 'select',

        initialize: function() {
            UniformSelectInputWidget.__super__.initialize.apply(this, arguments);
            if (this.$el.is('.error:not([multiple])')) {
                this.$el.removeClass('error');
                this.$container.addClass('error');
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.uniform.restore();
            UniformSelectInputWidget.__super__.dispose.apply(this, arguments);
        },

        setContainer: function() {
            this.$container = this.$el.parent('.selector');
        },

        setWidth: function(width) {
            UniformSelectInputWidget.__super__.setWidth.apply(this, arguments);
            this.$container.find('span').width(width);
        }
    });

    return UniformSelectInputWidget;
});
