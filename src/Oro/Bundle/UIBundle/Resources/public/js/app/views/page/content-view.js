/*global define*/
define([
    'oroui/js/mediator',
    './../base/page-region-view'
], function (mediator, PageRegionView) {
    'use strict';

    var PageContentView;

    PageContentView = PageRegionView.extend({
        template: function (data) {
            return data.content;
        },
        pageItems: ['content', 'scripts'],

        render: function () {
            var data;
            data = this.getTemplateData();
            if (!data) {
                return;
            }

            mediator.execute('layout:dispose', this.$el);

            PageRegionView.prototype.render.call(this);

            // @TODO discuss if scripts section is still in use
            if (data.scripts.length) {
                this.$el.append(data.scripts);
            }
        }
    });

    return PageContentView;
});
