/*global define*/
define(['backbone', 'routing'
    ], function (Backbone, routing) {
    'use strict';

    /**
     * @export  oronavigation/js/pagestate/model
     * @class   oronavigationpagestate.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            restore   : false,
            pagestate : {
                pageId : '',
                data   : {}
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
});
