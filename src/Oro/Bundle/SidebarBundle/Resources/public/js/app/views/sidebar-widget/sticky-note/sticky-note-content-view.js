define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');

    const StickyNoteContentView = BaseView.extend({
        listen: {
            'change model': 'render'
        },

        /**
         * @inheritdoc
         */
        constructor: function StickyNoteContentView(options) {
            StickyNoteContentView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            const settings = this.model.get('settings') || {};
            const content = _.escape(String(settings.content)).replace(/\r?\n/g, '<br/>');
            this.$el.html(content);
            return this;
        }
    });

    return StickyNoteContentView;
});
