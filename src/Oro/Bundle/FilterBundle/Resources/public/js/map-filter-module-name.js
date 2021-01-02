define(function() {
    'use strict';

    const moduleNameTemplate = 'oro/filter/{{type}}-filter';
    const types = {
        'string': 'choice',
        'choice': 'select',
        'single_choice': 'select',
        'multichoice': 'multiselect',
        'boolean': 'boolean',
        'duplicate': 'select',
        'dictionary': 'dictionary'
    };

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', types[type] || type);
    };
});
