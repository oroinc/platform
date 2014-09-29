/*global define*/
define([
    'oroui/js/app/models/base/model',
    'routing'
], function (BaseModel, routing) {
    'use strict';

    var PageStateModel;

    PageStateModel = BaseModel.extend({
        defaults: {
            pageId : '',
            data   : '',
            pagestate : {
                pageId : '',
                data   : ''
            }
        },

        url: function (method) {
            var args = ['oro_api_post_pagestate'];
            if (this.id) {
                args = ['oro_api_put_pagestate', {id: this.id}];
            }
            return routing.generate.apply(routing, args);
        }
    });

    return PageStateModel;
});
