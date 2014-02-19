/*global define*/
define(function () {
    'use strict';
    var moduleNameTemplate = 'oro/datafilter/{{type}}-filter',
        types = {
            string:      'choice',
            choice:      'select',
            selectrow:   'select-row',
            multichoice: 'multiselect',
            boolean:     'select'
        };

    return function (type) {
        return moduleNameTemplate.replace('{{type}}', types[type] || type);
    };
});
