/*global define*/
define([
    './../base/page-region-view',
    'oroui/js/mediator',
    'underscore'
], function (PageRegionView, mediator, _) {
    'use strict';

    var PageBreadcrumbView;

    PageBreadcrumbView = PageRegionView.extend({
        template: function (data) {
            return data.breadcrumb;
        },
        breadcrumbsTemplate: _.template('<ul class="breadcrumb">' +
            '<% for (var i =0; i < breadcrumbs.length; i++) { %>'
                + '<li>'
                    + '<%= breadcrumbs[i] %>'
                    + '<%if (i+1 != breadcrumbs.length) { %><span class="divider">/&nbsp;</span><% } %>'
                + '</li>'
                +'<% } %>' +
            '</ul>'),
        pageItems: ['breadcrumb'],

        initialize: function(options) {
            mediator.on('mainMenuUpdated', this.update, this);
            PageBreadcrumbView.__super__.initialize.call(this, options);
        },

        update: function(breadcrumbs) {
            if (breadcrumbs.length) {
                this.data = {
                    'breadcrumb': this.breadcrumbsTemplate({'breadcrumbs': breadcrumbs})
                };
                this.render();
            }
        }
    });

    return PageBreadcrumbView;
});
