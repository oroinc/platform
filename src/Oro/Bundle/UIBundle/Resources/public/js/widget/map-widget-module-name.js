define(function() {
    'use strict';

    const moduleNameTemplate = 'oro/{{type}}-widget';

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', type);
    };
});
