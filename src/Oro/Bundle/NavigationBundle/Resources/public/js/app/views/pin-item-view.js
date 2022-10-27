define(function(require) {
    'use strict';

    const mediator = require('oroui/js/mediator');
    const __ = require('orotranslation/js/translator');
    const BookmarkItemView = require('oronavigation/js/app/views/bookmark-item-view');

    const PinItemView = BookmarkItemView.extend({
        className: 'pin-holder',

        template: require('tpl-loader!oronavigation/templates/pin-item.html'),

        listen: {
            'change:url model': 'render',
            'change:title_rendered_short model': 'render',
            'page:afterChange mediator': 'onPageUpdated',
            'route:change mediator': 'onPageUpdated'
        },

        /**
         * @inheritdoc
         */
        constructor: function PinItemView(options) {
            PinItemView.__super__.constructor.call(this, options);
        },

        remove: function() {
            mediator.off('content-manager:content-outdated', this.outdatedContentHandler, this);
            PinItemView.__super__.remove.call(this);
        },

        render: function() {
            PinItemView.__super__.render.call(this);

            // if cache used highlight tab on content outdated event
            mediator.on('content-manager:content-outdated', this.outdatedContentHandler, this);
            this.setActiveItem();
        },

        outdatedContentHandler: function(event) {
            const url = this.model.get('url');
            if (mediator.execute('compareUrl', url, event.path)) {
                if (!this.$el.hasClass('outdated')) {
                    this.markOutdated();
                    this.listenTo(mediator, 'page:afterRefresh', this.onPageRefresh);
                }
            }
        },

        onPageRefresh: function() {
            if (this.checkCurrentUrl()) {
                this.markNormal();
                this.stopListening(mediator, 'page:afterRefresh');
            }
        },

        markOutdated: function() {
            this.$el
                .addClass('outdated')
                .tooltip({
                    title: __('Content of pinned page is outdated')
                });
        },

        markNormal: function() {
            this.$el
                .removeClass('outdated')
                .tooltip('destroy');
        },

        /**
         * @inheritdoc
         */
        checkCurrentUrl: function() {
            const url = this.model.get('url');
            return mediator.execute('compareNormalizedUrl', url, {ignoreGetParameters: ['restore']});
        },

        /**
         * @inheritdoc
         */
        setActiveItem: function() {
            const isUrlSame = this.checkCurrentUrl();
            this.$el.toggleClass('active', isUrlSame);
            this.$el.find('a').data('options', {forceStartup: !isUrlSame});
        }
    });

    return PinItemView;
});
