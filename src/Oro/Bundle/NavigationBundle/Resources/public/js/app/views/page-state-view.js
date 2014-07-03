/*jshint browser:true*/
/*jslint browser:true, nomen:true*/
/*global define, window, base64_encode*/
define([
    'jquery',
    'underscore',
    'url',
    'routing',
    'oroui/js/app/views/base/view',
    'oroui/js/mediator',
    'base64'
], function ($, _, Url, routing, BaseView, mediator) {
    'use strict';

    var setInterval, clearInterval, PageStateView;

    setInterval = window.setInterval;
    clearInterval = window.clearInterval;

    PageStateView = BaseView.extend({
        pageStateTimer: null,

        listen: {
            'change:data model': '_saveModel',
            'sync model': '_updateCache',

            'page:request mediator': 'onPageRequest',
            'page:update mediator': 'onPageUpdate',
            'page:afterChange mediator': 'afterPageChange'
        },

        initialize: function () {
            this._init();
        },

        /**
         * Initializes form changes trace
         *  - if attributes is not in a cache, loads data from server
         * @private
         */
        _init: function () {
            var url, attributes, self;
            if (!this._hasForm()) {
                return;
            }

            self = this;
            attributes = mediator.execute('pageCache:state:fetch', 'form');
            if (attributes) {
                // data is cached no need to load it from server
                return;
            }

            url = routing.generate('oro_api_get_pagestate_checkid', {'pageId': this._combinePageId()});
            $.get(url).done(function (data) {
                var attributes;
                attributes = {
                    id: data.id,
                    pageId: data.pagestate.pageId,
                    data: data.pagestate.data
                };
                self._resetPageState(attributes);
                self._updateCache();
            });
        },

        /**
         * Clear page state timer and model on page request is started
         */
        onPageRequest: function () {
            if (this.pageStateTimer) {
                clearInterval(this.pageStateTimer);
            }
            this.model.clear({silent: true});
        },

        /**
         * Init page state on page updated
         */
        onPageUpdate: function () {
            this._init();
        },

        /**
         * Fetches model's attributes from cache on page changes is done
         */
        afterPageChange: function () {
            var attributes;
            if (!this._hasForm()) {
                return;
            }

            attributes = mediator.execute('pageCache:state:fetch', 'form');
            if (attributes) {
                this._resetPageState(attributes);
            }
        },

        /**
         * Rests page state model, restores page forms and start tracing changes
         * @param attributes
         * @private
         */
        _resetPageState: function (attributes) {
            this.model.set(attributes, {silent: true});
            this._restore();
            this.pageStateTimer = setInterval(_.bind(this._collect, this), 2000);
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
         * @private
         */
        _collect: function () {
            var pageId, data;

            pageId = this._combinePageId();
            if (!pageId) {
                return;
            }

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
                    var $item = $(item),
                        itemData = {name: item.name, value: $item.val()},
                        selectedData = $(item).select2('data');

                    if (!_.isEmpty(selectedData)) {
                        itemData.selectedData = [selectedData];
                    }

                    data[index].push(itemData);
                });
            });

            data = JSON.stringify(data);

            if (data === this.model.get('data')) {
                return;
            }
            this.model.set({
                pageId: pageId,
                data: data
            });
        },

        /**
         * Reads data from model and restores page forms
         * @private
         */
        _restore: function () {
            var data;
            data = this.model.get('data');

            if (!data) {
                return;
            }

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
            mediator.trigger("pagestate_restored");
        },

        /**
         * Combines pageId
         * @returns {string}
         * @private
         */
        _combinePageId: function () {
            var model, url, params;
            model = this.model;
            url = mediator.execute('currentUrl');
            url = new Url(url);
            url.search = url.query.toString();
            url.pathname = url.path;

            params = url.search.replace('?', '').split('&');

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
