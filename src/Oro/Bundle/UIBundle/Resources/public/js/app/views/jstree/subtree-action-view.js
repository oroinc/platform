define(function(require) {
    'use strict';

    var SubTreeActionView;
    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');

    SubTreeActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            itemsLabel: __('oro.ui.jstree.actions.subitems.itemsLabel'),
            doNotSelectIcon: 'long-arrow-up',
            doNotSelectLabel: 'oro.ui.jstree.actions.subitems.do_not_select',
            selectIcon: 'long-arrow-down',
            selectLabel: 'oro.ui.jstree.actions.subitems.select'
        }),

        selectSubTree: false,

        /**
         * @inheritDoc
         */
        constructor: function SubTreeActionView() {
            SubTreeActionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            SubTreeActionView.__super__.initialize.apply(this, arguments);
            this.options.selectLabel = __(this.options.selectLabel, {
                itemsLabel: this.options.itemsLabel
            });
            this.options.doNotSelectLabel = __(this.options.doNotSelectLabel, {
                itemsLabel: this.options.itemsLabel
            });
        },

        render: function() {
            if (this.selectSubTree) {
                this.options.icon = this.options.doNotSelectIcon;
                this.options.label = this.options.doNotSelectLabel;
            } else {
                this.options.icon = this.options.selectIcon;
                this.options.label = this.options.selectLabel;
            }
            return SubTreeActionView.__super__.render.apply(this, arguments);
        },

        onClick: function() {
            this.selectSubTree = !this.selectSubTree;
            this.render();
            this.options.$tree.trigger('select-subtree-item:change', {selectSubTree: this.selectSubTree});
        }
    });

    return SubTreeActionView;
});
