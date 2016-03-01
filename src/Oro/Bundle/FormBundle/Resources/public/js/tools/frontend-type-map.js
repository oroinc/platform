define(function(require) {
    'use strict';
    var frontendTypeMap = {
        tags: {
            viewer: require('orotag/js/app/views/viewer/tags-view'),
            editor: require('orotag/js/app/views/editor/tags-editor-view')
        }
    };
    return frontendTypeMap;
});
