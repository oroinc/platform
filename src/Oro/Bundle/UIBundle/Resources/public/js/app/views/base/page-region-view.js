define([
    'underscore',
    './view',
    'oroui/js/mediator'
], function(_, BaseView, mediator) {
    'use strict';

    var PageRegionView;

    PageRegionView = BaseView.extend({
        listen: {
            'page:update mediator': 'onPageUpdate'
        },

        data: null,
        pageItems: [],

        /**
         * Defer object,
         * helps to notify environment that the view has updated its content
         */
        deferredRender: null,

        /**
         * Handles page load event
         *  - stores from page data corresponded page items
         *  - renders view
         *  - dispose cached data
         *
         * @param {Object} pageData
         * @param {Object} actionArgs arguments of controller's action point
         * @param {Object} jqXHR
         * @param {Array.<Object>} promises collection
         */
        onPageUpdate: function(pageData, actionArgs, jqXHR, promises) {
            this.data = _.pick(pageData, this.pageItems);
            this.actionArgs = actionArgs;
            this.disposePageComponents();
            this.render();
            this.data = null;
            this.actionArgs = null;
            if (this.deferredRender) {
                // collects initialization promises
                promises.push(this.deferredRender.promise(this));
            }
        },

        /**
         * Renders the view
         *  - prevents rendering a view without page data
         *  - disposes old content before rendering
         *  - initializes page components
         *
         * @override
         */
        render: function() {
            var data;
            data = this.getTemplateData();

            if (!data) {
                // no data, it is initial auto render, skip rendering
                return this;

            } else if (!_.isEmpty(data)) {
                // data object is not empty, dispose old content and render new
                this.disposePageComponents();
                mediator.execute('layout:dispose', this.$el);
                PageRegionView.__super__.render.call(this);
            }

            // starts deferred initialization
            this._deferredRender();
            // initialize components in view's markup
            this.initLayout()
                .done(_.bind(this._resolveDeferredRender, this));

            return this;
        },

        /**
         * Gets cached page data
         *
         * @returns {Object}
         * @override
         */
        getTemplateData: function() {
            return this.data;
        }
    });

    return PageRegionView;
});
