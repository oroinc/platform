define(function() {
    'use strict';

    const moduleNameTemplate = 'oro/datagrid/cell/{{type}}-cell';
    const types = {
        'integer': 'number',
        'decimal': 'number',
        'percent': 'number',
        'currency': 'number',
        'array': 'string',
        'simple_array': 'string',
        'enum': 'string'
    };

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', types[type] || type);
    };
});
