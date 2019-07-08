define(function(require) {
    'use strict';

    var FormLoadingView;
    var BaseView = require('oroui/js/app/views/base/view');
    var FormSectionLoadingView = require('oroform/js/app/views/form-section-loading-view');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

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
            var self = this;
            // TODO: uncomment when scrol to section will be fixed
            // var index = this.$(window.location.hash).parents('.responsive-section').index();
            //
            // index = index !== -1 ? index : 0;
            var index = 0;

            this.$('.responsive-section').not(':nth-child(' + (index + 1) + ')').each(function(index, el) {
                self.subview('form-section-loading-' + index, new FormSectionLoadingView({
                    el: $(el)
                }));
            });

            FormLoadingView.__super__.initialize.apply(this, arguments);
        },

        render: function() {
            FormLoadingView.__super__.render.apply(this, arguments);

            this.$el.removeAttr('data-skip-input-widgets');
            this.$el.addClass('lazy-loading');

            this.initLayout()
                .then(function() {
                    var $buttons = this._getNavButtons();
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
            var promises = _.map(this.subviews, function(view) {
                return view.startLoading();
            });

            return $.when.apply($, promises);
        },

        _getNavButtons: function() {
            return this.$('.title-buttons-container').find(':button, [role="button"]');
        }
    });

    return FormLoadingView;
});
