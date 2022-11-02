define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Backbone = require('backbone');
    const componentContainerMixin = require('oroui/js/app/components/base/component-container-mixin');
    const loadModules = require('oroui/js/app/services/load-modules');
    const tools = require('oroui/js/tools');
    const pageVisibilityTracker = require('oroui/js/tools/page-visibility-tracker');

    const console = window.console;

    const OriginalBackboneView = Backbone.View;
    Backbone.View = function(options) {
        Object.assign(this, _.pick(options, this.optionNames));
        this.subviews = [];
        this.subviewsByName = {};
        OriginalBackboneView.call(this, options);
    };
    _.extend(Backbone.View, OriginalBackboneView, {
        RENDERING_TIMEOUT: 30000 // 30s
    });
    Backbone.View.prototype = OriginalBackboneView.prototype;
    Backbone.View.prototype.constructor = Backbone.View;

    const original = _.pick(Backbone.View.prototype, 'remove');

    // Backbone.View
    Backbone.View.prototype.subviews = null;
    Backbone.View.prototype.subviewsByName = null;
    _.extend(Backbone.View.prototype, componentContainerMixin);

    Backbone.View.prototype.subview = function(name, view) {
        const subviews = this.subviews;
        const byName = this.subviewsByName;
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
        let name;
        let otherName;
        let otherView;
        let view;
        if (!nameOrView) {
            return;
        }
        const subviews = this.subviews;
        const byName = this.subviewsByName;
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
        const index = _.indexOf(subviews, view);
        if (index !== -1) {
            subviews.splice(index, 1);
        }
        return delete byName[name];
    };

    Backbone.View.prototype.disposed = false;
    Backbone.View.prototype.dispose = function() {
        let prop;
        let subview;
        let _i;
        let _j;
        let _len;
        let _len1;
        if (this.disposed || !this.$el) {
            return;
        }

        if (this.deferredRender) {
            this._rejectDeferredRender();
        }
        this.disposeControls();
        this.disposePageComponents();
        this.trigger('dispose', this);

        const _ref = _.toArray(this.subviews);
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
            subview = _ref[_i];
            subview.dispose();
        }

        Backbone.mediator.unsubscribe(null, null, this);
        this.off();
        this.stopListening();

        if (this.keepElement === false) {
            this.remove();
        } else {
            this.undelegateEvents();
        }

        const properties = ['el', '$el', 'options', 'model', 'collection', 'subviews', 'subviewsByName', '_callbacks'];
        for (_j = 0, _len1 = properties.length; _j < _len1; _j++) {
            prop = properties[_j];
            delete this[prop];
        }

        this.disposed = true;
        return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
    };

    /**
     * Copied original `_ensureElement` method and changed:
     *  - added with support extra `_attributes` set, collected from prototype chain
     *  - `keepElement` is true by default, is the element was passed within options
     */
    Backbone.View.prototype._ensureElement = function() {
        if (!this.el) {
            const attrs = this._collectAttributes();
            if (this.id) attrs.id = _.result(this, 'id');
            if (this.className) attrs['class'] = _.result(this, 'className');
            this.setElement(this._createElement(_.result(this, 'tagName')));
            this._setAttributes(attrs);
        } else {
            // if the element was passed within options -- preserve it in the DOM
            if (this.keepElement !== false) {
                this.keepElement = true;
            }
            this.setElement(_.result(this, 'el'));
        }
    };
    Backbone.View.prototype._collectAttributes = function() {
        const attrsSet = tools.getAllPropertyVersions(this, '_attributes')
            .map(attrs => typeof attrs === 'function' ? attrs.call(this) : attrs);
        return Object.assign({}, ...attrsSet, _.result(this, 'attributes'));
    };
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
        const initPromise = this.initPageComponents(options);
        if (!this.deferredRender) {
            this._deferredRender();
            initPromise.always(this._resolveDeferredRender.bind(this));
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
            pageVisibilityTracker.setTimeout(function() {
                const xpath = tools.getElementXPath(this.el);
                const error = new Error('Rendering timeout for view of element: "' + xpath + '"');
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
            const promises = [];
            const resolve = () => {
                pageVisibilityTracker.clearTimeout(this.deferredRender.timeoutID);
                this.deferredRender.resolve(this);
                delete this.deferredRender;
            };

            if (this.subviews.length) {
                _.each(this.subviews, function(subview) {
                    if (subview.deferredRender) {
                        promises.push(subview.getDeferredRenderPromise());
                    }
                });
            }

            if (promises.length) {
                // even with empty list of promises $.when takes about 1.4ms
                $.when(...promises).done(resolve);
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
        let prop;
        let _i;
        let _len;
        if (this.disposed) {
            return;
        }
        this.trigger('dispose', this);
        Backbone.mediator.unsubscribe(null, null, this);
        this.stopListening();
        this.off();
        const properties = ['collection', 'attributes', 'changed', '_escapedAttributes', '_previousAttributes',
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
        let prop;
        let _i;
        let _len;
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
        const properties = ['model', 'models', '_byId', '_byCid', '_callbacks'];
        for (_i = 0, _len = properties.length; _i < _len; _i++) {
            prop = properties[_i];
            delete this[prop];
        }
        this.disposed = true;
        return typeof Object.freeze === 'function' ? Object.freeze(this) : void 0;
    };

    Backbone.View.prototype.remove = function() {
        // Since we often override original `delegateEvents` to bind events on elements placed outside view el
        // it needs call `undelegateEvents` even view el is going to be removed
        this.undelegateEvents();
        // Before remove container should to destroy some plugins and widgets from child elements
        this.disposeControls();

        return original.remove.call(this);
    };

    // Backbone.Events
    /**
     * Wraps original `listenTo` method and makes the event handler first in order
     *
     * @param {Object} obj
     * @param {string} name
     * @param {Function} callback
     * @return {Backbone.Events}
     */
    Backbone.Events.firstListenTo = function(obj, name, callback) {
        this.listenTo(obj, name, callback);

        if (!obj) {
            return this;
        }
        const events = obj._events[name];
        const last = events.splice(events.length - 1, 1);
        events.unshift(last[0]);
    };

    /**
     * Wraps original `on` method and makes the event handler first in order
     *
     * @param {string} name
     * @param {Function} callback
     * @param {Object} [context]
     */
    Backbone.Events.firstOn = function(name, callback, context) {
        this.on(name, callback, context);

        const events = this._events[name];
        const last = events.splice(events.length - 1, 1);
        events.unshift(last[0]);
    };

    const oldLoadUrl = Backbone.History.prototype.loadUrl;
    _.extend(Backbone.History.prototype, {
        loadUrl: function(...args) {
            try {
                return oldLoadUrl.apply(this, args);
            } catch (e) {
                if (e instanceof URIError) {
                    loadModules(['oroui/js/messenger', 'orotranslation/js/translator'], function(messenger, __) {
                        messenger.showErrorMessage(__('oro.ui.malformed_url_loading_error'));
                    });
                    return false;
                }

                throw e;
            }
        }
    });

    return Backbone;
});
