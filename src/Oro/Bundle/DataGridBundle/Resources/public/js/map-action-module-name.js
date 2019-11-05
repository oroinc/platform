define(function() {
    'use strict';

    const moduleNameTemplate = 'oro/datagrid/action/{{type}}-action';

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', type);
    };
});
