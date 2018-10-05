define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var Backbone = require('backbone');
    var componentContainerMixin = require('oroui/js/app/components/base/component-container-mixin');
    var tools = require('oroui/js/tools');

    var console = window.console;

    var OriginalBackboneView = Backbone.View;
    Backbone.View = function(original) {
        this.subviews = [];
        this.subviewsByName = {};
        OriginalBackboneView.apply(this, arguments);
    };
    _.extend(Backbone.View, OriginalBackboneView, {
        RENDERING_TIMEOUT: 30000 // 30s
    });
    Backbone.View.prototype = OriginalBackboneView.prototype;

    // Backbone.View
    Backbone.View.prototype.subviews = null;
    Backbone.View.prototype.subviewsByName = null;
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

        if (this.deferredRender) {
            this._rejectDeferredRender();
        }
        this.disposeControls();
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
        }

        properties = ['el', '$el', 'options', 'model', 'collection', 'subviews', 'subviewsByName', '_callbacks'];
        for (_j = 0, _len1 = properties.length; _j < _len1; _j++) {
            prop = properties[_j];
            delete this[prop];
        }

        this.disposed = true;
        return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
    };
    Backbone.View.prototype._ensureElement = _.wrap(Backbone.View.prototype._ensureElement, function(_ensureElement) {
        // if the element was passed with options -- preserve it in the DOM, if `keepElement` is not set to `false`
        if (this.el && this.keepElement !== false) {
            this.keepElement = true;
        }
        return _ensureElement.call(this);
    });
    Backbone.View.prototype.setElement = _.wrap(Backbone.View.prototype.setElement, function(setElement, element) {
        if (this.$el) {
            this.disposePageComponents();
        }
        return setElement.call(this, element);
    });
    Backbone.View.prototype.eventNamespace = function() {
        return '.delegateEvents' + this.cid;
    };
    Backbone.View.prototype.getLayoutElement = function() {
        return this.$el;
    };
    Backbone.View.prototype.initControls = function() {
        Backbone.mediator.execute({name: 'layout:init', silent: true}, this.getLayoutElement());
    };
    Backbone.View.prototype.disposeControls = function() {
        Backbone.mediator.execute({name: 'layout:dispose', silent: true}, this.$el);
    };
    Backbone.View.prototype.initLayout = function(options) {
        // initializes controls in layout
        this.initControls();
        // initializes page components
        var initPromise = this.initPageComponents(options);
        if (!this.deferredRender) {
            this._deferredRender();
            initPromise.always(_.bind(this._resolveDeferredRender, this));
        }
        return initPromise.fail(function(e) {
            console.error(e);
        });
    };
    /**
     * Create flag of deferred render
     *
     * @protected
     */
    Backbone.View.prototype._deferredRender = function() {
        if (this.deferredRender) {
            // reject previous deferredRender object due to new rendering process is initiated
            this._rejectDeferredRender();
        }
        this.deferredRender = $.Deferred();
        this.deferredRender.timeoutID =
            setTimeout(function() {
                var xpath = tools.getElementXPath(this.el);
                var error = new Error('Rendering timeout for view of element: "' + xpath + '"');
                this._rejectDeferredRender(error);
            }.bind(this), Backbone.View.RENDERING_TIMEOUT);
    };

    /**
     * Resolves deferred render
     *
     * @protected
     */
    Backbone.View.prototype._resolveDeferredRender = function() {
        if (this.deferredRender) {
            var promises = [];
            var resolve = _.bind(function() {
                clearTimeout(this.deferredRender.timeoutID);
                this.deferredRender.resolve(this);
                delete this.deferredRender;
            }, this);

            if (this.subviews.length) {
                _.each(this.subviews, function(subview) {
                    if (subview.deferredRender) {
                        promises.push(subview.getDeferredRenderPromise());
                    }
                });
            }

            if (promises.length) {
                // even with empty list of promises $.when takes about 1.4ms
                $.when.apply($, promises).done(resolve);
            } else {
                resolve();
            }
        }
    };

    /**
     * Rejects deferred render promise
     *
     * @protected
     */
    Backbone.View.prototype._rejectDeferredRender = function(error) {
        if (this.deferredRender) {
            clearTimeout(this.deferredRender.timeoutID);
            if (error) {
                error.target = this;
                this.deferredRender.reject(error);
            } else {
                this.deferredRender.reject();
            }
            delete this.deferredRender;
        }
    };

    /**
     * Create promise object from deferredRender with reference to a targetView
     *
     * @return {Promise|null}
     */
    Backbone.View.prototype.getDeferredRenderPromise = function() {
        return this.deferredRender ? this.deferredRender.promise({targetView: this}) : null;
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
