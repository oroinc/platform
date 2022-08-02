define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const FormSectionLoadingView = require('oroform/js/app/views/form-section-loading-view');
    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');

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
            // TODO: uncomment when scrol to section will be fixed
            // var index = this.$(window.location.hash).parents('.responsive-section').index();
            //
            // index = index !== -1 ? index : 0;
            const index = 0;

            this.$('.responsive-section')
                .not(':nth-child(' + (index + 1) + '),[data-init-section-instantly]')
                .each((index, el) => {
                    this.subview('form-section-loading-' + index, new FormSectionLoadingView({
                        el: $(el)
                    }));
                });

            FormLoadingView.__super__.initialize.call(this, options);
        },

        render: function() {
            FormLoadingView.__super__.render.call(this);

            this.$el.removeAttr('data-skip-input-widgets');
            this.$el.addClass('lazy-loading');

            this.initLayout()
                .then(function() {
                    const $buttons = this._getNavButtons();
                    $buttons.addClass('disabled');
                    this._loadSubviews().then(this._afterLoadSubviews.bind(this, $buttons));
                }.bind(this));

            return this;
        },

        _afterLoadSubviews: function($buttons) {
            $buttons.removeClass('disabled');
            mediator.trigger('page:afterPagePartChange');
            this.$el.removeClass('lazy-loading');
        },

        _loadSubviews: function() {
            const promises = _.map(this.subviews, function(view) {
                return view.startLoading();
            });

            return $.when(...promises);
        },

        _getNavButtons: function() {
            return this.$('.title-buttons-container').find(':button, [role="button"]');
        }
    });

    return FormLoadingView;
});
