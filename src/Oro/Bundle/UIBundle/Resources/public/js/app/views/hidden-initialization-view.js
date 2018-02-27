/** @exports HiddenInitializationView */
define(function(require) {
    'use strict';

    var HiddenInitializationView;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * View allows hide part of DOM tree till all page components will be initialized
     *
     * Usage sample:
     *
     * > Please note that all div's attributes are required for valid work.
     *
     * ```html
     * <div class="invisible"
     *         data-page-component-module="oroui/js/app/components/view-component"
     *         data-page-component-options="{'view': 'oroui/js/app/views/hidden-initialization-view'}"
     *         data-layout="separate">
     *     <!-- write anything here -->
     * </div>
     * ```
     *
     * @class HiddenInitializationView
     * @augments BaseView
     */
    HiddenInitializationView = BaseView.extend(/** @lends HiddenInitializationView.prototype */{
        autoRender: true,

        /**
         * @inheritDoc
         */
        constructor: function HiddenInitializationView() {
            HiddenInitializationView.__super__.constructor.apply(this, arguments);
        },

        render: function() {
            this.$el.addClass('invisible');
            this.initLayout().done(_.bind(function() {
                this.$el.removeClass('invisible');
            }, this));
        }
    });

    return HiddenInitializationView;
});

