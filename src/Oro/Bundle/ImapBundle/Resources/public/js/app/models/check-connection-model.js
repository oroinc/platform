define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    const CheckConnectionModel = BaseModel.extend({
        defaults: {
            imap: {},
            smtp: {}
        },

        /**
         * @inheritdoc
         */
        constructor: function CheckConnectionModel(...args) {
            CheckConnectionModel.__super__.constructor.apply(this, args);
        }
    });
    return CheckConnectionModel;
});
