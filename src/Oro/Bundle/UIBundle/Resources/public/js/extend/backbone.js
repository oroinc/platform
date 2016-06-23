define([
    'jquery',
    'underscore',
    'backbone',
    'oroui/js/app/components/base/component-container-mixin'
], function($, _, Backbone, componentContainerMixin) {
    'use strict';

    // Backbone.View
    Backbone.View.prototype.subviews = [];
    Backbone.View.prototype.subviewsByName = {};
    _.extend(Backbone.View.prototype, componentContainerMixin);

    Backbone.View.prototype.subview = function(name, view) {
        var subviews = this.subviews;
        var byName = this.subviewsByName;
        if (name && view) {
            this.removeSubview(name);
            subviews.push(view);
            byName[name] = view;
            return view;
        } else if (name) {
            return byName[name];
        }
    };

    Backbone.View.prototype.removeSubview = function(nameOrView) {
        var byName;
        var index;
        var name;
        var otherName;
        var otherView;
        var subviews;
        var view;
        if (!nameOrView) {
            return;
        }
        subviews = this.subviews;
        byName = this.subviewsByName;
        if (typeof nameOrView === 'string') {
            name = nameOrView;
            view = byName[name];
        } else {
            view = nameOrView;
            for (otherName in byName) {
                if (byName.hasOwnProperty(otherName)) {
                    otherView = byName[otherName];
                    if (otherView !== view) {
                        continue;
                    }
                    name = otherName;
                    break;
                }
            }
        }
        if (!(name && view && view.dispose)) {
            return;
        }
        view.dispose();
        index = _.indexOf(subviews, view);
        if (index !== -1) {
            subviews.splice(index, 1);
        }
        return delete byName[name];
    };

    Backbone.View.prototype.disposed = false;
    Backbone.View.prototype.dispose = function() {
        var prop;
        var properties;
        var subview;
        var _i;
        var _j;
        var _len;
        var _len1;
        var _ref;
        if (this.disposed || !this.$el) {
            return;
        }

        this.disposePageComponents();
        this.trigger('dispose', this);

        _ref = _.toArray(this.subviews);
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            subview = _ref[_i];
            subview.dispose();
        }
        Backbone.mediator.unsubscribe(null, null, this);
        this.off();
        this.stopListening();

        if (this.keepElement === false) {
            this.$el.remove();
        } else {
            this.undelegateEvents();
            this.$el.removeData();
        }

        properties = ['el', '$el', 'options', 'model', 'collection', 'subviews', 'subviewsByName', '_callbacks'];
        for (_j = 0, _len1 = properties.length; _j < _len1; _j++) {
            prop = properties[_j];
            delete this[prop];
        }

        this.disposed = true;
        return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
    };
    Backbone.View.prototype.eventNamespace = function() {
        return '.delegateEvents' + this.cid;
    };
    Backbone.View.prototype.getLayoutElement = function() {
        return this.$el;
    };
    Backbone.View.prototype.initLayout = function(options) {
        // initializes layout
        Backbone.mediator.execute('layout:init', this.getLayoutElement());
        // initializes page components
        return this.initPageComponents(options);
    };
    /**
     * Create flag of deferred render
     *
     * @protected
     */
    Backbone.View.prototype._deferredRender = function() {
        this.deferredRender = $.Deferred();
    };
    /**
     * Resolves deferred render
     *
     * @protected
     */
    Backbone.View.prototype._resolveDeferredRender = function() {
        var self = this;
        if (this.deferredRender) {
            var promises = [];

            if (this.subviews.length) {
                _.each(this.subviews, function(subview) {
                    if (subview.deferredRender) {
                        var promise = $.Deferred();
                        promises.push(promise);
                        subview.deferredRender.done(function() {
                            promise.resolve();
                        });
                    }
                });
            }
            $.when.apply($, promises).done(function() {
                self.deferredRender.resolve(self);
                delete self.deferredRender;
            });
        }
    };

    // Backbone.Model
    Backbone.Model.prototype.disposed = false;
    Backbone.Model.prototype.dispose = function() {
        var prop;
        var properties;
        var _i;
        var _len;
        if (this.disposed) {
            return;
        }
        this.trigger('dispose', this);
        Backbone.mediator.unsubscribe(null, null, this);
        this.stopListening();
        this.off();
        properties = ['collection', 'attributes', 'changed', '_escapedAttributes', '_previousAttributes',
            '_silent', '_pending', '_callbacks'];
        for (_i = 0, _len = properties.length; _i < _len; _i++) {
            prop = properties[_i];
            delete this[prop];
        }
        this.disposed = true;
        return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
    };

    // Backbone.Collection
    Backbone.Collection.prototype.disposed = false;
    Backbone.Collection.prototype.dispose = function() {
        var prop;
        var properties;
        var _i;
        var _len;
        if (this.disposed) {
            return;
        }
        this.trigger('dispose', this);
        this.reset([], {
            silent: true
        });
        Backbone.mediator.unsubscribe(null, null, this);
        this.stopListening();
        this.off();
        properties = ['model', 'models', '_byId', '_byCid', '_callbacks'];
        for (_i = 0, _len = properties.length; _i < _len; _i++) {
            prop = properties[_i];
            delete this[prop];
        }
        this.disposed = true;
        return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
    };

    return Backbone;
});
