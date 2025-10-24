define(function(require) {
    'use strict';

    const document = window.document;
    const $ = require('jquery');
    const _ = require('underscore');
    const tools = require('oroui/js/tools');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const LoadingMask = require('oroui/js/app/views/loading-mask-view');
    const __ = require('orotranslation/js/translator');
    const errorHandler = require('oroui/js/error');
    const messenger = require('oroui/js/messenger');
    const systemAccessModeOrganizationProvider =
        require('oroorganization/js/app/tools/system-access-mode-organization-provider').default;
    require('jquery.form');

    /**
     * @export  oroui/js/widget/abstract-widget
     * @class   oroui.widget.AbstractWidgetView
     * @extends oroui.app.views.BaseView
     */
    const AbstractWidgetView = BaseView.extend({
        options: {
            type: 'widget',
            actionsEl: '.widget-actions',
            moveAdoptedActions: true,
            url: false,
            method: 'GET',
            elementFirst: true,
            title: '',
            alias: null,
            wid: null,
            actionSectionTemplate: _.template('<div data-section="<%- section %>" class="widget-actions-section"/>'),
            actionWrapperTemplate: _.template('<span class="action-wrapper"/>'),
            loadingMaskEnabled: true,
            loadingElement: null,
            container: null,
            submitHandler: function() {
                this.trigger('adoptedFormSubmit', this.form, this);
            },
            initLayoutOptions: null
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

        /**
         * Collection of GET parameters which must remain in action url.
         */
        contextParameters: ['entityClass', 'entityId[id]', 'entityId', 'route', 'datagrid', 'group', 'fromUrl'],

        /**
         * @inheritdoc
         */
        constructor: function AbstractWidgetView(options) {
            AbstractWidgetView.__super__.constructor.call(this, options);
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
                AbstractWidgetView.__super__.remove.call(this);
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

            AbstractWidgetView.__super__.dispose.call(this);
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

            const saOrgIdSelector = $('input#_sa_org_id');
            const saOrgId = saOrgIdSelector ? saOrgIdSelector.val() : null;
            if (saOrgId !== null && saOrgId > 0) {
                systemAccessModeOrganizationProvider.setOrganizationId(saOrgId);
            }

            this.on('adoptedFormSubmitClick', this._onAdoptedFormSubmitClick.bind(this));
            this.on('adoptedFormResetClick', this._onAdoptedFormResetClick.bind(this));
            this.on('adoptedFormSubmit', this._onAdoptedFormSubmit.bind(this));
            if (this.options.loadingMaskEnabled) {
                this.on('beforeContentLoad', this._showLoading.bind(this));
                this.on('contentLoad', this._hideLoading.bind(this));
                this.on('renderStart', el => {
                    this.loadingElement = el;
                });
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
            const loadingElement = this.options.loadingElement || this.loadingElement;
            return $(loadingElement);
        },

        /**
         * Show loading indicator
         *
         * @private
         */

        _showLoading: function() {
            let options = {
                container: this._getLoadingElement()
            };

            if (this.options.loadingProperties) {
                options = Object.assign({}, options, this.options.loadingProperties);
            }

            this.subview('loadingMask', new LoadingMask(options));
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
            const widget = this;
            const sections = this.widget.find('[data-section]');
            sections.each(function(i, sectionEl) {
                const $sectionEl = $(sectionEl);
                const sectionName = $sectionEl.attr('data-section');
                const actions = $sectionEl.find('[action-name], [data-action-name]');
                if ($sectionEl.attr('action-name') || $sectionEl.attr('data-action-name')) {
                    actions.push($sectionEl);
                }
                if (!widget.actions[sectionName]) {
                    widget.actions[sectionName] = {};
                }
                actions.each(function(i, actionEl) {
                    const $actionEl = $(actionEl);
                    const actionName = $actionEl.attr('action-name') || $actionEl.attr('data-action-name');
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
            const adoptedActionsContainer = this._getAdoptedActionsContainer();
            if (adoptedActionsContainer.length > 0) {
                const self = this;
                const form = adoptedActionsContainer.closest('form');
                const actions = adoptedActionsContainer.find(':input, a, [data-action-name]');

                if (form.length > 0) {
                    this.form = form;
                }

                _.each(actions, function(action, idx) {
                    const $action = $(action);
                    let actionId = $action.data('action-name') || 'adopted_action_' + idx;
                    if (actionId !== 'delete') {
                        switch (action.type && action.type.toLowerCase()) {
                            case 'submit':
                                const submitReplacement = $('<input type="submit" tabindex="-1" aria-hidden="true"/>');
                                submitReplacement.css({
                                    position: 'absolute',
                                    [_.isRTL() ? 'right' : 'left']: '-9999px',
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
                    }
                    self.actions.adopted[actionId] = $action;
                });
                if (this.options.moveAdoptedActions) {
                    adoptedActionsContainer.remove();
                }
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
                } else if (_.isElement(this.options.actionsEl) || this.options.actionsEl instanceof $) {
                    return $(this.options.actionsEl);
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
                    data: this._getWidgetData(),
                    success: this._onContentLoad.bind(this),
                    errorHandlerMessage: false,
                    error: this._onContentLoadFail.bind(this)
                });
                this.loading = form.data('jqxhr');
            } else {
                let formAction = this.form.attr('action');
                formAction = formAction.length > 0 && formAction[0] !== '#' ? formAction : null;
                if (!this.options.url && formAction) {
                    this.options.url = formAction;
                }
                const url = formAction ? formAction : this.options.url;
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
            let content = actionElement;

            if (typeof this.options.actionWrapperTemplate === 'function') {
                content = $(this.options.actionWrapperTemplate()).append(content);
            }

            sectionContainer.append(content);
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
                let sectionContainer = this.getActionsElement().find('[data-section="' + section + '"]');
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
            const self = this;
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
                let hasAction = false;
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
                let hasActions = false;
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
                let action = null;
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
            this._clearActionsContainer();
            const container = this.getActionsElement();

            if (container) {
                _.each(this.actions, function(actions, section) {
                    const sectionContainer = this._createWidgetActionsSection(section);
                    const move = section === 'adopted' ? this.options.moveAdoptedActions : true;
                    _.each(actions, function(action, key) {
                        this._initActionEvents(action);
                        if (move) {
                            this._appendActionElement(sectionContainer, action);
                        }
                        this.trigger('widget:add:action:' + section + ':' + key, $(action));
                    }, this);
                    container.append(sectionContainer);
                }, this);
            }
        },

        /**
         * Bind submit handler for widget container defined in options
         *
         * @private
         */
        _bindSubmitHandler: function() {
            this.$el.parent().on('submit', e => {
                if (!e.isDefaultPrevented()) {
                    this.options.submitHandler.call(this);
                }
                e.preventDefault();
            });
        },

        /**
         * Initialize adopted action event handlers
         *
         * @param {HTMLElement} action
         * @private
         */
        _initActionEvents: function(action) {
            const self = this;
            const type = $(action).attr('type');
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
            const actionsEl = this.getActionsElement();
            if (actionsEl) {
                actionsEl.empty();
            }
        },

        /**
         * Render widget
         */
        render: function() {
            this._deferredRender();
            const loadAllowed = !this.options.elementFirst ||
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
            const widgetContent = $(content).filter('.widget-content').first();

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
                method = this.options.method;
            }
            const options = this.prepareContentRequestOptions(data, method, url);

            this.trigger('beforeContentLoad', this);
            this.loading = $.ajax(options)
                .done(this._onContentLoad.bind(this))
                .fail(this._onContentLoadFail.bind(this));
        },

        prepareContentRequestOptions: function(data, method, url) {
            let query = '';

            if (method.toUpperCase() === 'POST') {
                const urlParts = url.split('?');

                query = typeof urlParts[1] === 'undefined' ? '' : urlParts[1] + '&';
                url = urlParts[0] + '?' + query.split('&').filter((function(item) {
                    if (!item) {
                        return false;
                    }

                    return _.contains(this.contextParameters, decodeURIComponent(item.split('=')[0]));
                }).bind(this)).join('&');
            }

            const options = {
                url: url,
                type: method,
                data: query + (data === void 0 ? '' : data + '&'),
                errorHandlerMessage: false
            };

            options.data += $.param(this._getWidgetData());

            return options;
        },

        _getWidgetData: function() {
            const data = {
                _widgetContainer: this.options.type,
                _wid: this.getWid(),
                _widgetInit: this.firstRun ? 1 : 0
            };

            if (this.options.widgetTemplate) {
                data._widgetContainerTemplate = this.options.widgetTemplate;
            }

            const organizationId = systemAccessModeOrganizationProvider.getOrganizationId();

            if (organizationId) {
                data._sa_org_id = organizationId;
            }

            return data;
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

            if (jqxhr.status === 401) {
                this.remove();
                return;
            }

            let message = __('oro.ui.widget_loading_failed');

            if (jqxhr.status === 403) {
                message = __('oro.ui.forbidden_error');
            } else if (jqxhr.status === 404) {
                mediator.trigger('widget:notFound');
                mediator.execute('refreshPage');
                return;
            }

            const failContent = '<div class="widget-content">' +
                '<div class="alert alert-error" role="alert">' + message + '</div>' +
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
            const json = this._getJson(content);

            if (json) {
                content = '<div class="widget-content"></div>'; // set empty response to cover base functionality
            }

            delete this.loading;
            this.disposePageComponents();
            this.setContent(content, true);
            if (this.deferredRender) {
                this.deferredRender
                    .done(this._triggerContentLoadEvents.bind(this, content))
                    .fail(error => {
                        if (!this.disposing && !this.disposed) {
                            if (error) {
                                errorHandler.showErrorInConsole(error);
                            }
                            this._triggerContentLoadEvents();
                        }
                    });
            } else {
                this._triggerContentLoadEvents();
            }

            if (json) {
                this._onJsonContentResponse(json);
            }
        },

        /**
         * @param {String} content
         * @returns {json|null}
         * @private
         */
        _getJson: function(content) {
            if (_.isObject(content)) {
                return content; // return application/json content
            }

            try {
                return JSON.parse(content);
            } catch (e) {}

            return null;
        },

        /**
         * Handle returned json response
         *
         * @param {Object} content
         * @private
         */
        _onJsonContentResponse: function(content) {
            const widgetResponse = content.widget || {};

            if (_.has(widgetResponse, 'message')) {
                let message = widgetResponse.message;
                const messageOptions = widgetResponse.messageOptions || {};

                if (_.isString(message)) {
                    message = {type: 'success', text: message};
                }

                if (_.has(widgetResponse, 'messageAfterPageChange') && widgetResponse.messageAfterPageChange === true) {
                    mediator.once('page:afterChange', function() {
                        messenger.notificationFlashMessage(message.type, message.text, messageOptions);
                    });
                } else {
                    messenger.notificationFlashMessage(message.type, message.text, messageOptions);
                }
            }

            if (_.has(widgetResponse, 'trigger')) {
                let events = widgetResponse.trigger;

                if (!_.isObject(events)) {
                    events = [events];
                }

                _.each(events, function(event) {
                    const eventBroker = this._getEventBroker(event);
                    const eventFunction = this._getEventFunction(event);

                    if (_.isObject(event)) {
                        const args = [event.name].concat(event.args);
                        eventBroker[eventFunction](...args);
                    } else {
                        eventBroker[eventFunction](event);
                    }
                }, this);
            }

            if (_.has(widgetResponse, 'triggerSuccess') && widgetResponse.triggerSuccess) {
                mediator.trigger('widget_success:' + this.getAlias());
                mediator.trigger('widget_success:' + this.getWid());
            }

            if (_.has(widgetResponse, 'remove') && widgetResponse.remove) {
                this.remove();
            }
        },

        _getEventBroker: function(event) {
            return event.eventBroker === 'widget' ? this : mediator;
        },

        _getEventFunction: function(event) {
            return event.eventFunction === 'execute' ? 'execute' : 'trigger';
        },

        _triggerContentLoadEvents: function(content) {
            this.trigger('contentLoad', content, this);
            mediator.trigger('widget:contentLoad', this.widget);
            mediator.trigger('layout:adjustHeight', this.el);
        },

        /**
         * @inheritdoc
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
            this.getLayoutElement().attr('data-layout', 'separate');
            this.initLayout(this.options.initLayoutOptions || {})
                .done(this._afterLayoutInit.bind(this));
        },

        _afterLayoutInit: function() {
            if (this.disposed) {
                return;
            }
            if (this.deferredRender) {
                this.deferredRender.done(this._renderHandler.bind(this));
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

    return AbstractWidgetView;
});
