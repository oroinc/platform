/*global define*/
define(function () {
    'use strict';
    var moduleNameTemplate = 'orodatagrid/js/datagrid/action/{{type}}-action';

    return function (type) {
        return moduleNameTemplate.replace('{{type}}', type);
    };
});
