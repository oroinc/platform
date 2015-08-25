define([
    'oroui/js/app/models/base/model',
    'routing'
], function(BaseModel, routing) {
    'use strict';

    var PageStateModel;

    PageStateModel = BaseModel.extend({
        defaults: {
            pageId: '',
            data: '',
            pagestate: {
                pageId: '',
                data: ''
            }
        },

        postRoute: 'oro_api_post_pagestate',
        putRoute: 'oro_api_put_pagestate',

        url: function(method) {
            var args = [this.postRoute];
            if (this.id) {
                args = [this.putRoute, {id: this.id}];
            }
            return routing.generate.apply(routing, args);
        }
    });

    return PageStateModel;
});
