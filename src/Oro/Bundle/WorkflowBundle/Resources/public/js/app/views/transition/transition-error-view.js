define(function(require) {
    'use strict';

    const widgetManager = require('oroui/js/widget-manager');
    const BaseView = require('oroui/js/app/views/base/view');

    const TransitionErrorView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['wid']),

        /**
         * @inheritdoc
         */
        constructor: function TransitionErrorView(options) {
            TransitionErrorView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            widgetManager.getWidgetInstance(this.wid, function(widget) {
                widget.trigger('formSaveError');
            });

            TransitionErrorView.__super__.initialize.call(this, options);
        }
    });

    return TransitionErrorView;
});
