define([
    'orotranslation/js/translator',
    'oroui/js/mediator',
    './bookmark-item-view'
], function(__, mediator, BookmarkItemView) {
    'use strict';

    var PinItemView;

    PinItemView = BookmarkItemView.extend({
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
            var url = this.model.get('url');
            if (mediator.execute('compareUrl', url, event.path)) {
                if (!this.getPinStatusIcon().is('.outdated')) {
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
            this.getPinStatusIcon().addClass('outdated').attr('title', __('Content of pinned page is outdated'));
        },

        markNormal: function() {
            this.getPinStatusIcon().removeClass('outdated').removeAttr('title');
        },

        getPinStatusIcon: function() {
            return this.$('.pin-status');
        }
    });

    return PinItemView;
});
