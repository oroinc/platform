define(function(require) {
    'use strict';

    var StickyNoteContentView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    StickyNoteContentView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function StickyNoteContentView() {
            StickyNoteContentView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var settings = this.model.get('settings') || {};
            var content = _.escape(String(settings.content)).replace(/\r?\n/g, '<br/>');
            this.$el.html(content);
            return this;
        }
    });

    return StickyNoteContentView;
});
