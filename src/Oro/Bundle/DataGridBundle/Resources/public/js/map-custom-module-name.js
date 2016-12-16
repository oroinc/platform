define(function() {
    'use strict';

    var types = {
        productServerRenderGrid: 'oroproduct/js/app/datagrid/backend-grid',
        productPageableCollection: 'oroproduct/js/app/datagrid/backend-pageable-collection'
    };

    return function(type) {
        return types[type] || null;
    };
});
