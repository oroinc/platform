define(function(require) {
    'use strict';

    var TransitionErrorView;
    var widgetManager = require('oroui/js/widget-manager');
    var BaseView = require('oroui/js/app/views/base/view');

    TransitionErrorView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['wid']),

        /**
         * @inheritDoc
         */
        constructor: function TransitionErrorView() {
            TransitionErrorView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            widgetManager.getWidgetInstance(this.wid, function(widget) {
                widget.trigger('formSaveError');
            });

            TransitionErrorView.__super__.initialize.apply(this, arguments);
        }
    });

    return TransitionErrorView;
});
