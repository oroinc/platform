define(function (require) {
    var BaseView = require('oroui/js/app/views/base/view'),
        JsplubmBaseView;

    JsplubmBaseView = BaseView.extend({
        ensureId: function () {
            this.$el.attr('id', this.cid);
        },

        cleanup: function () {
            // empty
        },

        dispose: function () {
            this.cleanup();
            JsplubmBaseView.__super__.dispose.apply(this, arguments);
        }
    });

    return JsplubmBaseView;
});
