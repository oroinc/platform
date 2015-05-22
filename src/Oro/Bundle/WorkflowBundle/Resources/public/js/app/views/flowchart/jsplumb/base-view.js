define(function (require) {
    'use strict';
    var BaseView = require('oroui/js/app/views/base/view'),
        FlowchartJsPlubmBaseView;

    FlowchartJsPlubmBaseView = BaseView.extend({

        initialize: function (options) {
            this.cid = 'jsplumb-' + this.cid;
            FlowchartJsPlubmBaseView.__super__.initialize.apply(this, arguments);
        },
        ensureId: function () {
            this.$el.attr('id', this.cid);
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
