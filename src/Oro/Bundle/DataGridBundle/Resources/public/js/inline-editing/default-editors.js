import string from 'oroform/js/app/views/editor/text-editor-view';
import phone from 'oroform/js/app/views/editor/text-editor-view';
import datetime from 'oroform/js/app/views/editor/datetime-editor-view';
import date from 'oroform/js/app/views/editor/date-editor-view';
import currency from 'oroform/js/app/views/editor/number-editor-view';
import number from 'oroform/js/app/views/editor/number-editor-view';
import integer from 'oroform/js/app/views/editor/number-editor-view';
import decimal from 'oroform/js/app/views/editor/number-editor-view';
import percent from 'oroform/js/app/views/editor/percent-editor-view';
import select from 'oroform/js/app/views/editor/select-editor-view';

/**
 * Maps frontend types to editor views
 *
 * @type {Object}
 * @exports defaultEditors
 */
const defaultEditors = {
    /**
     * Please see [text-editor-view](../../../../FormBundle/Resources/doc/editor/text-editor-view.md) for details
     *
     * @type {function(new:TextEditorView)}
     */
    string,

    /**
     * Please see [text-editor-view](../../../../FormBundle/Resources/doc/editor/text-editor-view.md) for details
     *
     * @type {function(new:TextEditorView)}
     */
    phone,

    /**
     * Please see [datetime-editor-view](../../../../FormBundle/Resources/doc/editor/datetime-editor-view.md)
     * for details
     *
     * @type {Function}
     */
    datetime,

    /**
     * Please see [date-editor-view](../../../../FormBundle/Resources/doc/editor/date-editor-view.md) for details
     *
     * @type {Function}
     */
    date,

    /**
     * Please see [number-editor-view](../../../../FormBundle/Resources/doc/editor/number-editor-view.md)
     * for details
     *
     * @type {Function}
     */
    currency,

    /**
     * Please see [number-editor-view](../../../../FormBundle/Resources/doc/editor/number-editor-view.md)
     * for details
     *
     * @type {Function}
     */
    number,

    /**
     * Please see [number-editor-view](../../../../FormBundle/Resources/doc/editor/number-editor-view.md)
     * for details
     *
     * @type {Function}
     */
    integer,

    /**
     * Please see [number-editor-view](../../../../FormBundle/Resources/doc/editor/number-editor-view.md)
     * for details
     *
     * @type {Function}
     */
    decimal,

    /**
     * Please see [percent-editor-view](../../../../FormBundle/Resources/doc/editor/percent-editor-view.md)
     * for details
     *
     * @type {Function}
     */
    percent,

    /**
     * Please see [select-editor-view](../../../../FormBundle/Resources/doc/editor/select-editor-view.md)
     * for details
     *
     * @type {Function}
     */
    select
};

export default defaultEditors;
