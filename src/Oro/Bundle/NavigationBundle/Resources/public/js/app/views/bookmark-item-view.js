define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    const BookmarkItemView = BaseView.extend({
        tagName: 'li',

        events: {
            'click .btn-close': 'toRemove',
            'click .close': 'toRemove'
        },

        listen: {
            'change:url model': 'render',
            'change:title_rendered_short model': 'render',
            'page:afterChange mediator': 'onPageUpdated'
        },

        /**
         * @inheritdoc
         */
        constructor: function BookmarkItemView(options) {
            BookmarkItemView.__super__.constructor.call(this, options);
        },

        /**
         * Change active item after navigation request is completed
         */
        onPageUpdated: function() {
            this.setActiveItem();
        },

        toRemove: function() {
            this.model.collection.trigger('toRemove', this.model);
        },

        /**
         * Compares current url with model's url
         *
         * @returns {boolean}
         */
        checkCurrentUrl: function() {
            const url = this.model.get('url');
            return mediator.execute('compareUrl', url);
        },

        setActiveItem: function() {
            this.$el.toggleClass('active', this.checkCurrentUrl());
        },

        getTemplateData: function() {
            const data = BookmarkItemView.__super__.getTemplateData.call(this);
            // to support previously saved urls without leading slash
            data.url = (data.url[0] !== '/' ? '/' : '') + data.url;
            return data;
        }
    });

    return BookmarkItemView;
});
