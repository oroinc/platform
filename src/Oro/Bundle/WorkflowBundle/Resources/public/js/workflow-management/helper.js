/* global define */
define(function() {
    'use strict';

    /**
     * @export  oro/workflow-management/helper
     * @class   oro.WorkflowManagement.Helper
     */
    return {
        getNameByString: function(str, prefix) {
            str = (prefix || '') + str;         //Add prefix to string
            str = str
                .toLowerCase()                   //Convert to lowercase
                .replace(/\s+|\-+/g, '_')        //Replace spaces and - with underscore
                .replace(/[\u0250-\ue007]/g, '') //Remove all non latin symbols
                .replace(/__+/g, '_');           //Remove duplicated underscores;

            return str + '_' + this.getRandomId();
        },

        getRandomId: function() {
            return Math.random().toString(16).slice(2);
        },

        getFormData: function(form) {
            var data = form.serializeArray();
            var result = {};
            for (var i = 0; i < data.length; i++) {
                var field = data[i];
                result[field.name] = field.value;
            }
            return result;
        }
    };
});
