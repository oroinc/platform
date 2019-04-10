define(function(require) {
    'use strict';

    var SelectCreateInlineTypeView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var routing = require('routing');
    var DialogWidget = require('oro/dialog-widget');
    var BaseView = require('oroui/js/app/views/base/view');
    var PageableCollection = require('orodatagrid/js/pageable-collection');

    SelectCreateInlineTypeView = BaseView.extend({
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
         * @inheritDoc
         */
        constructor: function SelectCreateInlineTypeView() {
            SelectCreateInlineTypeView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            SelectCreateInlineTypeView.__super__.initialize.apply(this, arguments);
            _.extend(this, _.pick(options, 'urlParts', 'entityLabel', 'existingEntityGridId', 'inputSelector'));
        },

        buildRouteParams: function(routeType) {
            var routeParams = this.urlParts[routeType].parameters;
            return _.extend({}, routeParams, this.$(this.inputSelector).data('select2_query_additional_params'));
        },

        setEnableState: function(enabled) {
            this.$('button').prop('disabled', !enabled);
            this.$(this.inputSelector).select2('readonly', !enabled);
        },

        onSelect: function(e) {
            e.preventDefault();
            var routeName = _.result(this.urlParts.grid, 'gridWidgetView') || this.urlParts.grid.route;
            var routeParams = this.buildRouteParams('grid');
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
                    close: _.bind(this.onDialogClose, this)
                }
            });

            this.dialogWidget.once('grid-row-select', _.bind(this.onGridRowSelect, this));
            this.dialogWidget.render();
        },

        /**
         * Add query_additional_params to select datagrid AJAX request to have them passed in both cases
         * when grid is loaded in widget and when grid data is loaded with AJAX requests like pager, search, etc.
         *
         * @param {Object} collection
         */
        onGridLoadComplete: function(collection) {
            var routeParams = this.buildRouteParams('grid');
            if (collection.inputName === routeParams.gridName) {
                var additionalParameters = _.extend(
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
                var url = collection.url;
                if (_.isUndefined(url)) {
                    return;
                }
                var newParams = _.extend(this._getQueryParamsFromUrl(url), params);
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

            var query = url.substring(url.indexOf('?') + 1, url.length);
            if (!query.length) {
                return {};
            }

            return PageableCollection.decodeStateData(query);
        },

        onDialogClose: function() {
            this.$(this.inputSelector).off('.' + this.dialogWidget._wid);
        },

        onGridRowSelect: function(data) {
            var eventNamespace = this.dialogWidget._wid;
            var loadingStarted = false;
            var $input = this.$(this.inputSelector);
            var onSelect = _.bind(function() {
                this.dialogWidget.remove();
                this.dialogWidget = null;

                var $input = this.$(this.inputSelector);
                var $form = $input.closest('form');

                if ($form.length && $form.data('validator')) {
                    $form.validate().element($input);
                }

                $input.inputWidget('focus');
            }, this);
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

        onCreate: function(e) {
            e.preventDefault();
            var routeName = this.urlParts.create.route;
            var routeParams = this.buildRouteParams('create');
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
                    close: _.bind(this.onDialogClose, this)
                }
            });

            this.dialogWidget.once('formSave', _.bind(function(id) {
                var $input = this.$(this.inputSelector);
                $input.inputWidget('val', id, true);
                this.dialogWidget.remove();
                this.dialogWidget = null;

                var $form = $input.closest('form');
                if ($form.length && $form.data('validator')) {
                    $form.validate().element($input);
                }

                $input.inputWidget('focus');
            }, this));

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
        }
    });

    return SelectCreateInlineTypeView;
});
