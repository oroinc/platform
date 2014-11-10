/*jslint browser:true, nomen:true*/
/*global define, alert*/
define([
    'jquery',
    'underscore',
    'oroui/js/app/views/base/view',
    'routing',
    'orolocale/js/formatter/datetime'
], function ($, _, BaseView, routing, dateTimeFormatter) {
    'use strict';

    var ActivityView;
    ActivityView = BaseView.extend({
        options: {
            configuration: {},
            template: null,
            urls: {
                viewItem: null,
                updateItem: null,
                deleteItem: null
            }
        },
        attributes: {
            'class': 'list-item'
        },
        events: {
            'click .item-view-button': 'onView',
            'click .item-edit-button': 'onEdit',
            'click .item-remove-button': 'onDelete',
            'click .accordion-toggle': 'onToggle'
        },
        listen: {
            'change model': '_onModelChanged'
        },

        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.collapsed = true;
            if (this.options.template) {
                this.template = _.template($(this.options.template).html());
            }
            if (this.model.attributes.relatedActivityClass) {
                var templateName = '#template-activity-item-' + this.model.attributes.relatedActivityClass;
                templateName = templateName.replace(/\\/g, '_');
                this.template = _.template($(templateName).html());
            }
        },

        getTemplateData: function () {
            var data = ActivityView.__super__.getTemplateData.call(this);

            data.collapsed = this.collapsed;
            data.createdAt = dateTimeFormatter.formatDateTime(data.createdAt);
            data.updatedAt = dateTimeFormatter.formatDateTime(data.updatedAt);
            data.relatedActivityClass = _.escape(data.relatedActivityClass);
            data.actor_url = routing.generate('oro_user_view', {'id': data.owner_id});

            return data;
        },

        onView: function () {
            this.model.collection.trigger('toView', this.model);
        },

        onEdit: function () {
            this.model.collection.trigger('toEdit', this.model);
        },

        onDelete: function () {
            this.model.collection.trigger('toDelete', this.model);
        },

        onToggle: function (e) {
            e.preventDefault();
            this.toggle();
        },

        /**
         * Collapses/expands view elements
         *
         * @param {boolean=} collapse
         */
        toggle: function (collapse) {
            if (_.isUndefined(collapse)) {
                collapse = !this.isCollapsed();
            }
            this.$('.accordion-toggle').toggleClass('collapsed', collapse);
            this.$('.collapse').toggleClass('in', !collapse);
        },

        isCollapsed: function () {
            return this.$('.accordion-toggle').hasClass('collapsed');
        },

        _onModelChanged: function () {
            this.collapsed = this.isCollapsed();
            this.render();
        }
    });

    return ActivityView;
});
