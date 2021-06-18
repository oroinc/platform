define(function(require) {
    'use strict';

    const _ = require('underscore');
    const routing = require('routing');
    const BaseModel = require('oroui/js/app/models/base/model');

    const BaseNavigationItemModel = BaseModel.extend({
        route: 'oro_api_get_navigationitems',

        defaults: {
            title: '',
            title_rendered: '',
            url: null,
            position: null,
            type: null
        },

        /**
         * @inheritdoc
         */
        constructor: function BaseNavigationItemModel(attrs, options) {
            BaseNavigationItemModel.__super__.constructor.call(this, attrs, options);
        },

        url: function() {
            let base = _.result(this, 'urlRoot') || _.result(this.collection, 'url');
            if (base && base.indexOf(this.get('type')) === -1) {
                base += (base.charAt(base.length - 1) === '/' ? '' : '/') + this.get('type');
            } else if (!base) {
                base = routing.generate(this.route, {type: this.get('type')});
            }
            if (this.isNew()) {
                return base;
            }
            return base + (base.charAt(base.length - 1) === '/' ? '' : '/') + 'ids/' + encodeURIComponent(this.id);
        }
    });

    return BaseNavigationItemModel;
});
