define(function() {
    'use strict';

    var moduleNameTemplate = '{{type}}-grid';
    var types = {
        productServerRenderGrid: 'oroproduct/js/app/datagrid/backend'
    };

    return function(type) {
        return moduleNameTemplate.replace('{{type}}', types[type] || type);
    };
});
