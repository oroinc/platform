define(function(require) {
    'use strict';

    var UniformSelectInputWidget;
    var AbstractInputWidget = require('oroui/js/app/views/input-widget/abstract');

    UniformSelectInputWidget = AbstractInputWidget.extend({
        widgetFunctionName: 'uniform',

        refreshOptions: 'update',

        initialize: function() {
            UniformSelectInputWidget.__super__.initialize.apply(this, arguments);
            if (this.$el.is('.error:not([multiple])')) {
                this.$el.removeClass('error').closest('.selector').addClass('error');
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.uniform.restore();
            UniformSelectInputWidget.__super__.dispose.apply(this, arguments);
        },

        getContainer: function() {
            var $parent = this.$el.parent('.selector');
            return $parent.length > 0 ? $parent : null;
        }
    });

    return UniformSelectInputWidget;
});
