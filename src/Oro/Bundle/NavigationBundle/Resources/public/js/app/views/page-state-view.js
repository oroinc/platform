define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const base64 = require('base64');
    const routing = require('routing');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const Modal = require('oroui/js/modal');
    const PageStateModel = require('oronavigation/js/app/models/page-state-model');
    const pageStateChecker = require('oronavigation/js/app/services/page-state-checker');
    const BaseView = require('oroui/js/app/views/base/view');

    const PageStateView = BaseView.extend({
        IGNORE: ':input[type=button], :input[type=submit], :input[type=reset], ' +
            ':input[type=password], :input[type=file], :input[name$="[_token]"], ' +
            '[data-ignore-form-state-change] :input, :input[name^=temp-validation-name]',

        listen: {
            'change:data model': '_saveModel',
            'change model': '_updateCache',

            'page:request mediator': 'onPageRequest',
            'page:update mediator': 'onPageUpdate',
            'page:afterChange mediator': 'afterPageChange',
            'page:afterPagePartChange mediator': 'afterPageChange',
            'page:beforeRefresh mediator': 'beforePageRefresh',
            'openLink:before mediator': 'beforePageChange'
        },

        events: {
            'patchInitialState form[data-collect=true]': 'onPatchInitialState'
        },

        /**
         * @inheritdoc
         */
        constructor: function PageStateView(options) {
            PageStateView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.model = new PageStateModel();

            this._initialState = null;
            this._resetChanges = false;

            const confirmModal = new Modal({
                title: __('Refresh Confirmation'),
                content: __('Your local changes will be lost. Are you sure you want to refresh the page?'),
                okText: __('Ok, got it'),
                className: 'modal modal-primary',
                cancelText: __('Cancel'),
                disposeOnHidden: false
            });
            this.subview('confirmModal', confirmModal);

            $(window).on('beforeunload' + this.eventNamespace(), this.onWindowUnload.bind(this));

            this.isChangesLossConfirmationNeeded = this.isChangesLossConfirmationNeeded.bind(this);
            pageStateChecker.registerChecker(this.isChangesLossConfirmationNeeded);

            PageStateView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            pageStateChecker.removeChecker(this.isChangesLossConfirmationNeeded);
            $(window).off(this.eventNamespace());
            PageStateView.__super__.dispose.call(this);
        },

        /**
         * Handle page's refresh action
         * if confirmation is required:
         *  - prepares deferred object
         *  - puts deferred object into refresh
         *
         * @param {Array} queue
         */
        beforePageRefresh: function(queue) {
            let deferred;
            let confirmModal;
            let self;
            if (!this.model.get('data')) {
                // data is not set, nothing to compare with
                return;
            }
            const preservedState = JSON.parse(this.model.get('data'));
            if (this._isStateChanged(preservedState)) {
                self = this;
                confirmModal = this.subview('confirmModal');
                deferred = $.Deferred();

                deferred.always(function() {
                    self.stopListening(confirmModal);
                });
                this.listenTo(confirmModal, 'ok', function() {
                    deferred.resolve({resetChanges: true});
                });
                this.listenTo(confirmModal, 'cancel close', function() {
                    deferred.reject();
                });

                queue.push(deferred);
                confirmModal.open();
            }
        },

        /**
         * Returns if form state was changed
         *
         * @returns {Boolean}
         */
        isStateChanged: function() {
            return this._isStateChanged();
        },

        /**
         * Checks if there are changes and the page is not pinned (changes are temporary preserved)
         * @return {boolean}
         */
        isChangesLossConfirmationNeeded: function() {
            return this._isStateChanged() && !this._isStateTraceRequired();
        },

        /**
         * Handles navigation action and shows confirm dialog
         * if page changes is not preserved and the state is changed from initial
         * (excludes cancel action)
         */
        beforePageChange: function(e) {
            const action = $(e.target).data('action');
            const blank = $(e.target).attr('target') === '_blank';
            if (!e.prevented && action !== 'cancel' && !blank && this.isChangesLossConfirmationNeeded()) {
                e.prevented = !window.confirm(__('oro.ui.leave_page_with_unsaved_data_confirm'));
            }
        },

        /**
         * Clear page state timer and model on page request is started
         */
        onPageRequest: function() {
            this._initialState = null;
            this._resetChanges = false;
            this._switchOffTrace();
        },

        /**
         * Init page state on page updated
         * @param {Object} attributes
         * @param {Object} args
         */
        onPageUpdate: function(attributes, args) {
            const options = (args || {}).options;
            this._resetChanges = Boolean(options && options.resetChanges);
        },

        /**
         * Handles window unload event and shows confirm dialog
         * if page changes is not preserved and the state is changed from initial
         */
        onWindowUnload: function() {
            if (this.isChangesLossConfirmationNeeded() && !pageStateChecker.hasChangesIgnored()) {
                return __('oro.ui.leave_page_with_unsaved_data_confirm');
            }
        },

        /**
         * Fetches model's attributes from cache on page changes is done
         */
        afterPageChange: function() {
            let options;

            if (this._hasForm()) {
                this._initialState = this._collectFormsData();
            }
            if (!this._hasForm() || !this._isStateTraceRequired()) {
                return;
            }

            if (this._resetChanges) {
                // delete cache if changes are discarded
                mediator.execute('pageCache:state:save', 'form', null);
                options = {initial: true};
            }

            this._switchOnTrace(options);
        },

        /**
         * Switch on/off form state trace
         */
        toggleStateTrace: function() {
            const switchOn = this._isStateTraceRequired();
            if (switchOn) {
                this._switchOnTrace({initial: true});
            } else {
                this._switchOffTrace();
            }
        },

        /**
         * Handles `patchInitialState` DOM event and execute correspondent handler for an input/a container and its form
         * @param {jQuery.Event} event
         */
        onPatchInitialState(event) {
            this.patchInitialState($(event.target), $(event.currentTarget));
        },

        /**
         * Collects patch data and updates initial state
         * @param {jQuery.Element} $elem an input or a container
         * @param {jQuery.Element} $form
         */
        patchInitialState($elem, $form) {
            const formIndex = this.$('form[data-collect=true]').index($form);
            if (formIndex === -1 || !this._initialState || !this._initialState[formIndex]) {
                // form with traced state is not found
                return;
            }
            const initialState = this._initialState[formIndex];
            const patchData = this._extractInputsData($elem);
            const findIndexInState = (state, patch) => {
                return state.findIndex((itemA, indexA) => {
                    return patch.every((itemB, indexB) => state[indexA + indexB].name === itemB.name);
                });
            };

            const replaceIndex = findIndexInState(initialState, patchData);
            if (replaceIndex !== -1) {
                // found exact sequence of patchData in initialState -- replace that data
                initialState.splice(replaceIndex, patchData.length, ...patchData);
            } else {
                // insert patchData into initial state
                const newState = this._extractInputsData($form);
                const newIndex = findIndexInState(newState, patchData);
                const preNewItem = newState[newIndex - 1];
                const nextNewItem = newState[newIndex + patchData.length];

                if (preNewItem) {
                    // there's previous item, try to find its index in initial state
                    const index = initialState.findLastIndex(item => item.name === preNewItem.name);
                    // index is found and no next item (end of list) or next item is the same as in initial state
                    if (index !== -1 && (!nextNewItem || initialState[index + 1].name === nextNewItem.name)) {
                        initialState.splice(index + 1, 0, ...patchData);
                    }
                } else if (nextNewItem) {
                    // no previous item, but next item is found, insert before it
                    const index = initialState.findIndex(item => item.name === nextNewItem.name);
                    if (index !== -1) {
                        initialState.splice(index - 1, 0, ...patchData);
                    }
                }
            }
        },

        /**
         * Switch on form state trace
         * @param {Object=} options
         * @protected
         */
        _switchOnTrace: function(options) {
            const attributes = mediator.execute('pageCache:state:fetch', 'form');
            if (attributes && attributes.id) {
                this._initStateTracer(attributes, options);
            } else {
                this._loadState(options);
            }
        },

        /**
         * Switch off form state trace
         * @protected
         */
        _switchOffTrace: function() {
            this.$el.off('change.page-state');
            this.model.clear({silent: true});
        },

        /**
         * Initializes form changes trace
         *  - if attributes is not in a cache, loads data from server
         * @param {Object=} options
         * @protected
         */
        _loadState: function(options) {
            const self = this;
            let checkIdRoute = 'oro_api_get_pagestate_checkid';
            const pageStateRoutes = this.$el.find('#pagestate-routes');
            if (pageStateRoutes.data()) {
                this.model.postRoute = pageStateRoutes.data('pagestate-put-route');
                this.model.putRoute = pageStateRoutes.data('pagestate-put-route');
                checkIdRoute = pageStateRoutes.data('pagestate-checkid-route');
            }

            const url = routing.generate(checkIdRoute, {pageId: this._combinePageId()});
            $.get(url).done(function(data) {
                const attributes = {
                    pageId: data.pagestate.pageId || self._combinePageId(),
                    data: self._resetChanges ? '' : data.pagestate.data,
                    pagestate: data.pagestate
                };
                if (data.id) {
                    attributes.id = data.id;
                }
                self._initStateTracer(attributes, options);
            });
        },

        /**
         * Resets page state model, restores page forms and start tracing changes
         * @param {Object} attributes
         * @param {Object=} options
         * @protected
         */
        _initStateTracer: function(attributes, options) {
            options = options || {};
            const currentData = JSON.stringify(this._collectFormsData());
            if (!attributes.data || options.initial) {
                attributes.data = currentData;
            }
            this.model.set(attributes);
            if (attributes.data !== currentData) {
                this._restoreState();
            }
            this.$el.on('change.page-state', this._collectState.bind(this));
        },

        /**
         * Updates state in cache on model sync
         * @protected
         */
        _updateCache: function() {
            const attributes = {};
            _.extend(attributes, this.model.getAttributes());
            mediator.execute('pageCache:state:save', 'form', attributes);
        },

        /**
         * Defines if page has forms and state tracing is required
         * @returns {boolean}
         * @protected
         */
        _hasForm: function() {
            return Boolean(this.$('form[data-collect=true]').length);
        },

        /**
         * Handles model save
         * @protected
         */
        _saveModel: function() {
            // page state is the same -- nothing to save
            if (this.model.get('pagestate').data === this.model.get('data')) {
                return;
            }
            // @TODO why data duplication is required?
            this.model.save({
                pagestate: {
                    pageId: this.model.get('pageId'),
                    data: this.model.get('data')
                }
            });
        },

        /**
         * Collects data of page forms and update model if state is changed
         *  - collects data
         *  - updates model
         * @protected
         */
        _collectState: function() {
            const pageId = this._combinePageId();
            if (!pageId) {
                return;
            }

            const data = JSON.stringify(this._collectFormsData());

            if (data === this.model.get('data')) {
                return;
            }

            this.model.set({
                pageId: pageId,
                data: data
            });
        },

        /**
         * Goes through collectable forms and collects data
         * @returns {Array}
         * @protected
         */
        _collectFormsData() {
            return this.$('form[data-collect=true]')
                .toArray()
                .map(form => this._extractInputsData($(form)));
        },

        /**
         * Extracts data from input elements collection
         * @param {jQuery.Element} $elem input element or container with inputs
         * @returns {Array<{name: string, value: string}>}
         * @protected
         */
        _extractInputsData($elem) {
            const inputSelector = `:input:not(${this.IGNORE})`;
            const $inputs = $elem.is(inputSelector) ? $elem : $elem.find(inputSelector);

            const data = $inputs
                .toArray()
                .map(input => {
                    const $input = $(input);
                    if (!$input.is('.select2[type=hidden]')) {
                        return $input.serializeArray()[0];
                    } else {
                        // collect select2 selected data
                        const selectedData = $input.inputWidget('data');
                        const itemData = {name: input.name, value: $input.val()};

                        if (!_.isEmpty(selectedData)) {
                            itemData.selectedData = selectedData;
                        }
                        return itemData;
                    }
                })
                .filter(item => item);
            return data;
        },

        /**
         * Reads data from model and restores page forms
         * @protected
         */
        _restoreState: function() {
            const data = this.model.get('data');

            if (data) {
                this._restoreForms(JSON.parse(data));
                mediator.trigger('pagestate_restored');
            }
        },

        /**
         * Updates form from data
         * @param {Array} data
         * @protected
         */
        _restoreForms: function(data) {
            $.each(data, function(index, el) {
                const form = $('form[data-collect=true]').eq(index);

                $.each(el, function(i, input) {
                    const element = form.find('[name="' + input.name + '"]');
                    switch (element.prop('type')) {
                        case 'checkbox':
                            element.filter('[value="' + input.value + '"]').prop('checked', true);
                            break;
                        case 'select-multiple':
                            element
                                .find('option').prop('selected', false).end()
                                .find('option[value="' + input.value + '"]').prop('selected', true);
                            break;
                        default:
                            if (input.selectedData) {
                                element.data('selected-data', input.selectedData);
                            }
                            if (input.value !== element.val()) {
                                element.val(input.value).trigger('change');
                            }
                    }
                });
            });
        },

        /**
         * Combines pageId
         * @returns {string}
         * @protected
         */
        _combinePageId: function() {
            return base64.encode(this._getCurrentURL());
        },

        /**
         * Parses URL for current page
         * @returns {Object}
         * @protected
         */
        _getCurrentURL: function() {
            let url = mediator.execute('currentUrl');
            url = mediator.execute('normalizeUrl', url);

            return url;
        },

        /**
         * Allows to overload _isStateTraceRequired stub-method
         *
         * @param {Function} callback
         */
        setStateTraceRequiredChecker: function(callback) {
            this._isStateTraceRequired = callback;
        },

        /**
         * Defines if page is in cache and state trace is required
         * (it is stub-method and can be overloaded)
         * @protected
         */
        _isStateTraceRequired: function() {
            return false;
        },

        /**
         * Check if passed or current state is different from initial sate
         *
         * @param {Array=} state if not passed collects current state
         * @returns {boolean}
         * @protected
         */
        _isStateChanged: function(state) {
            state = state || this._collectFormsData();
            return this._initialState !== null && this._isDifferentFromInitialState(state);
        },

        /**
         * Check if passed state is different from initial state
         * compares just name-value pairs
         * (comparison of JSON strings is not in use, because field items can contain extra-data)
         *
         * @param {Array} state
         * @returns {boolean}
         * @protected
         */
        _isDifferentFromInitialState: function(state) {
            const initialState = this._initialState;

            if (_.isArray(state)) {
                $.each(state, function(index, item) {
                    if (_.isArray(item)) {
                        item = $.grep(item, function(field) {
                            return _.isObject(field) && field.name.indexOf('temp-validation-name-') === -1;
                        });
                        state[index] = item;
                    }
                });
            }

            const isSame = initialState && _.every(initialState, function(form, i) {
                return _.isArray(state[i]) && _.every(form, function(field, j) {
                    return _.isObject(state[i][j]) &&
                        state[i][j].name === field.name && state[i][j].value === field.value;
                });
            });
            return !isSame;
        }
    });

    return PageStateView;
});
