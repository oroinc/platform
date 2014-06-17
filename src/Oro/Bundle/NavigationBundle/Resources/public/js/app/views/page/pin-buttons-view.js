/*global define*/
define([
    'oroui/js/app/views/base/page/region-view'
], function (PageRegionView) {
    'use strict';

    var PagePinButtonsView = PageRegionView.extend({
        pageItems: ['showPinButton', 'titleShort', 'titleSerialized'],

        initialize: function (options) {
            PageRegionView.prototype.initialize.apply(this, arguments);
            this.$buttons = this.$(options.buttons);
        },

        render: function () {
            var data, titleSerialized, titleShort;
            data = this.getTemplateData();
            if (!data) {
                return;
            }

            if (data.showPinButton) {
                titleShort = data.titleShort;
                this.$el.show();
                /**
                 * Setting serialized titles for pinbar and favourites buttons
                 */
                if (data.titleSerialized) {
                    titleSerialized = JSON.parse(data.titleSerialized);
                    this.$buttons.data('title', titleSerialized);
                }
                this.$buttons.data('title-rendered-short', titleShort);
            } else {
                this.$el.hide();
            }
        }
    });

    return PagePinButtonsView;
});
