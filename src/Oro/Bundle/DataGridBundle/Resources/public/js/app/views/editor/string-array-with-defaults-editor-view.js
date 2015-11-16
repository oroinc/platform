/** @lends SelectEditorView */
define(function(require) {
    'use strict';

    /**
     *
     * @TODO update doc
     *
     * @augments [StringArrayEditor](./string-array-editor-view.md)
     * @exports StringArrayWithDefaultsEditor
     */
    var StringArrayWithDefaultsEditor;
    var StringArrayEditor = require('./string-array-editor-view');
    require('jquery.select2');

    StringArrayWithDefaultsEditor = StringArrayEditor.extend(/** @exports StringArrayWithDefaultsEditor.prototype */{
    });

    return StringArrayWithDefaultsEditor;
});
