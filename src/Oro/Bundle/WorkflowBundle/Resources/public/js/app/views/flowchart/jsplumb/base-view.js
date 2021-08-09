define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const FlowchartJsPlumbBaseView = BaseView.extend({
        /**
         * @inheritdoc
         */
        constructor: function FlowchartJsPlumbBaseView(options) {
            FlowchartJsPlumbBaseView.__super__.constructor.call(this, options);
        },

        id: function() {
            return 'jsplumb-' + this.cid;
        },

        render: function() {
            FlowchartJsPlumbBaseView.__super__.render.call(this);

            if (!this.isConnected && !this.isConnecting) {
                this.isConnecting = true;
                this.connect();
                this.isConnected = true;
                delete this.isConnecting;
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
                FlowchartJsPlumbBaseView.__super__.dispose.call(this);
            }
        }
    });

    return FlowchartJsPlumbBaseView;
});
