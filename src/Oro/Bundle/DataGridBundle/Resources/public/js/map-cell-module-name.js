define(function() {
    'use strict';

    var moduleNameTemplate = 'oro/datagrid/cell/{{type}}-cell';
    var types = {
            integer:   'number',
            decimal:   'number',
            percent:   'number',
            currency:  'number',
            array:     'string',
            simple_array: 'string'
        };

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', types[type] || type);
    };
});
