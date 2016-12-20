define(function(require) {
    'use strict';

    var SubcategoriesActionView;
    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    var _ = require('underscore');

    SubcategoriesActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            doNotSelectIcon: 'long-arrow-up',
            doNotSelectLabel: _.__('oro.ui.jstree.actions.subcategories.do_not_select'),
            selectIcon: 'long-arrow-down',
            selectLabel: _.__('oro.ui.jstree.actions.subcategories.select')
        }),

        selectSubcategories: false,

        render: function() {
            if (this.selectSubcategories) {
                this.options.icon = this.options.doNotSelectIcon;
                this.options.label = this.options.doNotSelectLabel;
            } else {
                this.options.icon = this.options.selectIcon;
                this.options.label = this.options.selectLabel;
            }
            return SubcategoriesActionView.__super__.render.apply(this, arguments);
        },

        onClick: function() {
            this.selectSubcategories = !this.selectSubcategories;
            this.render();
            this.options.$tree.trigger('select-subcategories:change', {selectSubcategories: this.selectSubcategories});
        }
    });

    return SubcategoriesActionView;
});
