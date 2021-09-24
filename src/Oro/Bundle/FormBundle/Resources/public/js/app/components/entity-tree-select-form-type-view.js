define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const BaseTreeView = require('oroui/js/app/views/jstree/base-tree-view');

    /**
     * Additional option:
     *  - fieldSelector - selector for field ID field
     */
    const EntityTreeSelectFormTypeView = BaseTreeView.extend({
        /**
         * @property {Object}
         */
        $fieldSelector: null,

        /**
         * @inheritdoc
         */
        constructor: function EntityTreeSelectFormTypeView(options) {
            EntityTreeSelectFormTypeView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            EntityTreeSelectFormTypeView.__super__.initialize.call(this, options);
            if (!this.$tree) {
                return;
            }

            const fieldSelector = options.fieldSelector;
            if (!fieldSelector) {
                return;
            }
            this.$fieldSelector = $(fieldSelector);

            this.$tree.on('select_node.jstree', _.bind(this.onSelect, this));
            this.$tree.on('deselect_node.jstree', _.bind(this.onDeselect, this));
        },

        /**
         * Set category ID to field value
         *
         * @param {Object} node
         * @param {Object} selected
         */
        onSelect: function(node, selected) {
            this.$fieldSelector.val(selected.node.id);
        },

        /**
         * Clear field value
         */
        onDeselect: function() {
            this.$fieldSelector.val('');
        }
    });

    return EntityTreeSelectFormTypeView;
});
