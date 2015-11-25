 /** @lends defaultEditors */
define(function(require) {
    'use strict';

    /**
     * Maps frontend types to editor views
     *
     * @type {Object}
     * @exports defaultEditors
     */
    var defaultEditors = {
        /**
         * Please see [text-editor-view](../frontend/editor/text-editor-view.md) for details
         *
         * @type {function(new:TextEditorView)}
         */
        string: require('oroform/js/app/views/editor/text-editor-view'),

        /**
         * Please see [text-editor-view](../frontend/editor/text-editor-view.md) for details
         *
         * @type {function(new:TextEditorView)}
         */
        phone: require('oroform/js/app/views/editor/text-editor-view'),

        /**
         * Please see [datetime-editor-view](../frontend/editor/datetime-editor-view.md) for details
         *
         * @type {Function}
         */
        datetime: require('oroform/js/app/views/editor/datetime-editor-view'),

        /**
         * Please see [date-editor-view](../frontend/editor/date-editor-view.md) for details
         *
         * @type {Function}
         */
        date: require('oroform/js/app/views/editor/date-editor-view'),

        /**
         * Please see [number-editor-view](../frontend/editor/number-editor-view.md) for details
         *
         * @type {Function}
         */
        currency: require('oroform/js/app/views/editor/number-editor-view'),

        /**
         * Please see [number-editor-view](../frontend/editor/number-editor-view.md) for details
         *
         * @type {Function}
         */
        number: require('oroform/js/app/views/editor/number-editor-view'),

        /**
         * Please see [number-editor-view](../frontend/editor/number-editor-view.md) for details
         *
         * @type {Function}
         */
        integer: require('oroform/js/app/views/editor/number-editor-view'),

        /**
         * Please see [number-editor-view](../frontend/editor/number-editor-view.md) for details
         *
         * @type {Function}
         */
        decimal: require('oroform/js/app/views/editor/number-editor-view'),

        /**
         * Please see [percent-editor-view](../frontend/editor/percent-editor-view.md) for details
         *
         * @type {Function}
         */
        percent: require('oroform/js/app/views/editor/percent-editor-view'),

        /**
         * Please see [select-editor-view](../frontend/editor/select-editor-view.md) for details
         *
         * @type {Function}
         */
        select: require('oroform/js/app/views/editor/select-editor-view')
    };

    return defaultEditors;
});
