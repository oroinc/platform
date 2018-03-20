define(function(require) {
    'use strict';

    var Select2Component;
    var $ = require('jquery');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');
    var Select2View = require('oroform/js/app/views/select2-view');
    var BaseComponent = require('oroui/js/app/components/base/component');

    Select2Component = BaseComponent.extend({
        resultTemplate: require('text!oroui/templates/select2/default-template.html'),
        selectionTemplate: require('text!oroui/templates/select2/default-template.html'),
        url: '',
        perPage: 10,
        excluded: [],
        view: null,
        ViewType: Select2View,

        /**
         * @inheritDoc
         */
        constructor: function Select2Component() {
            Select2Component.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            var config = options.configs || {};

            // Check enable icon for each option and set default template
            if (config.showIcon) {
                if (!config.result_template) {
                    config.result_template = this.resultTemplate;
                }
                if (!config.selection_template) {
                    config.selection_template = this.selectionTemplate;
                }
            }

            this.perPage = _.result(config, 'per_page') || this.perPage;
            this.url = _.result(options, 'url') || '';
            this.excluded = _.result(options, 'excluded') || this.excluded;
            config = this.preConfig(config);
            config = this.setConfig(config);
            if (options._sourceElement.is('select') || config.query || config.ajax || config.data || config.tags) {
                this.view = new this.ViewType(this.prepareViewOptions(options, config));
            }
        },

        /**
         * Prepares options for the related view
         *
         * @param {Object} options - component's options
         * @param {Object} config - select2's options
         * @return {Object}
         */
        prepareViewOptions: function(options, config) {
            return {
                el: options._sourceElement,
                select2Config: config
            };
        },

        preConfig: function(config) {
            var that = this;
            if (this.url) {
                config.ajax = {
                    url: this.url,
                    data: function(query, page) {
                        return {
                            page: page,
                            per_page: that.perPage,
                            name: config.autocomplete_alias,
                            query: that.makeQuery(query, config)
                        };
                    },
                    results: function(data, page) {
                        return data;
                    }
                };
            }

            if (!config.hasOwnProperty('createSearchChoice') &&
                config.hasOwnProperty('ajax') &&
                config.hasOwnProperty('tags')
            ) {
                config.createSearchChoice = function(term, data) {
                    if (!dataHasText(data, term)) {
                        return {
                            id: term,
                            text: term
                        };
                    }
                };
            }

            return config;
        },

        setConfig: function(config) {
            // configure AJAX object if it exists
            if (config.ajax !== undefined) {
                config.minimumInputLength = _.result(config, 'minimumInputLength', 0);
                config.initSelection = config.initSelection ||
                    _.partial(this.constructor.initSelection, this.constructor, config);
                if (this.excluded) {
                    var excluded = this.excluded;
                    config.ajax.results = _.wrap(config.ajax.results, function(func, data, page) {
                        var response = func.call(this, data, page);
                        response.results = _.filter(response.results, function(item) {
                            return !_.contains(excluded, item.id);
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
                config.sortResults = function(results, container, query) {
                    if (!query.term || query.term.length < 1) {
                        return results;
                    }
                    var expression = tools.safeRegExp(query.term, 'im');

                    var sortIteratorDelegate = function(first, second) {
                        var inFirst = first.text.search(expression);
                        var inSecond = second.text.search(expression);

                        if (inFirst === -1 || inSecond === -1) {
                            return inSecond - inFirst;
                        }

                        return inFirst - inSecond;
                    };

                    return results.sort(sortIteratorDelegate);
                };
            }
            // set default values for other Select2 options
            if (config.formatResult === undefined) {
                config.formatResult = formatFabric(config, config.result_template || false);
            }
            if (config.formatSelection === undefined) {
                config.formatSelection = formatFabric(config, config.selection_template || false);
            }
            _.defaults(config, {
                escapeMarkup: function(m) {
                    return m;
                },
                dropdownAutoWidth: true,
                openOnEnter: null
            });

            return config;
        },

        makeQuery: function(query, configs) {
            return query;
        }
    }, {
        initSelection: function(self, config, element, callback) {
            var selectedData;
            var dataIds;
            var handleResults = _.partial(self.handleResults, self, config, callback);
            var setSelect2ValueById = _.partial(self.setSelect2ValueById, self, config, element, callback);
            var inputValue = element.inputWidget('val');
            var currentValue = inputValue === '' ? [] : tools.ensureArray(inputValue);

            if (config.forceSelectedData && element.data('selected-data')) {
                var data = element.data('selected-data');
                var result = [];
                if (!_.isObject(data)) {
                    _.each(data.split(config.separator), function(item) {
                        result.push(JSON.parse(item));
                    });
                }
                handleResults(result.length > 0 ? result : data);
                return;
            }

            selectedData = _.filter(
                tools.ensureArray(element.data('selected-data')),
                function(item) {
                    return _.isObject(item);
                }
            );

            var emptySelection = selectedData.length === 0 ||
                (selectedData.length === 1 && _.first(selectedData).id === null && _.first(selectedData).name === null);

            if (!emptySelection) {
                dataIds = _.map(selectedData, function(item) {
                    return item.id;
                });

                // handle case when creation of new item allowed and value should be restored (f.e. validation failed)
                dataIds = _.compact(dataIds);

                if (dataIds.length === 0 ||
                    dataIds.sort().join(config.separator) === currentValue.sort().join(config.separator)) {
                    handleResults(selectedData);
                    return;
                }
            }

            if (currentValue.length !== 0) {
                setSelect2ValueById(currentValue);
            }
        },
        setSelect2ValueById: function(self, config, element, callback, id) {
            var ids = _.isArray(id) ? id.join(config.separator) : id;
            var handleResults = _.partial(self.handleResults, self, config, callback);
            var select2Obj = element.data('select2');
            var ajaxOptions = select2Obj.opts.ajax;
            var searchData = ajaxOptions.data(ids, 1, true);
            var url = _.isFunction(ajaxOptions.url) ? ajaxOptions.url.call(select2Obj, ids, 1) : ajaxOptions.url;

            searchData.search_by_id = true;
            element.trigger('select2-data-request');
            $.ajax({
                url: url,
                data: searchData,
                success: function(response) {
                    if (_.isFunction(ajaxOptions.results)) {
                        response = ajaxOptions.results.call(select2Obj, response, 1);
                    }
                    if (typeof response.results !== 'undefined') {
                        handleResults(response.results);
                    }
                    element.trigger({type: 'select2-data-loaded', items: response});
                    element.data('selected-data', element.select2('data'));
                }
            });
        },

        handleResults: function(self, config, callback, data) {
            if (config.multiple === true) {
                callback(data);
            } else {
                var item = data.pop();
                if (!_.isUndefined(item) && !_.isUndefined(item.children) && _.isArray(item.children)) {
                    callback(item.children.pop());
                } else {
                    callback(item);
                }
            }
        }
    });

    function highlightSelection(str, selection) {
        return str && selection && selection.term
            ? str.replace(tools.safeRegExp(selection.term.trim(), 'ig'), '<span class="select2-match">$&</span>') : str;
    }

    function getTitle(data, properties) {
        var title = '';
        var result;
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
        if (jsTemplate && !_.isFunction(jsTemplate)) {
            jsTemplate = _.template(jsTemplate);
        }

        return function(object, container, query) {
            if ($.isEmptyObject(object)) {
                return undefined;
            }
            var result = '';
            var highlight = function(str) {
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

    function dataHasText(data, text) {
        return _.some(data, function(row) {
            if (!row.hasOwnProperty('children')) {
                return row.text.localeCompare(text) === 0;
            }

            return dataHasText(row.children, text);
        });
    }

    return Select2Component;
});
