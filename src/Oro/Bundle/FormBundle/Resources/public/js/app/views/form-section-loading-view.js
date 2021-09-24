define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const LoadingMaskView = require('oroui/js/app/views/loading-mask-view');

    const FormLoadingView = BaseView.extend({
        autoRender: true,

        /**
         * @inheritdoc
         */
        constructor: function FormLoadingView(options) {
            FormLoadingView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.subview('loadingMaskView', new LoadingMaskView({
                container: this.$('.section-content')
            }));

            FormLoadingView.__super__.initialize.call(this, options);
        },

        render: function() {
            FormLoadingView.__super__.render.call(this);

            this.$el.attr({'data-layout': 'separate', 'data-skip-input-widgets': true});

            return this;
        },

        startLoading: function() {
            const loadingMaskView = this.subview('loadingMaskView');

            loadingMaskView.show();
            this.$el.removeAttr('data-skip-input-widgets');

            return this.initLayout().then(function() {
                loadingMaskView.hide();
            });
        }
    });

    return FormLoadingView;
});
