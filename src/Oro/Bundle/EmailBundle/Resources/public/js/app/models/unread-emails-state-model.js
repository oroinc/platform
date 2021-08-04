define(function(require) {
    'use strict';

    const BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export oroemail/js/app/models/email-variable-model
     */
    const UnreadEmailsStateModel = BaseModel.extend({
        defaults: {
            count: 0,
            ids: []
        },

        /**
         * @inheritdoc
         */
        constructor: function UnreadEmailsStateModel(attrs, options) {
            UnreadEmailsStateModel.__super__.constructor.call(this, attrs, options);
        }
    });

    return UnreadEmailsStateModel;
});
