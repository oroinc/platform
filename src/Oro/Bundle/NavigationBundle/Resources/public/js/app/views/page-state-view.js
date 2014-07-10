/*jslint nomen:true*/
/*global define, base64_encode*/
define([
    'jquery',
    'underscore',
    'url',
    'routing',
    'orotranslation/js/translator',
    'oroui/js/mediator',
    'oroui/js/modal',
    'oroui/js/app/views/base/view',
    'base64'
], function ($, _, Url, routing, __, mediator, Modal, BaseView) {
    'use strict';

    var PageStateView;

    PageStateView = BaseView.extend({
        events: {
            'click.action.data-api [data-action=page-refresh]': 'onRefreshClick'
        },

        listen: {
            'change:data model': '_saveModel',
            'change model': '_updateCache',

            'page:request mediator': 'onPageRequest',
            'page:update mediator': 'onPageUpdate',
            'page:afterChange mediator': 'afterPageChange'
        },

        initialize: function () {
            var confirmModal;

            this._initialData = null;
            this._restore = false;

            confirmModal = new Modal({
                title: __('Refresh Confirmation'),
                content: __('Your local changes will be lost. Are you sure you want to refresh the page?'),
                okText: __('Ok, got it.'),
                className: 'modal modal-primary',
                okButtonClass: 'btn-primary btn-large',
                cancelText: __('Cancel')
            });
            this.listenTo(confirmModal, 'ok', this._refreshPage);

            this.subview('confirmModal', confirmModal);

            if (this._hasForm()) {
                this._loadState();
            }
        },

        /**
         * Handles click on page refresh element
         * @param {jQuery.Event} e
         */
        onRefreshClick: function (e) {
            e.preventDefault();
            if (this._initialData !== null && this.model.get('data') !== this._initialData) {
                this.subview('confirmModal').open();
            } else {
                this._refreshPage();
            }
        },

        /**
         * Calls global refreshPage handler with proper options
         * @private
         */
        _refreshPage: function () {
            mediator.execute('refreshPage', {restore: true});
        },

        /**
         * Initializes form changes trace
         *  - if attributes is not in a cache, loads data from server
         * @private
         */
        _loadState: function () {
            var url, self;
            self = this;

            url = routing.generate('oro_api_get_pagestate_checkid', {'pageId': this._combinePageId()});
            $.get(url).done(function (data) {
                var attributes;
                attributes = {
                    pageId: data.pagestate.pageId || self._combinePageId(),
                    data: self._restore ? '' : data.pagestate.data
                };
                if (data.id) {
                    attributes.id = data.id;
                }
                self._initFormTracer(attributes);
                self._updateCache();
            });
        },

        /**
         * Clear page state timer and model on page request is started
         */
        onPageRequest: function () {
            this._initialData = null;
            this._restore = false;
            this.$el.off('change.page-state');
            this.model.clear({silent: true});
        },

        /**
         * Init page state on page updated
         */
        onPageUpdate: function (attributes, args) {
            var options;
            options = (args || {}).options;
            this._restore = Boolean(options && options.restore);
        },

        /**
         * Fetches model's attributes from cache on page changes is done
         */
        afterPageChange: function () {
            var attributes;
            if (!this._hasForm()) {
                return;
            }

            if (this._restore) {
                // delete cache if changes are discarded
                mediator.execute('pageCache:state:save', 'form', null);
            }

            attributes = mediator.execute('pageCache:state:fetch', 'form');
            if (attributes && attributes.id) {
                this._initFormTracer(attributes);
            } else {
                this._loadState();
            }
        },

        /**
         * Rests page state model, restores page forms and start tracing changes
         * @param attributes
         * @private
         */
        _initFormTracer: function (attributes) {
            var options;
            this._initialData = this._collectFormsData();
            if (attributes.data) {
                options = {silent: true};
            } else {
                attributes.data = this._initialData;
            }

            this.model.set(attributes, options);
            if (this.model.get('restore')) {
                this._restoreState();
                this.model.set('restore', false);
            }
            this.$el.on('change.page-state', _.bind(this._collectState, this));
        },

        /**
         * Updates state in cache on model sync
         * @private
         */
        _updateCache: function () {
            var attributes;
            attributes = {};
            _.extend(attributes, this.model.getAttributes());
            mediator.execute('pageCache:state:save', 'form', attributes);
        },

        /**
         * Defines if page has forms and state tracing is required
         * @returns {boolean}
         * @private
         */
        _hasForm: function () {
            return Boolean($('form[data-collect=true]').length);
        },

        /**
         * Handles model save
         * @private
         */
        _saveModel: function () {
            if (!this.model.get('pageId')) {
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
         * @private
         */
        _collectState: function () {
            var pageId, data;

            pageId = this._combinePageId();
            if (!pageId) {
                return;
            }

            data = this._collectFormsData();

            if (data === this.model.get('data')) {
                return;
            }

            this.model.set({
                pageId: pageId,
                data: data
            });
        },

        /**
         * Goes through the form and collects data
         * @returns {string}
         * @private
         */
        _collectFormsData: function () {
            var data;
            data = [];
            $('form[data-collect=true]').each(function (index, el) {
                var items = $(el)
                    .find('input, textarea, select')
                    .not(':input[type=button],   :input[type=submit], :input[type=reset], ' +
                         ':input[type=password], :input[type=file],   :input[name$="[_token]"], ' +
                         '.select2[type=hidden]');

                data[index] = items.serializeArray();

                // collect select2 selected data
                items = $(el).find('.select2[type=hidden], .select2[type=select]');
                _.each(items, function (item) {
                    var $item, itemData, selectedData;
                    $item = $(item);
                    itemData = {name: item.name, value: $item.val()};

                    if ($item.data('select2')) {
                        // select2 is already initialized
                        selectedData = $item.select2('data');
                    }
                    if (!_.isEmpty(selectedData) && $.isPlainObject(selectedData)) {
                        itemData.selectedData = [selectedData];
                    }

                    data[index].push(itemData);
                });
            });
            data = JSON.stringify(data);
            return data;
        },

        /**
         * Reads data from model and restores page forms
         * @private
         */
        _restoreState: function () {
            var data;
            data = this.model.get('data');

            if (data) {
                this._restoreForms(data);
                mediator.trigger("pagestate_restored");
            }
        },

        /**
         * Updates form from data
         * @param {string} data JSON
         * @private
         */
        _restoreForms: function (data) {
            data = JSON.parse(data);

            $.each(data, function (index, el) {
                var form = $('form[data-collect=true]').eq(index);
                form.find('option').prop('selected', false);

                $.each(el, function (i, input) {
                    var element = form.find('[name="' + input.name + '"]');
                    switch (element.prop('type')) {
                    case 'checkbox':
                        element.filter('[value="' +  input.value + '"]').prop('checked', true);
                        break;
                    case 'select-multiple':
                        element.find('option[value="' + input.value + '"]').prop('selected', true);
                        break;
                    default:
                        if (input.selectedData) {
                            element.data('selected-data', input.selectedData);
                        }
                        element.val(input.value);
                    }
                });
            });
        },

        /**
         * Combines pageId
         * @returns {string}
         * @private
         */
        _combinePageId: function () {
            var model, url, params, _ref;
            model = this.model;
            url = mediator.execute('currentUrl');

            _ref = url.split('?');
            url = {
                pathname: _ref[0],
                search: _ref[1] || ''
            };

            params = url.search.split('&');

            params = _.filter(params, function (part) {
                var toRestore;
                toRestore = part.indexOf('restore') !== -1;
                if (toRestore) {
                    model.set('restore', true);
                }
                return !toRestore && part.length;
            });

            url = url.pathname + (params.length ? '?' + params.join('&') : '');
            return base64_encode(url);
        }
    });

    return PageStateView;
});
