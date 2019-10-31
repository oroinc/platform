define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseView = require('oroui/js/app/views/base/view');
    const stickyElementMixin = require('oroui/js/app/views/sticky-element/sticky-element-mixin');

    const StickyElementView = BaseView.extend(_.extend({}, stickyElementMixin, {
        /**
         * @inheritDoc
         */
        constructor: function StickyElementView(options) {
            StickyElementView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            StickyElementView.__super__.initialize.call(this, options);

            this.initializeSticky({
                $stickyElement: $(options.el),
                stickyOptions: options.stickyOptions || {}
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.disposeSticky();

            StickyElementView.__super__.dispose.call(this);
        }
    }));

    return StickyElementView;
});
