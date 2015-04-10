/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentModel,
        BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-template-model
     */
    EmailAttachmentModel = BaseModel.extend({
        defaults: {
            id: null,
            type: '',
            fileName: '',
            checked: false, // whether file is checked for attaching to an email
            attached: false // whether file is already attached to an email
        },

        toggleChecked: function() {
            this.set('checked', !this.get('checked'));
        },

        set: function(key, value, options) {
            var attrs;
            if (_.isObject(key) || key == null) {
                attrs = key;
                options = value;
            } else {
                attrs = {};
                attrs[key] = value;
            }

            for (var attr in attrs) {
                if (attr == 'fileName') {
                    attrs['fileName'] = this.formatFileName(attrs['fileName']);
                }
            }

            return Backbone.Model.prototype.set.call(this, attrs, options);
        },

        formatFileName: function(fileName) {
            if (fileName.length > 15) {
                fileName = fileName.substr(0, 7) + '..' + fileName.substr(fileName.length - 7);
            }

            return fileName;
        }
    });

    return EmailAttachmentModel;
});
