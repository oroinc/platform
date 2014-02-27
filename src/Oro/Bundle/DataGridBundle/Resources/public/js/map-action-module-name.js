/*global define*/
define(function () {
    'use strict';
    var moduleNameTemplate = 'oro/datagrid/{{type}}-action';

    return function (type) {
        return moduleNameTemplate.replace('{{type}}', type);
    };
});
