define(function(require) {
    'use strict';

    var FormLoadingView;
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    FormLoadingView = BaseView.extend({
        autoRender: true,

        /**
         * @inheritDoc
         */
        constructor: function FormLoadingView() {
            FormLoadingView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.subview('loadingMaskView', new LoadingMaskView({
                container: this.$('.section-content')
            }));

            FormLoadingView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            FormLoadingView.__super__.render.apply(this, arguments);

            this.$el.attr({'data-layout': 'separate', 'data-skip-input-widgets': true});

            return this;
        },

        startLoading: function() {
            var loadingMaskView = this.subview('loadingMaskView');

            loadingMaskView.show();
            this.$el.removeAttr('data-skip-input-widgets');

            return this.initLayout().then(function() {
                loadingMaskView.hide();
            });
        }
    });

    return FormLoadingView;
});
