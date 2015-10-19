define(function(require) {
    'use strict';

    var TabsComponent;
    var $ = require('jquery');
    var BaseComponent = require('oroui/js/app/components/base/component');
    require('jquery.droptabs');

    /**
     * @export oroui/js/app/components/tabs-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.TabsComponent
     */
    TabsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            useDropdown: false
        },

        /**
         * @property {jQuery}
         */
        $el: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            this.$el = options._sourceElement;

            if (this.options.useDropdown) {
                this.$el.find('.nav-tabs').droptabs();
            }
        }
    });

    return TabsComponent;
});
