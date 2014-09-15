/*jslint nomen:true*/
/*global define*/
define([
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    './collection'
], function (Backbone, _, __, GridViewsCollection) {
    'use strict';
    var $, GridViewsView;
    $ = Backbone.$;

    /**
     * Datagrid views widget
     *
     * @export  orodatagrid/js/datagrid/grid-views/view
     * @class   orodatagrid.datagrid.GridViewsView
     * @extends Backbone.View
     */
    GridViewsView = Backbone.View.extend({
        className: 'btn-group grid-views',

        /** @property */
        events: {
            "click a": "onChange"
        },

        /** @property */
        template: _.template(
            '<button data-toggle="dropdown" class="btn dropdown-toggle <% if (disabled) { %>disabled<% } %>">' +
                '<%=  current %>' + '<span class="caret"></span>' +
            '</button>' +
            '<ul class="dropdown-menu pull-right">' +
                '<% _.each(choices, function (choice) { %>' +
                    '<li><a href="#" data-value="' + '<%= choice.value %>' + '">' + '<%= choice.label %>' + '</a></li>' +
                '<% }); %>' +
            '</ul>'
        ),

        /** @property */
        enabled: true,

        /** @property */
        choices: [],

        /** @property */
        viewsCollection: GridViewsCollection,

        /**
         * Initializer.
         *
         * @param {Object} options
         * @param {Backbone.Collection} options.collection
         * @param {Boolean} [options.enable]
         * @param {Array}   [options.choices]
         * @param {Array}   [options.views]
         */
        initialize: function (options) {
            options = options || {};

            if (!options.collection) {
                throw new TypeError("'collection' is required");
            }

            if (options.choices) {
                this.choices = _.union(this.choices, options.choices);
            }

            this.collection = options.collection;
            this.enabled = options.enable != false;

            this.listenTo(this.collection, "updateState", this.render);
            this.listenTo(this.collection, "beforeFetch", this.render);

            options.views = options.views || [];
            this.viewsCollection = new this.viewsCollection(options.views);

            GridViewsView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }
            this.viewsCollection.dispose();
            delete this.viewsCollection;
            GridViewsView.__super__.dispose.call(this);
        },

        /**
         * Disable view selector
         *
         * @return {*}
         */
        disable: function () {
            this.enabled = false;
            this.render();

            return this;
        },

        /**
         * Enable view selector
         *
         * @return {*}
         */
        enable: function () {
            this.enabled = true;
            this.render();

            return this;
        },

        /**
         * Select change event handler
         *
         * @param {Event} e
         */
        onChange: function (e) {
            e.preventDefault();
            var value = $(e.target).data('value');
            if (value !== this.collection.state.gridView) {
                this.changeView(value);
            }
        },

        /**
         * Updates collection
         *
         * @param gridView
         * @returns {*}
         */
        changeView: function (gridView) {
            var view, viewState;
            view = this.viewsCollection.get(gridView);

            if (view) {
                viewState = _.extend({}, this.collection.initialState, view.toGridState());
                this.collection.updateState(viewState);
                this.collection.fetch({reset: true});
            }

            return this;
        },

        render: function () {
            var currentView, currentViewLabel, html;
            this.$el.empty();

            if (this.choices.length > 0) {
                currentView = _.filter(this.choices, function (item) {
                    return item.value == this.collection.state.gridView;
                }, this);

                currentViewLabel = currentView.length ? _.first(currentView).label : __('Please select view');
                html = this.template({
                    disabled: !this.enabled,
                    choices: this.choices,
                    current: currentViewLabel
                });

                this.$el.append(html);
            }

            return this;
        }
    });

    return GridViewsView;
});
