define(function() {
    'use strict';

    var moduleNameTemplate = 'oro/{{type}}-widget';

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', type);
    };
});
