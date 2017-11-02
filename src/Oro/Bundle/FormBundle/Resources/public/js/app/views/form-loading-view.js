define(function(require) {
    'use strict';

    var FormLoadingView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');

    FormLoadingView = BaseView.extend({
        autoRender: true,

        render: function() {
            var index = this.$(window.location.hash).parents('.responsive-section').index();
            index = index !== -1 ? index : 0;

            this.$el.removeAttr('data-skip-input-widgets');

            this.$('.responsive-section').not(':nth-child(' + (index + 1) + ')').each(function() {
                $(this).attr({'data-layout': 'separate', 'data-skip-input-widgets': true});
            });

            this.initLayout();

            FormLoadingView.__super__.render.apply(this);
        }
    });

    return FormLoadingView;
});
