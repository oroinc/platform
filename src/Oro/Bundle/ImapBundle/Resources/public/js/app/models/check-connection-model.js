define(function(require) {
    'use strict';

    var CheckConnectionModel;
    var $ = require('jquery');
    var _ = require('underscore');
    var routing = require('routing');
    var BaseModel = require('oroui/js/app/models/base/model');

    CheckConnectionModel = BaseModel.extend({
        route: 'oro_imap_connection_check',
        entity: 'user',
        entityId: null,
        organization: '',
        defaults: {
            imap: {},
            smtp: {}
        },
        initialize: function(attributes, options) {
            _.extend(this, _.pick(options, ['entityId', 'entity', 'organization']));
        },
        url: function() {
            var params = {
                'for_entity': this.entity,
                'organization': this.organization
            };
            if (this.entityId !== null) {
                params.id = this.entityId;
            }

            return routing.generate(this.route) + '?' + $.param(params);
        }
    });
    return CheckConnectionModel;
});
