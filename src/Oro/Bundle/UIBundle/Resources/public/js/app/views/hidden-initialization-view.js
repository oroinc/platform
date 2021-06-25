/** @exports HiddenInitializationView */
define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

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
    const HiddenInitializationView = BaseView.extend(/** @lends HiddenInitializationView.prototype */{
        autoRender: true,

        /**
         * @inheritdoc
         */
        constructor: function HiddenInitializationView(options) {
            HiddenInitializationView.__super__.constructor.call(this, options);
        },

        render: function() {
            this.$el.addClass('invisible');
            this.initLayout().done(() => {
                this.$el.removeClass('invisible');
            });
        }
    });

    return HiddenInitializationView;
});

