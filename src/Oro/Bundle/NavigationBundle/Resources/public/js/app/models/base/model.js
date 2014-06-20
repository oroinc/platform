/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'routing',
    'oroui/js/app/models/base/model'
], function (_, routing, BaseModel) {
    'use strict';

    var Model;

    Model = BaseModel.extend({
        defaults: {
            title: '',
            title_rendered: '',
            url: null,
            position: null,
            type: null
        },

        url: function () {
            var base = _.result(this, 'urlRoot') || _.result(this.collection, 'url');
            if (base && base.indexOf(this.get('type')) === -1) {
                base += (base.charAt(base.length - 1) === '/' ? '' : '/') + this.get('type');
            } else if (!base) {
                base = routing.generate('oro_api_get_navigationitems', { type: this.get('type') });
            }
            if (this.isNew()) {
                return base;
            }
            return base + (base.charAt(base.length - 1) === '/' ? '' : '/') + 'ids/' + encodeURIComponent(this.id);
        }
    });

    return Model;
});
