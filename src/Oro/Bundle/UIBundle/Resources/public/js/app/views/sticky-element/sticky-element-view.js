define(function(require) {
    'use strict';

    var StickyElementView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var stickyElementMixin = require('oroui/js/app/views/sticky-element/sticky-element-mixin');

    StickyElementView = BaseView.extend(_.extend({}, stickyElementMixin, {
        /**
         * @inheritDoc
         */
        constructor: function StickyElementView() {
            StickyElementView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            StickyElementView.__super__.initialize.apply(this, arguments);

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

            StickyElementView.__super__.dispose.apply(this, arguments);
        }
    }));

    return StickyElementView;
});
