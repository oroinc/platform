define(function(require) {
    'use strict';

    var DatagridSettingsListItemView;
    var template = require('tpl!orodatagrid/templates/datagrid-settings/datagrid-settings-item.html');
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @class DatagridSettingsListItemView
     * @extends BaseView
     */
    DatagridSettingsListItemView = BaseView.extend({
        template: template,

        tagName: 'tr',

        events: {
            'change input[type=checkbox][data-role=renderable]': 'updateModel'
        },

        listen: {
            // for some reason events delegated in view constructor does not work
            'addedToParent': 'delegateEvents',
            // update view on model change
            'change:disabledVisibilityChange model': 'render',
            'change:renderable model': 'updateView'
        },

        /**
         * @property {Boolean}
         */
        addSorting: false,

        /**
         * @inheritDoc
         */
        constructor: function DatagridSettingsListItemView() {
            DatagridSettingsListItemView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            DatagridSettingsListItemView.__super__.render.apply(this, arguments);
            this.$el.toggleClass('renderable', this.model.get('renderable'));
            this.$el.inputWidget('seekAndCreate');
            return this;
        },

        /**
         * Set filter data to model
         * @param {Object} filterModel
         */
        setFilterModel: function(filterModel) {
            this.filterModel = filterModel;
            this.listenTo(this.filterModel, 'change:search', this.render);
        },

        /**
         * Set sorting data
         * @param {Boolean} addSorting
         */
        setSorting: function(addSorting) {
            this.addSorting = addSorting;
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var searchString = this.filterModel.get('search');
            var data = DatagridSettingsListItemView.__super__.getTemplateData.call(this);
            data.cid = this.model.cid;
            data.label = _.escape(data.label);
            if (searchString.length > 0) {
                data.label = this.highlightLabel(data.label, searchString);
            }
            data.addSorting = this.addSorting;
            return data;
        },

        /**
         * Highlight of found label
         * @param {String} label
         * @param {Number} searchString
         */
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

    return DatagridSettingsListItemView;
});
