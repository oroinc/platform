define(function(require) {
    'use strict';

    const routing = require('routing');
    const BaseModel = require('oroui/js/app/models/base/model');

    const PageStateModel = BaseModel.extend({
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

        /**
         * @inheritdoc
         */
        constructor: function PageStateModel(attrs, options) {
            PageStateModel.__super__.constructor.call(this, attrs, options);
        },

        url: function(method) {
            let args = [this.postRoute];
            if (this.id) {
                args = [this.putRoute, {id: this.id}];
            }
            return routing.generate(...args);
        }
    });

    return PageStateModel;
});
