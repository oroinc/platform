define(function(require) {
    'use strict';

    var UnreadEmailsStateModel;
    var BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export oroemail/js/app/models/email-variable-model
     */
    UnreadEmailsStateModel = BaseModel.extend({
        defaults: {
            count: 0,
            ids: []
        },

        /**
         * @inheritDoc
         */
        constructor: function UnreadEmailsStateModel() {
            UnreadEmailsStateModel.__super__.constructor.apply(this, arguments);
        }
    });

    return UnreadEmailsStateModel;
});
