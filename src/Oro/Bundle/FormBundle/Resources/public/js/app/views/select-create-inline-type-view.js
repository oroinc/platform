define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const routing = require('routing');
    const DialogWidget = require('oro/dialog-widget');
    const BaseView = require('oroui/js/app/views/base/view');
    const PageableCollection = require('orodatagrid/js/pageable-collection');

    const SelectCreateInlineTypeView = BaseView.extend({
        autoRender: true,

        urlParts: null,

        inputSelector: null,

        select2QueryAdditionalParams: null,

        entityLabel: '',

        existingEntityGridId: null,

        dialogWidget: null,

        events: {
            'click .entity-select-btn': 'onSelect',
            'click .entity-create-btn': 'onCreate'
        },

        listen: {
            'grid_load:complete mediator': 'onGridLoadComplete'
        },

        /**
         * @inheritdoc
         */
        constructor: function SelectCreateInlineTypeView(options) {
            SelectCreateInlineTypeView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize(options) {
            SelectCreateInlineTypeView.__super__.initialize.call(this, options);
            _.extend(this, _.pick(options, 'urlParts', 'entityLabel', 'existingEntityGridId', 'inputSelector'));
        },

        buildRouteParams(routeType) {
            const routeParams = this.urlParts[routeType].parameters;
            return _.extend({}, routeParams, this.$(this.inputSelector).data('select2_query_additional_params'));
        },

        setEnableState(enabled) {
            this.$('button').prop('disabled', !enabled);
            this.$(this.inputSelector).select2('readonly', !enabled);
        },

        /**
         * @param {Object} e
         */
        onSelect(e) {
            e.preventDefault();

            if (this.dialogWidget) {
                return;
            }

            const routeName = _.result(this.urlParts.grid, 'gridWidgetView') || this.urlParts.grid.route;
            const routeParams = this.buildRouteParams('grid');
            this.dialogWidget = new DialogWidget({
                title: __('Select {{ entity }}', {entity: this.entityLabel}),
                url: routing.generate(routeName, routeParams),
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 1280,
                    height: 650,
                    close: this.onDialogClose.bind(this)
                }
            });

            this.dialogWidget.once('grid-row-select', this.onGridRowSelect.bind(this));
            this.dialogWidget.render();
        },

        /**
         * Add query_additional_params to select datagrid AJAX request to have them passed in both cases
         * when grid is loaded in widget and when grid data is loaded with AJAX requests like pager, search, etc.
         *
         * @param {Object} collection
         */
        onGridLoadComplete: function(collection) {
            const routeParams = this.buildRouteParams('grid');
            if (collection.inputName === routeParams.gridName) {
                const additionalParameters = _.extend(
                    {},
                    this.select2QueryAdditionalParams,
                    this.$(this.inputSelector).data('select2_query_additional_params')
                );
                this._patchGridCollectionUrl(collection, additionalParameters);
            }
        },

        /**
         * @param {Object} collection
         * @param {Object} params
         * @private
         */
        _patchGridCollectionUrl: function(collection, params) {
            if (!_.isUndefined(collection)) {
                let url = collection.url;
                if (_.isUndefined(url)) {
                    return;
                }
                const newParams = _.extend(this._getQueryParamsFromUrl(url), params);
                if (url.indexOf('?') !== -1) {
                    url = url.substring(0, url.indexOf('?'));
                }
                if (!_.isEmpty(newParams)) {
                    collection.url = url + '?' + $.param(newParams);
                }
            }
        },

        /**
         * @param {String} url
         * @return {Object}
         * @private
         */
        _getQueryParamsFromUrl: function(url) {
            if (_.isUndefined(url)) {
                return {};
            }

            if (url.indexOf('?') === -1) {
                return {};
            }

            const query = url.substring(url.indexOf('?') + 1, url.length);
            if (!query.length) {
                return {};
            }

            return PageableCollection.decodeStateData(query);
        },

        onDialogClose: function() {
            this.$(this.inputSelector).off('.' + this.dialogWidget._wid);
            delete this.dialogWidget;
        },

        onGridRowSelect: function(data) {
            const eventNamespace = this.dialogWidget._wid;
            let loadingStarted = false;
            const $input = this.$(this.inputSelector);
            const onSelect = () => {
                this.dialogWidget.remove();
                this.dialogWidget = null;

                const $input = this.$(this.inputSelector);
                const $form = $input.closest('form');

                if ($form.length && $form.data('validator')) {
                    $form.validate().element($input);
                }

                $input.inputWidget('focus');
            };
            this.dialogWidget._showLoading();
            $input.one('select2-data-request.' + eventNamespace, function() {
                loadingStarted = true;
                $(this).one('select2-data-loaded.' + eventNamespace, onSelect);
            });
            $input.inputWidget('val', data.model.get(this.existingEntityGridId), true);
            // if there was no data request sent to server
            if (!loadingStarted) {
                onSelect();
            }
        },

        /**
         * @param {Object} e
         */
        onCreate: function(e) {
            e.preventDefault();

            if (this.dialogWidget) {
                return;
            }

            const routeName = this.urlParts.create.route;
            const routeParams = this.buildRouteParams('create');
            this.dialogWidget = new DialogWidget({
                title: __('Create {{ entity }}', {entity: this.entityLabel}),
                url: routing.generate(routeName, routeParams),
                stateEnabled: false,
                incrementalPosition: true,
                dialogOptions: {
                    modal: true,
                    allowMaximize: true,
                    width: 1280,
                    height: 650,
                    close: this.onDialogClose.bind(this)
                }
            });

            this.dialogWidget.once('formSave', id => {
                const $input = this.$(this.inputSelector);
                $input.inputWidget('val', id, true);
                this.dialogWidget.remove();
                this.dialogWidget = null;

                const $form = $input.closest('form');
                if ($form.length && $form.data('validator')) {
                    $form.validate().element($input);
                }

                $input.inputWidget('focus');
            });

            this.dialogWidget.render();
        },

        getUrlParts: function() {
            return this.urlParts;
        },

        setUrlParts: function(newParts) {
            this.urlParts = newParts;
        },

        setSelection: function(value) {
            this.$(this.inputSelector).inputWidget('val', value);
        },

        getSelection: function() {
            return this.$(this.inputSelector).inputWidget('val');
        },

        dispose() {
            if (this.disposed) {
                return;
            }

            if (this.dialogWidget && !this.dialogWidget.disposed) {
                // Parent dialog with select create input is closed - current dialog should be disposed as well
                this.dialogWidget.dispose();
            }

            return SelectCreateInlineTypeView.__super__.dispose.call(this);
        }
    });

    return SelectCreateInlineTypeView;
});
