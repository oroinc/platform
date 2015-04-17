/*global define*/
define(function (require) {
    'use strict';

    var EmailAttachmentModel,
        datetime = require('orolocale/js/formatter/datetime'),
        numeral = require('numeral'),
        BaseModel = require('oroui/js/app/models/base/model');

    /**
     * @export  oroemail/js/app/models/email-template-model
     */
    EmailAttachmentModel = BaseModel.extend({
        defaults: {
            id: null,
            type: '',
            title: '',
            fileName: '',
            fileSize: '',
            modified: '',
            checked: false, // whether file is checked for attaching to an email
            visible: true
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
                } else if (attr == 'fileSize') {
                    if (attrs['fileSize']) {
                        attrs['fileSize'] = numeral(attrs['fileSize']).format('b');
                    }
                } else if (attr == 'modified') {
                    if (attrs['modified']) {
                        attrs['modified'] = datetime.formatDateTime(attrs['modified']);
                    }
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
