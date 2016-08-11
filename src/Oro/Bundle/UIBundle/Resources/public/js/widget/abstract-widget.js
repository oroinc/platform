define(function(require) {
    'use strict';

    var AbstractWidget;
    var document = window.document;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');
    var __ = require('orotranslation/js/translator');
    require('jquery.form');

    /**
     * @export  oroui/js/widget/abstract-widget
     * @class   oroui.widget.AbstractWidget
     * @extends oroui.app.views.BaseView
     */
    AbstractWidget = BaseView.extend({
        options: {
            type: 'widget',
            actionsEl: '.widget-actions',
            url: false,
            elementFirst: true,
            title: '',
            alias: null,
            wid: null,
            actionSectionTemplate: _.template('<div data-section="<%= section %>" class="widget-actions-section"/>'),
            actionWrapperTemplate: _.template('<span class="action-wrapper"/>'),
            loadingMaskEnabled: true,
            loadingElement: null,
            container: null,
            submitHandler: function() {
                this.trigger('adoptedFormSubmit', this.form, this);
            }
        },

        loadingElement: null,
        loadingMask: null,
        loading: false,
        /**
         * Flag if the widget is embedded to the page
         * (defines life cycle of the widget)
         *
         * @type {boolean}
         */
        _isEmbedded: true,

        listen: {
            renderComplete: '_initSectionActions'
        },

        initialize: function(options) {
            options = options || {};
            this.options = _.defaults(options, this.options);
            this.initializeWidget(options);
        },

        /**
         * Set widget title.
         *
         * @param {string} title
         */
        setTitle: function(title) {
            throw new Error('Implement setTitle');
        },

        /**
         * Get actions container element
         */
        getActionsElement: function() {
            throw new Error('Implement getActionsElement');
        },

        /**
         * Remove widget
         */
        remove: function() {
            if (!this.disposing) {
                // If remove method was called directly -- execute dispose first
                this.dispose();
            } else {
                AbstractWidget.__super__.remove.call(this);
            }
        },

        /**
         *
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            // add flag: this is disposing process
            // (to prevent recursion from remove method)
            this.disposing = true;

            // if there's loading process -- stop it
            if (this.loading) {
                this.loading.abort();
                delete this.loading;
            }

            // call before dom will be removed
            this.disposePageComponents();

            // trigger all events before handlers got undelegated
            this.trigger('widgetRemove', this.$el);
            mediator.trigger('widget_remove', this.getWid());
            if (this.getAlias()) {
                mediator.trigger('widget_remove:' + this.getAlias());
            }
            this.trigger('widgetRemoved');

            AbstractWidget.__super__.dispose.call(this);
        },

        /**
         * Check if widget is actual. To be actual, widget should:
         *  - not to be disposed
         *  - have the element is in the DOM or have loading flag
         *
         * @returns {boolean}
         */
        isActual: function() {
            return !this.disposed &&
                (this.loading || $.contains(document.documentElement, this.el));
        },

        /**
         * Returns flag if the widget is embedded to the parent content
         *
         * @returns {boolean}
         */
        isEmbedded: function() {
            return this._isEmbedded;
        },

        /**
         * Initialize
         *
         * @para {Object} options Widget options
         */
        initializeWidget: function(options) {
            if (this.options.wid) {
                this._wid = this.options.wid;
            }

            this.on('adoptedFormSubmitClick', _.bind(this._onAdoptedFormSubmitClick, this));
            this.on('adoptedFormResetClick', _.bind(this._onAdoptedFormResetClick, this));
            this.on('adoptedFormSubmit', _.bind(this._onAdoptedFormSubmit, this));
            if (this.options.loadingMaskEnabled) {
                this.on('beforeContentLoad', _.bind(this._showLoading, this));
                this.on('contentLoad', _.bind(this._hideLoading, this));
                this.on('renderStart', _.bind(function(el) {
                    this.loadingElement = el;
                }, this));
            }

            this.actions = {};
            this.firstRun = true;
            this.containerFilled = false;

            this.loadingElement = $('body');

            mediator.trigger('widget_initialize', this);
        },

        /**
         * Get loading element.
         *
         * @returns {HTMLElement}
         * @private
         */
        _getLoadingElement: function() {
            var loadingElement = this.options.loadingElement || this.loadingElement;
            return $(loadingElement);
        },

        /**
         * Show loading indicator
         *
         * @private
         */

        _showLoading: function() {
            this.subview('loadingMask', new LoadingMask({
                container: this._getLoadingElement()
            }));
            this.subview('loadingMask').show();
        },

        /**
         * Hide loading indicator
         *
         * @private
         */
        _hideLoading: function() {
            this.removeSubview('loadingMask');
        },

        /**
         * Get unique widget identifier
         *
         * @returns {string}
         */
        getWid: function() {
            if (!this._wid) {
                this._wid = this._getUniqueIdentifier();
            }
            return this._wid;
        },

        /**
         * Get widget alias
         *
         * @returns {string|null}
         */
        getAlias: function() {
            return this.$el.data('alias') || this.options.alias;
        },

        /**
         * Generate unique widget identifier
         *
         * @returns {string}
         * @private
         */
        _getUniqueIdentifier: function() {
            return tools.createRandomUUID();
        },

        /**
         * Register other action elements
         *
         * @private
         */
        _initSectionActions: function() {
            var widget = this;
            var sections = this.widget.find('[data-section]');
            sections.each(function(i, sectionEl) {
                var $sectionEl = $(sectionEl);
                var sectionName = $sectionEl.attr('data-section');
                var actions = $sectionEl.find('[action-name], [data-action-name]');
                if ($sectionEl.attr('action-name') || $sectionEl.attr('data-action-name')) {
                    actions.push($sectionEl);
                }
                if (!widget.actions[sectionName]) {
                    widget.actions[sectionName] = {};
                }
                actions.each(function(i, actionEl) {
                    var $actionEl = $(actionEl);
                    var actionName = $actionEl.attr('action-name') || $actionEl.attr('data-action-name');
                    widget.actions[sectionName][actionName] = $actionEl;
                    widget.trigger('widget:add:action:' + sectionName + ':' + actionName, $actionEl);
                });
            });
        },

        /**
         * Convert form actions to widget actions
         *
         *  @private
         */
        _adoptWidgetActions: function() {
            this.actions.adopted = {};
            this.form = null;
            var adoptedActionsContainer = this._getAdoptedActionsContainer();
            if (adoptedActionsContainer.length > 0) {
                var self = this;
                var form = adoptedActionsContainer.closest('form');
                var actions = adoptedActionsContainer.find('button, input, a, [data-action-name]');

                if (form.length > 0) {
                    this.form = form;
                }

                _.each(actions, function(action, idx) {
                    var $action = $(action);
                    var actionId = $action.data('action-name') || 'adopted_action_' + idx;
                    switch (action.type && action.type.toLowerCase()) {
                        case 'submit':
                            var submitReplacement = $('<input type="submit"/>');
                            submitReplacement.css({
                                position: 'absolute',
                                left: '-9999px',
                                top: '-9999px',
                                width: '1px',
                                height: '1px'
                            });
                            form.prepend(submitReplacement);
                            actionId = 'form_submit';
                            break;
                        case 'reset':
                            actionId = 'form_reset';
                            break;
                    }
                    self.actions.adopted[actionId] = $action;
                });
                adoptedActionsContainer.remove();
            }
        },

        /**
         * Get container with adopted form actions
         *
         * @returns {HTMLElement}
         * @private
         */
        _getAdoptedActionsContainer: function() {
            if (this.options.actionsEl !== undefined) {
                if (typeof this.options.actionsEl === 'string') {
                    return this.$el.find(this.options.actionsEl);
                } else if (_.isElement(this.options.actionsEl)) {
                    return this.options.actionsEl;
                }
            }
            return false;
        },

        /**
         * Handle adopted form submit button click
         *
         * @param {HTMLElement} form
         * @private
         */
        _onAdoptedFormSubmitClick: function(form) {
            form.submit();
        },

        /**
         * Handle adopted form submit
         *
         * @param {HTMLElement} form
         * @private
         */
        _onAdoptedFormSubmit: function(form) {
            if (this.loading) {
                return;
            }
            if (form.find('[type="file"]').length) {
                this.trigger('beforeContentLoad', this);
                form.ajaxSubmit({
                    data: {
                        '_widgetContainer': this.options.type,
                        '_wid': this.getWid()
                    },
                    success: _.bind(this._onContentLoad, this),
                    error: _.bind(this._onContentLoadFail, this)
                });
                this.loading = form.data('jqxhr');
            } else {
                var formAction = this.form.attr('action');
                formAction = formAction.length > 0 && formAction[0] !== '#' ? formAction : null;
                if (!this.options.url && formAction) {
                    this.options.url = formAction;
                }
                var url = formAction ? formAction : this.options.url;
                this.loadContent(form.serialize(), form.attr('method'), url);
            }
        },

        /**
         * Handle adopted form reset button click
         *
         * @param {HTMLElement} form
         * @private
         */
        _onAdoptedFormResetClick: function(form) {
            $(form).trigger('reset');
        },

        /**
         * Create container for actions section
         *
         * @param {string} section
         * @returns {HTMLElement}
         * @private
         */
        _createWidgetActionsSection: function(section) {
            return $(
                this.options.actionSectionTemplate({
                    section: section
                })
            );
        },

        /**
         * Append action element to sections
         *
         * @param {HTMLElement} sectionContainer
         * @param {HTMLElement} actionElement
         * @private
         */
        _appendActionElement: function(sectionContainer, actionElement) {
            sectionContainer.append($(this.options.actionWrapperTemplate()).append(actionElement));
        },

        /**
         * Add action element to specified section
         *
         * @param {string} key action name
         * @param {string} section section name
         * @param {HTMLElement} actionElement
         */
        addAction: function(key, section, actionElement) {
            if (section === undefined) {
                section = 'main';
            }
            if (!this.hasAction(key, section)) {
                if (!this.actions.hasOwnProperty(section)) {
                    this.actions[section] = {};
                }
                this.actions[section][key] = actionElement;
                var sectionContainer = this.getActionsElement().find('[data-section="' + section + '"]');
                if (!sectionContainer.length) {
                    sectionContainer = this._createWidgetActionsSection(section);
                    sectionContainer.appendTo(this.getActionsElement());
                }
                this._appendActionElement(sectionContainer, actionElement);
                this.trigger('widget:add:action:' + section + ':' + key, $(actionElement));
            }
        },

        /**
         * Get all registered actions
         *
         * @returns {Object}
         */
        getActions: function() {
            return this.actions;
        },

        /**
         * Set url
         *
         * @param {string} url
         */
        setUrl: function(url) {
            this.options.url = url;
        },

        /**
         * Remove action from section
         *
         * @param {string} key action name
         * @param {string} section section name
         */
        removeAction: function(key, section) {
            var self = this;
            function remove(actions, key) {
                if (_.isElement(self.actions[key])) {
                    self.actions[key].remove();
                }
                delete self.actions[key];
            }
            if (this.hasAction(key, section)) {
                if (section !== undefined) {
                    remove(this.actions[section], key);
                } else {
                    _.each(this.actions, function(actions, section) {
                        if (self.hasAction(key, section)) {
                            remove(actions, key);
                        }
                    });
                }
            }
        },

        /**
         * Check action availability.
         *
         * @param {string} key action name
         * @param {string} section section name
         * @returns {boolean}
         */
        hasAction: function(key, section) {
            if (section !== undefined) {
                return this.actions.hasOwnProperty(section) && this.actions[section].hasOwnProperty(key);
            } else {
                var hasAction = false;
                _.each(this.actions, function(actions) {
                    if (actions.hasOwnProperty(key)) {
                        hasAction = true;
                    }
                });
                return hasAction;
            }
        },

        /**
         * Check if there is at least one action.
         *
         * @param {string} section section name
         * @returns {boolean}
         */
        hasActions: function(section) {
            if (section !== undefined) {
                return this.actions.hasOwnProperty(section) && !_.isEmpty(this.actions[section]);
            } else {
                var hasActions = false;
                _.each(this.actions, function(actions) {
                    if (!_.isEmpty(actions)) {
                        hasActions = true;
                    }
                });
                return hasActions;
            }
        },

        /**
         * Get action element when after render.
         *
         * @param {string} key action name
         * @param {string} section section name
         * @param {function} callback callback method for processing action element
         */
        getAction: function(key, section, callback) {
            if (this.hasAction(key, section)) {
                var action = null;
                if (section !== undefined) {
                    action = this.actions[section][key];
                } else {
                    _.each(this.actions, function(actions) {
                        if (actions.hasOwnProperty(key)) {
                            action = actions[key];
                        }
                    });
                }
                callback(action);
            } else {
                this.once('widget:add:action:' + section + ':' + key, callback);
            }
        },

        /**
         * Render widget actions
         *
         * @private
         */
        _renderActions: function() {
            var self = this;
            this._clearActionsContainer();
            var container = this.getActionsElement();

            if (container) {
                _.each(this.actions, function(actions, section) {
                    var sectionContainer = self._createWidgetActionsSection(section);
                    _.each(actions, function(action, key) {
                        self._initActionEvents(action);
                        self._appendActionElement(sectionContainer, action);
                        self.trigger('widget:add:action:' + section + ':' + key, $(action));
                    });
                    container.append(sectionContainer);
                });
            }
        },

        /**
         * Bind submit handler for widget container defined in options
         *
         * @private
         */
        _bindSubmitHandler: function() {
            this.$el.parent().on('submit', _.bind(function(e) {
                if (!e.isDefaultPrevented()) {
                    this.options.submitHandler.call(this);
                }
                e.preventDefault();
            }, this));
        },

        /**
         * Initialize adopted action event handlers
         *
         * @param {HTMLElement} action
         * @private
         */
        _initActionEvents: function(action) {
            var self = this;
            var type = $(action).attr('type');
            if (!type) {
                return;
            }
            switch (type.toLowerCase()) {
                case 'submit':
                    action.on('click', function() {
                        self.trigger('adoptedFormSubmitClick', self.form, self);
                        return false;
                    });
                    break;

                case 'reset':
                    action.on('click', function() {
                        self.trigger('adoptedFormResetClick', self.form, self);
                    });
                    break;
            }
        },

        /**
         * Clear actions container.
         *
         * @private
         */
        _clearActionsContainer: function() {
            var actionsEl = this.getActionsElement();
            if (actionsEl) {
                actionsEl.empty();
            }
        },

        /**
         * Render widget
         */
        render: function() {
            this._deferredRender();
            var loadAllowed = !this.options.elementFirst ||
                    (this.options.elementFirst && !this.firstRun) ||
                        (this.$el && this.$el.length && this.$el.html().length === 0);
            if (loadAllowed && this.options.url !== false) {
                this.loadContent();
            } else {
                this._show();
            }
            this.firstRun = false;
        },

        /**
         * Updates content of a widget.
         *
         * @param {String} content
         */
        setContent: function(content) {
            var widgetContent = $(content).filter('.widget-content:first');

            this.actionsEl = null;
            this.actions = {};

            // creating of jqUI dialog could throw exception
            if (widgetContent.length === 0) {
                throw new Error('Invalid server response: ' + content);
            }
            this.disposePageComponents();
            this.setElement(widgetContent);
            this._show();
        },

        /**
         * Load content
         *
         * @param {Object=} data
         * @param {string=} method
         * @param {string=} url
         */
        loadContent: function(data, method, url) {
            url = url || this.options.url;
            if (url === undefined || !url) {
                url = window.location.href;
            }
            if (this.firstRun || method === undefined || !method) {
                method = 'get';
            }
            var options = {
                url: url,
                type: method
            };
            if (data !== undefined) {
                options.data = data;
            }
            options.data = (options.data !== undefined ? options.data + '&' : '') +
                '_widgetContainer=' + this.options.type + '&_wid=' + this.getWid();

            this.trigger('beforeContentLoad', this);
            this.loading = $.ajax(options)
                .done(_.bind(this._onContentLoad, this))
                .fail(_.bind(this._onContentLoadFail, this));
        },

        /**
         * Handle content loading failure.
         * @private
         */
        _onContentLoadFail: function(jqxhr) {
            if (jqxhr.statusText === 'abort') {
                // content load was aborted
                delete this.loading;
                return;
            }

            var message = __('oro.ui.widget_loading_failed');

            if (jqxhr.status === 403) {
                message = __('oro.ui.forbidden_error');
            }

            var failContent = '<div class="widget-content">' +
                '<div class="alert alert-error">' + message + '</div>' +
                '</div>';

            this._onContentLoad(failContent);
        },

        /**
         * Handle loaded content.
         *
         * @param {String} content
         * @private
         */
        _onContentLoad: function(content) {
            delete this.loading;
            this.disposePageComponents();
            this.setContent(content, true);
            if (this.deferredRender) {
                this.deferredRender
                    .done(_.bind(this._triggerContentLoadEvents, this, content))
                    .fail(function() {
                        throw new Error('Widget rendering failed');
                    });
            } else {
                this._triggerContentLoadEvents();
            }
        },

        _triggerContentLoadEvents: function(content) {
            this.trigger('contentLoad', content, this);
            mediator.trigger('widget:contentLoad', this.widget);
            mediator.trigger('layout:adjustHeight');
        },

        /**
         * @inheritDoc
         */
        getLayoutElement: function() {
            return this.widget;
        },

        /**
         * Show widget content
         *
         * @private
         */
        _show: function() {
            this._adoptWidgetActions();
            this.trigger('renderStart', this.$el, this);
            this.show();
            this._renderInContainer();
            this.trigger('renderComplete', this.$el, this);
            this.initLayout()
                .done(_.bind(this._afterLayoutInit, this));
        },

        _afterLayoutInit: function() {
            if (this.disposed) {
                return;
            }
            if (this.deferredRender) {
                this.deferredRender.done(_.bind(this._renderHandler, this));
                this._resolveDeferredRender();
            } else {
                this._renderHandler();
            }
        },

        _renderHandler: function() {
            this.widget.removeClass('invisible');
            this.trigger('widgetReady', this);
        },

        /**
         * General implementation of show logic.
         */
        show: function() {
            this.setWidToElement(this.$el);
            this._renderActions();
            this._bindSubmitHandler();
            this.trigger('widgetRender', this.$el, this);
            mediator.trigger('widget:render:' + this.getWid(), this.$el, this);
        },

        _renderInContainer: function() {
            if (!this.containerFilled && this.options.container) {
                this.widget.addClass('invisible');
                $(this.options.container).append(this.widget);
                this.containerFilled = true;
            }
        },

        /**
         * Add data-wid attribute to given element.
         *
         * @param {HTMLElement} el
         */
        setWidToElement: function(el) {
            el.attr('data-wid', this.getWid());
        }
    });

    return AbstractWidget;
});
