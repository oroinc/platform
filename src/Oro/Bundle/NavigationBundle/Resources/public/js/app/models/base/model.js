define([
    'underscore',
    'routing',
    'oroui/js/app/models/base/model'
], function(_, routing, BaseModel) {
    'use strict';

    var Model;

    Model = BaseModel.extend({
        route: 'oro_api_get_navigationitems',

        defaults: {
            title: '',
            title_rendered: '',
            url: null,
            position: null,
            type: null
        },

        url: function() {
            var base = _.result(this, 'urlRoot') || _.result(this.collection, 'url');
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

    return Model;
});
