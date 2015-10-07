 /** @lends defaultEditors */
define(function(require) {
    'use strict';

    /**
     * Maps frontend types into editor views
     *
     * @type {Object}
     * @exports defaultEditors
     */
    var defaultEditors = {
        /**
         * @type {[text-editor-view](../frontend/editor/text-editor-view.md)}
         */
        string: require('orodatagrid/js/app/views/editor/text-editor-view'),
        /**
         * See [datetime-editor-view](../frontend/editor/datetime-editor-view.md) for details
         *
         * @type {Function}
         */
        datetime: require('orodatagrid/js/app/views/editor/datetime-editor-view'),
        /**
         * See [date-editor-view](../frontend/editor/date-editor-view.md) for details
         *
         * @type {Function}
         */
        date: require('orodatagrid/js/app/views/editor/date-editor-view'),
        /**
         * See [number-editor-view](../frontend/editor/number-editor-view.md) for details
         *
         * @type {Function}
         */
        currency: require('orodatagrid/js/app/views/editor/number-editor-view'),
        /**
         * See [number-editor-view](../frontend/editor/number-editor-view.md) for details
         *
         * @type {Function}
         */
        number: require('orodatagrid/js/app/views/editor/number-editor-view'),
        /**
         * See [select-editor-view](../frontend/editor/select-editor-view.md) for details
         *
         * @type {Function}
         */
        select: require('orodatagrid/js/app/views/editor/select-editor-view')
    };

    return defaultEditors;
});
