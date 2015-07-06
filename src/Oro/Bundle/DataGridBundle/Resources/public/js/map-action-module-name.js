define(function() {
    'use strict';
    var moduleNameTemplate = 'oro/datagrid/action/{{type}}-action';

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', type);
    };
});
