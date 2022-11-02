define([
    './abstract-action',
    'orodatagrid/js/datagrid/dropdown-select-choice-launcher'
], function(AbstractAction, DropdownSelectChoiceLauncher) {
    'use strict';

    /**
     * Resets collection to initial state
     *
     * @export  oro/datagrid/action/reset-collection-action
     * @class   oro.datagrid.action.ResetCollectionAction
     * @extends oro.datagrid.action.AbstractAction
     */
    const SelectDataAppearanceAction = AbstractAction.extend({
        /** @property {Function} */
        launcher: DropdownSelectChoiceLauncher,

        /** @property oro.PageableCollection */
        collection: undefined,

        /**
         * @inheritdoc
         */
        constructor: function SelectDataAppearanceAction(options) {
            SelectDataAppearanceAction.__super__.constructor.call(this, options);
        },

        /**
         * Initialize action
         *
         * @param {Object} options
         * @param {oro.PageableCollection} options.collection Collection
         * @throws {TypeError} If collection is undefined
         */
        initialize: function(options) {
            const opts = options || {};

            if (!opts.datagrid) {
                throw new TypeError('"datagrid" is required');
            }
            this.datagrid = opts.datagrid;

            SelectDataAppearanceAction.__super__.initialize.call(this, options);
        },

        /**
         * Execute reset collection
         */
        execute: function(data) {
            this.datagrid.changeAppearance(data.key, data.item.options);
        }
    });

    return SelectDataAppearanceAction;
});
