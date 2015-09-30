define(function(require) {
    'use strict';

    var ColumnManagerItemView;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');

    ColumnManagerItemView = BaseView.extend({
        template: require('tpl!orodatagrid/templates/column-manager/column-manager-item.html'),
        tagName: 'tr',

        events: {
            'change input[type=checkbox][data-role=renderable]': 'updateModel'
        },

        listen: {
            // for some reason events delegated in view constructor does not work
            addedToParent: 'delegateEvents',
            // update view on model change
            'change:disabledVisibilityChange model': 'render',
            'change:renderable model': 'updateView'
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var data = ColumnManagerItemView.__super__.getTemplateData.call(this);
            data.cid = this.model.cid;
            return data;
        },

        /**
         * Handles DOM event for a column visibility change and updates model's attribute
         *
         * @param {jQuery.Event} e
         */
        updateModel: function(e) {
            var renderable = $(e.target).prop('checked');
            this.model.set('renderable', renderable);
        },

        /**
         * Handles model event and updates the view
         */
        updateView: function() {
            var renderable = this.model.get('renderable');
            this.$('input[type=checkbox][data-role=renderable]').prop('checked', renderable);
        }
    });

    return ColumnManagerItemView;
});
