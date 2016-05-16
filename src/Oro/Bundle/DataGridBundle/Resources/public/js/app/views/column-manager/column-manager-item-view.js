define(function(require) {
    'use strict';

    var ColumnManagerItemView;
    var $ = require('jquery');
    var _ = require('underscore');
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

        render: function() {
            ColumnManagerItemView.__super__.render.apply(this, arguments);
            this.$el.toggleClass('renderable', this.model.get('renderable'));
            return this;
        },

        setFilterModel: function(filterModel) {
            this.filterModel = filterModel;
            this.listenTo(this.filterModel, 'change:search', this.render);
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var searchString = this.filterModel.get('search');
            var data = ColumnManagerItemView.__super__.getTemplateData.call(this);
            data.cid = this.model.cid;
            data.label = _.escape(data.label);
            if (searchString.length > 0) {
                data.label = this.highlightLabel(data.label, searchString);
            }
            return data;
        },

        highlightLabel: function(label, searchString) {
            var result = label;
            var length = searchString.length;
            var start = label.toLowerCase().indexOf(searchString.toLowerCase());
            if (start !== -1) {
                result = label.substr(0, start) +
                    '<span class="column-filter-match">' +
                    label.substr(start, length) +
                    '</span>' +
                    label.substr(start + length);
            }
            return result;
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
            this.$el.toggleClass('renderable', renderable);
        }
    });

    return ColumnManagerItemView;
});
