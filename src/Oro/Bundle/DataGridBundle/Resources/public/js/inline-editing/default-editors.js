define(function(require) {
    'use strict';

    var defaultEditors = {
        string: require('orodatagrid/js/app/views/editor/text-editor-view'),
        datetime: require('orodatagrid/js/app/views/editor/datetime-editor-view'),
        date: require('orodatagrid/js/app/views/editor/date-editor-view'),
        currency: require('orodatagrid/js/app/views/editor/number-editor-view'),
        number: require('orodatagrid/js/app/views/editor/number-editor-view')
    };

    return defaultEditors;
});
