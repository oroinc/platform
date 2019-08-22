define(function(require) {
    'use strict';

    var ControlsPlugin;

    ControlsPlugin = function(editor, options) {
        console.log(editor, arguments)

        _.extend(editor.Panels, {
            addElement: function() {
                console.log('add element', this)
            }
        })
    };

    return ControlsPlugin;
});
