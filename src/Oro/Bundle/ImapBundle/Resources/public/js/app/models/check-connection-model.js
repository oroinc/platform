define(function(require) {
    'use strict';

    var CheckConnectionModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    CheckConnectionModel = BaseModel.extend({
        defaults: {
            imap: {},
            smtp: {}
        },

        /**
         * @inheritDoc
         */
        constructor: function CheckConnectionModel() {
            CheckConnectionModel.__super__.constructor.apply(this, arguments);
        }
    });
    return CheckConnectionModel;
});
