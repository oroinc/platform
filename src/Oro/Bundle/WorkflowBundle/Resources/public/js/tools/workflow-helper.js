define(function(require) {
    'use strict';

    const _ = require('underscore');

    /**
     * @export  oroworkflow/js/tools/workflow-helper
     */
    return {
        getNameByString: function(str, prefix) {
            str = (prefix || '') + str; // Add prefix to string
            str = str
                .toLowerCase() // Convert to lowercase
                .replace(/[^A-Za-z\s_-]+/g, '') // Remove all non latin symbols
                .replace(/\s+|\-+/g, '_') // Replace spaces and - with underscore
                .replace(/__+/g, '_'); // Remove duplicated underscores;

            return str + '_' + this.getRandomId();
        },

        getRandomId: function() {
            return Math.random().toString(16).slice(2);
        },

        getFormData: function($form) {
            const data = $form.serializeArray();
            const result = {};
            for (let i = 0; i < data.length; i++) {
                const field = data[i];
                let name = field.name;

                let fieldNameParts = name.match(/\[(\w+)\]$/);
                if (fieldNameParts) {
                    name = fieldNameParts[1];
                    result[name] = field.value;
                    continue;
                }

                fieldNameParts = name.match(/\[(\w+)\]\[\]$/);
                if (fieldNameParts) {
                    name = fieldNameParts[1];
                    if (typeof result[name] === 'undefined') {
                        result[name] = [];
                    }

                    result[name].push(field.value);
                    continue;
                }

                result[name] = field.value;
            }
            return result;
        },

        deepClone: function(obj) {
            const result = _.clone(obj);
            for (const k in obj) {
                if (obj.hasOwnProperty(k) && _.isObject(obj[k])) {
                    obj[k] = this.deepClone(obj[k]);
                }
            }

            return result;
        }
    };
});
