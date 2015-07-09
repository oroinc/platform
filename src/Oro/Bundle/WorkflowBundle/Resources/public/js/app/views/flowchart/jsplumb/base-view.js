define(function (require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view'),
        FlowchartJsPlubmBaseView;

    FlowchartJsPlubmBaseView = BaseView.extend({
        id: function () {
            return 'jsplumb-' + this.cid;
        },

        render: function () {
            FlowchartJsPlubmBaseView.__super__.render.apply(this, arguments);

            if (!this.isConnected) {
                this.isConnected = true;
                this.connect();
            }
            return this;
        },

        connect: function () {
            // fill with stuff what should be done once element is rendered
        },

        cleanup: function () {
            // empty
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.cleanup();
            FlowchartJsPlubmBaseView.__super__.dispose.apply(this, arguments);
        }
    });

    return FlowchartJsPlubmBaseView;
});
