define([
    'jquery',
    'underscore',
    'oroui/js/tools',
    'chaplin',
    'oroui/js/extend/backbone' // it is a circular dependency, required just to make sure that backbone is extended
], function($, _, tools, Chaplin) {
    'use strict';

    var original = {};
    var utils = Chaplin.utils;
    original.viewDispose = Chaplin.View.prototype.dispose;
    original.collectionViewRender = Chaplin.CollectionView.prototype.render;

    Chaplin.View.prototype.dispose = function() {
        if (this.disposed) {
            return;
        }
        if (this.deferredRender) {
            this._rejectDeferredRender();
        }
        this.disposeControls();
        this.disposePageComponents();
        this.trigger('dispose', this);
        original.viewDispose.call(this);
    };

    // keep this flag falsy, but different from the `false`, to distinguish it was set intentionally
    Chaplin.View.prototype.keepElement = null;

    /**
     * Detach all item view elements before render of collection to preserve DOM event handlers bound
     */
    Chaplin.CollectionView.prototype.render = function() {
        _.each(this.getItemViews(), function(itemView) {
            itemView.$el.detach();
        });
        return original.collectionViewRender.call(this);
    };

    /**
     * Fixes issue where path '/' was converted to boolean false value
     * @override
     */
    Chaplin.Router.prototype.route = function(pathDesc, params, options) {
        var handler;
        var path;
        if (typeof pathDesc === 'object') {
            path = pathDesc.url;
            if (!params && pathDesc.params) {
                params = pathDesc.params;
            }
        }
        params = params ? _.isArray(params) ? params.slice() : _.extend({}, params) : {};
        if (path !== null && path !== void 0) {
            path = path.replace(this.removeRoot, '');
            handler = this.findHandler(function(handler) {
                return handler.route.test(path);
            });
            options = params;
            params = null;
        } else {
            options = options ? _.extend({}, options) : {};
            handler = this.findHandler(function(handler) {
                if (handler.route.matches(pathDesc)) {
                    params = handler.route.normalizeParams(params);
                    if (params) {
                        return true;
                    }
                }
                return false;
            });
        }
        if (handler) {
            _.defaults(options, {
                changeURL: true
            });
            handler.callback(path !== null && path !== void 0 ? path : params, options);
            return true;
        } else {
            throw new Error('Router#route: request was not routed');
        }
    };

    /**
     * Added force flag that allows to retrieve even stale composition
     *
     * @param {string} name
     * @param {boolean=} force
     * @returns {*}
     * @override
     */
    Chaplin.Composer.prototype.retrieve = function(name, force) {
        var active;
        active = this.compositions[name];
        if (active && (force || !active.stale())) {
            return active.item;
        } else {
            return void 0;
        }
    };

    var insertView = function(list, viewEl, position, length, itemSelector) {
        var children, childrenLength, insertInMiddle, isEnd, method; // eslint-disable-line one-var
        insertInMiddle = (0 < position && position < length);
        isEnd = function(length) {
            return length === 0 || position >= length;
        };
        if (insertInMiddle || itemSelector) {
            children = list.children(itemSelector);
            childrenLength = children.length;
            if (children[position] !== viewEl) {
                if (isEnd(childrenLength)) {
                    return list.append(viewEl);
                } else {
                    if (position === 0) {
                        return children.eq(position).before(viewEl);
                    } else {
                        return children.eq(position - 1).after(viewEl);
                    }
                }
            }
        } else {
            method = isEnd(length) ? 'append' : 'prepend';
            return list[method](viewEl);
        }
    };

    /**
     * insertView is reverted to version 1.0.0, due to this method in 1.2.0 version has issues:
     *  - helper function `insertView` gets invoked only for views that passed filter function and marked as `included`
     *  - `insertView` is called only from `itemAdded` and `renderAllItems`,
     *    so if a view passes filter function later it still wont be added to HTML
     *  - on attempt to add a passed filter view manually to HTML with the help of `insertView -- another issue pops up:
     *  wrong position (order) of elements in HTML. Assume `renderAllItems` renders only one model with index 3,
     *  the view element in HTML it will get index 0. Late another model with index 2 passes filter as well,
     *  it will be inserted after view element of model with index 3.
     * @override
     */
    Chaplin.CollectionView.prototype.insertView = function(item, view, position, enableAnimation) {
        var elem, included, length, list, // eslint-disable-line one-var
            _this = this;
        if (enableAnimation == null) {
            enableAnimation = true;
        }
        if (this.animationDuration === 0) {
            enableAnimation = false;
        }
        if (typeof position !== 'number') {
            position = this.collection.indexOf(item);
        }
        included = typeof this.filterer === 'function' ? this.filterer(item, position) : true;
        elem = $ ? view.$el : view.el;
        if (included && enableAnimation) {
            if (this.useCssAnimation) {
                elem.addClass(this.animationStartClass);
            } else {
                elem.css('opacity', 0);
            }
        }
        if (this.filterer) {
            this.filterCallback(view, included);
        }
        length = this.collection.length;
        list = $ ? this.$list : this.list;
        insertView(list, elem, position, length, this.itemSelector);
        view.trigger('addedToParent');
        this.updateVisibleItems(item, included);
        if (included && enableAnimation) {
            if (this.useCssAnimation) {
                setTimeout(function() {
                    return elem.addClass(_this.animationEndClass);
                }, 0);
            } else {
                elem.animate({opacity: 1}, this.animationDuration);
            }
        }
        return view;
    };

    /**
     * Since IE removes content form child elements when parent node is emptied
     * we need re-render item subviews manually
     * (see https://jsfiddle.net/3hrfhppe/)
     */
    if (/(MSIE\s|Trident\/|Edge\/)/.test(window.navigator.userAgent)) {
        Chaplin.CollectionView.prototype.insertView = _.wrap(
            Chaplin.CollectionView.prototype.insertView, function(func, item, view) {
                if (view.el.childNodes.length === 0) {
                    view.render();
                }
                return func.apply(this, _.rest(arguments));
            }
        );
    }

    /**
     * In case it's an error page blocks application's navigation and turns on full redirect
     * @override
     */
    utils.redirectTo = _.wrap(utils.redirectTo, function(func, pathDesc, params, options) {
        if (typeof pathDesc === 'object' && pathDesc.url !== null && pathDesc.url !== void 0 && tools.isErrorPage()) {
            options = params || {};
            options.fullRedirect = true;
            Chaplin.mediator.execute('redirectTo', pathDesc, options);
        } else {
            func.apply(this, _.rest(arguments));
        }
    });

    /**
     * Extends original Chaplin.SyncMachine with extra methods
     *
     * @mixin
     */
    Chaplin.SyncMachine = _.extend(/** @lends Chaplin.SyncMachine */{
        UNSYNCED: 'unsynced',
        SYNCING: 'syncing',
        SYNCED: 'synced',
        STATE_CHANGE: 'syncStateChange',
        markAsSynced: function() {
            if (this._syncState !== Chaplin.SyncMachine.SYNCED) {
                this._previousSync = this._syncState;
                this._syncState = Chaplin.SyncMachine.SYNCED;
                this.trigger(this._syncState, this, this._syncState);
                this.trigger(Chaplin.SyncMachine.STATE_CHANGE, this, this._syncState);
            }
        },
        /**
         * Returns promise that was resolved with fetched instance
         * when sync will be finished and starts fetch it's needed
         * @return {Promise.<Object>}
         */
        ensureSync: function() {
            var deferred = $.Deferred();
            switch (this.syncState()) {
                case 'unsynced':
                    this.fetch().then(function() {
                        deferred.resolve(this);
                    }.bind(this));
                    break;
                case 'syncing':
                    this.once('synced', function() {
                        deferred.resolve(this);
                    }.bind(this));
                    break;
                case 'synced':
                    deferred.resolve(this);
                    break;
            }
            return deferred.promise();
        }
    }, Chaplin.SyncMachine);

    return Chaplin;
});
