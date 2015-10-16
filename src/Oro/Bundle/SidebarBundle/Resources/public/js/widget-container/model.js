define([
    'require', 'underscore', 'jquery', 'backbone', '../constants', 'oroui/js/mediator'
], function(require, _, $, Backbone, constants, mediator) {
    'use strict';

    /**
     * @export  orosidebar/js/widget-container/model
     * @class   orosidebar.widgetContainer.Model
     * @extends Backbone.Model
     */
    return Backbone.Model.extend({
        defaults: {
            icon:       '',
            iconClass:  '',
            module:     '',
            position:   0,
            title:      '',
            settings:   {},
            state:      constants.WIDGET_MINIMIZED,
            widgetName: '',
            highlighted: false
        },

        initialize: function() {
            this.stateSnapshot = this.get('state');
            this.isDragged = false;
            this.loadModule();
        },

        loadModule: function() {
            if (!this.deferredModuleLoad) {
                this.deferredModuleLoad = $.Deferred();
                require([this.get('module')], _.bind(function(Widget) {
                    this.module = Widget;
                    this.deferredModuleLoad.resolve(this.module, this);
                }, this), _.bind(function() {
                    this.deferredModuleLoad.reject(arguments);
                }, this));
                this.deferredModulePromise = this.deferredModuleLoad
                    .then(_.bind(this.createController, this))
                    .fail(_.bind(this.onWidgetLoadError, this));
            }
            return this.deferredModulePromise;
        },

        createController: function(Widget) {
            if (this.module.Component) {
                var Component = this.module.Component;
                this.component = new Component({
                    model: this
                });
            }
            return Widget;
        },

        onWidgetLoadError: function() {
            mediator.execute('showErrorMessage', 'Cannot load sidebar widget module "' + this.get('module') + '"');
        },

        /**
         * Toggles state of widget container between minimized and maximized
         */
        toggleState: function() {
            var model = this;
            var state = model.get('state');

            if (state === constants.WIDGET_MAXIMIZED_HOVER) {
                return;
            }

            if (state === constants.WIDGET_MINIMIZED) {
                model.set('state', constants.WIDGET_MAXIMIZED);
            } else {
                model.set('state', constants.WIDGET_MINIMIZED);
            }
            this.save();
        },

        /**
         * Saves state of widget container
         */
        snapshotState: function() {
            this.stateSnapshot = this.get('state');
        },

        /**
         * Restores state of widget container
         */
        restoreState: function() {
            this.set({state: this.stateSnapshot}, {silent: true});
            this.save();
        },

        /**
         * Update from original data
         */
        update: function(widgetData) {
            this.set(_.omit(widgetData, 'settings', 'placement'));
        }
    });
});
