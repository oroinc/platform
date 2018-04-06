define(function(require) {
    'use strict';

    var FlowchartJsPlumbBaseView;
    var BaseView = require('oroui/js/app/views/base/view');

    FlowchartJsPlumbBaseView = BaseView.extend({
        /**
         * @inheritDoc
         */
        constructor: function FlowchartJsPlumbBaseView() {
            FlowchartJsPlumbBaseView.__super__.constructor.apply(this, arguments);
        },

        id: function() {
            return 'jsplumb-' + this.cid;
        },

        render: function() {
            FlowchartJsPlumbBaseView.__super__.render.apply(this, arguments);

            if (!this.isConnected) {
                this.isConnected = true;
                this.connect();
            }
            return this;
        },

        connect: function() {
            // fill with stuff what should be done once element is rendered
        },

        cleanup: function() {
            // empty
        },

        dispose: function() {
            if (!this.disposed) {
                this.cleanup();
                FlowchartJsPlumbBaseView.__super__.dispose.apply(this, arguments);
            }
        }
    });

    return FlowchartJsPlumbBaseView;
});
