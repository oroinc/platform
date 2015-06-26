define(function (require) {
    'use strict';

    var Select2Component,
        $ = require('jquery'),
        _ = require('underscore'),
        tools = require('oroui/js/tools'),
        Select2View = require('oroform/js/app/views/select2-view'),
        BaseComponent = require('oroui/js/app/components/base/component');
    Select2Component = BaseComponent.extend({

        url: '',
        perPage: 10,
        excluded: [],
        view: null,
        ViewType: Select2View,

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            var config = options.configs;
            this.perPage = _.result(config, 'per_page') || this.perPage;
            this.url = _.result(options, 'url') || '';
            this.excluded = _.result(options, 'excluded') || this.excluded;
            config = this.preConfig(config);
            config = this.setConfig(config);
            if (options._sourceElement.is('select') || config.query || config.ajax || config.data || config.tags) {
                this.view = new this.ViewType({el: options._sourceElement, select2Config: config});
            }
        },

        preConfig: function (config) {
            var that = this;
            if (this.url) {
                config.ajax = {
                    url: this.url,
                    data: function (query, page) {
                        return {
                            page: page,
                            per_page: that.perPage,
                            name: config.autocomplete_alias,
                            query: that.makeQuery(query, config)
                        };
                    },
                    results: function (data, page) {
                        return data;
                    }
                };
            }
            return config;
        },

        setConfig: function (config) {
            var that = this;
            // configure AJAX object if it exists
            if (config.ajax !== undefined) {
                config.minimumInputLength = 0;
                config.initSelection = _.result(config, 'initSelection') || _.bind(initSelection, config);
                if (that.excluded){
                    config.ajax.results = _.wrap(config.ajax.results, function (func, data, page) {
                        var response = func.call(this, data, page);
                        response.results = _.filter(response.results, function (item) {
                            return !item.hasOwnProperty('id') || _.indexOf(that.excluded, item.id) < 0;
                        });
                        return response;
                    });
                }
                config.ajax.quietMillis = _.result(config.ajax, 'quietMillis') || 700;
            } else {
                // configure non AJAX based Select2
                if (config.minimumResultsForSearch === undefined) {
                    config.minimumResultsForSearch = 7;
                }
                config.sortResults = function (results, container, query) {
                    if (!query.term || query.term.length < 1) {
                        return results;
                    }
                    var expression = tools.safeRegExp(query.term, 'im');

                    var sortIteratorDelegate = function (first, second) {
                        var inFirst = first.text.search(expression);
                        var inSecond = second.text.search(expression);

                        if (inFirst == -1 || inSecond == -1) {
                            return inSecond - inFirst;
                        }

                        return inFirst - inSecond;
                    };

                    return results.sort(sortIteratorDelegate);
                }
            }
            // set default values for other Select2 options
            if (config.formatResult === undefined) {
                config.formatResult = formatFabric(config, config.result_template || false);
            }
            if (config.formatSelection === undefined) {
                config.formatSelection = formatFabric(config, config.selection_template || false);
            }
            _.defaults(config, {
                escapeMarkup: function (m) { return m; },
                dropdownAutoWidth: true,
                openOnEnter: null
            });

            return config;
        },

        makeQuery: function (query, configs) {
            return query;
        }
    });

    function initSelection(element, callback) {
        var config = this;

        var handleResults = function(data) {
            if (config.multiple === true) {
                callback(data);
            } else {
                callback(data.pop());
            }
        };

        var setSelect2ValueById = function(id) {
            if (_.isArray(id)) {
                id = id.join(config.separator);
            }
            var select2Obj = element.data('select2');
            var select2AjaxOptions = select2Obj.opts.ajax;
            var searchData = select2AjaxOptions.data(id, 1, true);
            var url = (typeof select2AjaxOptions.url === 'function')
                ? select2AjaxOptions.url.call(select2Obj, id, 1)
                : select2AjaxOptions.url;

            searchData.search_by_id = true;
            element.trigger('select2-data-request');
            $.ajax({
                url: url,
                data: searchData,
                success: function(response) {
                    if (typeof select2AjaxOptions.results == 'function') {
                        response = select2AjaxOptions.results.call(select2Obj, response, 1);
                    }
                    if (typeof response.results != 'undefined') {
                        handleResults(response.results);
                    }
                    element.trigger('select2-data-loaded');
                }
            });
        };

        var currentValue = element.select2('val');
        if (!_.isArray(currentValue)) {
            currentValue = [currentValue];
        }

        var selectedData = element.data('selected-data');
        if (!_.isArray(selectedData)) {
            selectedData = [selectedData];
        }

        // elementData must have name
        var elementData = _.filter(
            selectedData,
            function (item) {
                return _.result(item, 'fullName') || _.result(item, 'name');
            }
        );

        if (elementData.length > 0) {
            var dataIds = _.map(elementData, function(item) {
                return item.id;
            });

            // handle case when creation of new item allowed and value should be restored (f.e. validation failed)
            dataIds = _.compact(dataIds);

            if (dataIds.length === 0 || dataIds.sort().join(config.separator) === currentValue.sort().join(config.separator)) {
                handleResults(elementData);
            } else {
                setSelect2ValueById(currentValue);
            }
        } else {
            setSelect2ValueById(currentValue);
        }
    }
    function highlightSelection(str, selection) {
        return str && selection && selection.term ?
            str.replace(tools.safeRegExp(selection.term, 'ig'), '<span class="select2-match">$&</span>') : str;
    }

    function getTitle(data, properties) {
        var title = '', result;
        if (data) {
            if (properties === undefined) {
                if (data.text !== undefined) {
                    title = data.text;
                }
            } else {
                result = [];
                _.each(properties, function(property) {
                    result.push(data[property]);
                });
                title = result.join(' ');
            }
        }
        return title;
    }

    function formatFabric(config, jsTemplate) {
        // pre-compile template if it exists
        if (jsTemplate) {
            jsTemplate = _.template(jsTemplate);
        }

        return function (object, container, query) {
            if ($.isEmptyObject(object)) {
                return undefined;
            }
            var result = '',
                highlight = function (str) {
                    return object.children ? str : highlightSelection(str, query);
                };
            if (object._html !== undefined) {
                result = _.escape(object._html);
            } else if (jsTemplate) {
                object = _.clone(object);
                object.highlight = highlight;
                if (config.formatContext !== undefined) {
                    object.context = config.formatContext();
                }
                result = jsTemplate(object);
            } else {
                result = highlight(_.escape(getTitle(object, config.properties)));
            }
            return result;
        };
    }

    return Select2Component;
});
